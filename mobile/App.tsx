import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Image,
  SafeAreaView,
  StatusBar,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { AuthProvider, useAuth } from './src/context/AuthContext';
import { COLORS } from './src/theme/colors';
import { AuthScreen } from './src/screens/AuthScreen';
import { DashboardScreen } from './src/screens/DashboardScreen';
import { InventoryScreen } from './src/screens/InventoryScreen';
import {
  TransactionLaunchRequest,
  TransactionsScreen,
} from './src/screens/TransactionsScreen';
import {
  PurchasesLaunchRequest,
  PurchasesSuppliersScreen,
} from './src/screens/PurchasesSuppliersScreen';
import { ReportsScreen } from './src/screens/ReportsScreen';
import {
  StaffAuditScreen,
  StaffLaunchRequest,
} from './src/screens/StaffAuditScreen';
import {
  SettingsLaunchRequest,
  SettingsProfileScreen,
} from './src/screens/SettingsProfileScreen';
import { FirstTimeGuideModal, useFirstTimeGuide } from './src/components/FirstTimeGuide';
import { NotificationsModal } from './src/components/NotificationsModal';
import { DrawerAction, MobileDrawerMenu } from './src/components/MobileDrawerMenu';
import {
  EMPTY_NOTIFICATION_RESPONSE,
  fetchStockNotifications,
} from './src/services/notifications';
import {
  createInitialShellState,
  resolveShellStateForTabSelection,
} from './src/services/navigationState';
import { StockNotificationItem, StockNotificationResponse } from './src/types/models';

interface BottomNavTab {
  key: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  activeIcon: keyof typeof Ionicons.glyphMap;
  settingsSubTab?: 'profile' | 'business';
}

const OWNER_BOTTOM_TABS: BottomNavTab[] = [
  { key: 'dashboard', label: 'Home', icon: 'home-outline', activeIcon: 'home' },
  { key: 'inventory', label: 'Inventory', icon: 'cube-outline', activeIcon: 'cube' },
  { key: 'reports', label: 'Reports', icon: 'bar-chart-outline', activeIcon: 'bar-chart' },
  {
    key: 'settings',
    label: 'Settings',
    icon: 'settings-outline',
    activeIcon: 'settings',
    settingsSubTab: 'business',
  },
];

const STAFF_BOTTOM_TABS: BottomNavTab[] = [
  { key: 'dashboard', label: 'Home', icon: 'home-outline', activeIcon: 'home' },
  { key: 'inventory', label: 'Inventory', icon: 'cube-outline', activeIcon: 'cube' },
  {
    key: 'transactions',
    label: 'Transactions',
    icon: 'swap-horizontal-outline',
    activeIcon: 'swap-horizontal',
  },
  {
    key: 'settings',
    label: 'Profile',
    icon: 'person-circle-outline',
    activeIcon: 'person-circle',
    settingsSubTab: 'profile',
  },
];

const MainShell: React.FC = () => {
  const { user, business, initializing, logout } = useAuth();
  const [navState, setNavState] = useState(createInitialShellState);
  const activeTab = navState.activeTab;
  const activeDrawerKey = navState.activeDrawerKey;
  const visitedTabs = navState.visitedTabs;

  const [txLaunch, setTxLaunch] = useState<TransactionLaunchRequest | null>(null);
  const [purchasesLaunch, setPurchasesLaunch] = useState<PurchasesLaunchRequest | null>(null);
  const [staffLaunch, setStaffLaunch] = useState<StaffLaunchRequest | null>(null);
  const [settingsLaunch, setSettingsLaunch] = useState<SettingsLaunchRequest | null>(null);

  const guide = useFirstTimeGuide();
  const [drawerVisible, setDrawerVisible] = useState(false);
  const [notifVisible, setNotifVisible] = useState(false);
  const [notifLoading, setNotifLoading] = useState(false);
  const [notifError, setNotifError] = useState<string | null>(null);
  const [notifData, setNotifData] = useState<StockNotificationResponse>(EMPTY_NOTIFICATION_RESPONSE);

  const loadNotifications = useCallback(async (skipCache = false) => {
    setNotifLoading(true);
    setNotifError(null);
    try {
      const res = await fetchStockNotifications(skipCache);
      setNotifData(res);
    } catch (err: any) {
      setNotifError(err?.message || 'Unable to load notifications.');
    } finally {
      setNotifLoading(false);
    }
  }, []);

  useEffect(() => {
    setNavState(createInitialShellState());
    setTxLaunch(null);
    setPurchasesLaunch(null);
    setStaffLaunch(null);
    setSettingsLaunch(null);
    setNotifVisible(false);
    setDrawerVisible(false);

    if (!user || (user.role !== 'owner' && user.role !== 'staff')) {
      setNotifData(EMPTY_NOTIFICATION_RESPONSE);
      return;
    }
    loadNotifications(false);
  }, [user?.id, user?.role, loadNotifications]);

  const isOwner = user?.role === 'owner';

  const handleSelectTab = useCallback(
    (tabKey: string, subTab?: string) => {
      setNavState((prev) => resolveShellStateForTabSelection(prev, tabKey, user?.role, subTab));

      if (tabKey === 'settings') {
        const nextSub = isOwner
          ? ((subTab as 'profile' | 'business' | 'backup') || 'business')
          : 'profile';
        setSettingsLaunch({ tab: nextSub, nonce: Date.now() });
      }
      if (isOwner && tabKey === 'purchases' && subTab) {
        setPurchasesLaunch({
          tab: subTab as PurchasesLaunchRequest['tab'],
          nonce: Date.now(),
        });
      }
      if (isOwner && tabKey === 'staff' && subTab) {
        setStaffLaunch({
          section: subTab as StaffLaunchRequest['section'],
          nonce: Date.now(),
        });
      }
      if (tabKey === 'transactions' && subTab) {
        setTxLaunch({
          mode: subTab as TransactionLaunchRequest['mode'],
          nonce: Date.now(),
        });
      }

      if (user && (tabKey === 'dashboard' || tabKey === 'inventory')) {
        loadNotifications(false);
      }
    },
    [user, isOwner, loadNotifications]
  );

  const handleQuickTransaction = useCallback(
    (mode: TransactionLaunchRequest['mode'], itemId?: number, typeFilter?: string) => {
      setNavState((prev) =>
        resolveShellStateForTabSelection(prev, 'transactions', user?.role, mode)
      );
      setTxLaunch({ mode, itemId, typeFilter, nonce: Date.now() });
    },
    [user?.role]
  );

  const handleDrawerNavigate = useCallback(
    (action: DrawerAction) => {
      handleSelectTab(action.tab, action.subTab);
    },
    [handleSelectTab]
  );

  const openNotificationsModal = useCallback(() => {
    setNotifVisible(true);
    loadNotifications(true);
  }, [loadNotifications]);

  const handleSelectNotification = useCallback(
    (notif: StockNotificationItem) => {
      setNotifVisible(false);
      if (notif.action_type === 'spoilage_history') {
        handleQuickTransaction('history', undefined, 'spoilage');
        return;
      }
      if (notif.item_id) {
        handleQuickTransaction('stock_in', notif.item_id);
        return;
      }
      handleSelectTab('inventory');
    },
    [handleQuickTransaction, handleSelectTab]
  );

  const bottomTabs = useMemo(
    () => (isOwner ? OWNER_BOTTOM_TABS : STAFF_BOTTOM_TABS),
    [isOwner]
  );

  if (initializing) {
    return (
      <View style={styles.centered}>
        <ActivityIndicator size="large" color={COLORS.primary} />
        <Text style={styles.loadingText}>Loading MyBatchoy...</Text>
      </View>
    );
  }

  if (!user) {
    return <AuthScreen />;
  }

  if (user.role !== 'owner' && user.role !== 'staff') {
    const portalTitle =
      user.role === 'system_admin'
        ? 'System Admin Technical Portal'
        : user.role === 'supplier'
        ? 'External Supplier Portal'
        : 'Web Portal Required';
    const portalDesc =
      user.role === 'system_admin'
        ? 'Technical administration, security monitoring, backups, and access requests run on the web admin portal. Business inventory modules remain isolated.'
        : user.role === 'supplier'
        ? 'Assigned purchase order confirmations, delivery tracking, and supplier profile controls are available via the Supplier Web Portal.'
        : 'This mobile app is exclusively for Owner and Staff inventory operations. Please use the web portal for your account role.';

    return (
      <SafeAreaView style={styles.centered}>
        <View style={{ padding: 24, maxWidth: 380, width: '100%' }}>
          <Text style={{ color: COLORS.secondary, fontSize: 12, fontWeight: '700', marginBottom: 6 }}>
            {user.role_label.toUpperCase()}
          </Text>
          <Text style={{ color: COLORS.text, fontSize: 20, fontWeight: '800', marginBottom: 10 }}>
            {portalTitle}
          </Text>
          <Text style={{ color: '#BDBDBD', fontSize: 14, lineHeight: 20, marginBottom: 18 }}>
            {portalDesc}
          </Text>
          <TouchableOpacity
            onPress={logout}
            style={{
              backgroundColor: COLORS.primary,
              paddingVertical: 12,
              borderRadius: 10,
              alignItems: 'center',
            }}
          >
            <Text style={{ color: '#FFFFFF', fontWeight: '700' }}>Sign Out ({user.username})</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      {/* Phone Top Header Matching Target Reference */}
      <View style={styles.topBar}>
        <TouchableOpacity
          style={styles.brandLockup}
          onPress={() => handleSelectTab('dashboard')}
          activeOpacity={0.85}
        >
          <View style={styles.mascotWrap}>
            <Image source={require('./assets/eustace-mascot.jpg')} style={styles.mascotImg} />
          </View>
          <View>
            <View style={{ flexDirection: 'row', alignItems: 'baseline', gap: 4 }}>
              <Text style={styles.brandScript}>Eustace</Text>
              <Text style={styles.brandText}>BATCHOY</Text>
            </View>
            <View style={styles.unliBadge}>
              <Text style={styles.unliText}>UNLI SABAW</Text>
            </View>
          </View>
        </TouchableOpacity>

        <View style={styles.topBarRight}>
          <TouchableOpacity
            style={styles.iconActionBtn}
            onPress={openNotificationsModal}
            accessibilityLabel="Open Stock Notifications"
          >
            <Ionicons name="notifications-outline" size={19} color={COLORS.text} />
            {notifData.total_count > 0 && (
              <View style={styles.bellBadge}>
                <Text style={styles.bellBadgeText}>
                  {notifData.total_count > 99 ? '99+' : notifData.total_count}
                </Text>
              </View>
            )}
          </TouchableOpacity>

          <TouchableOpacity
            style={styles.iconActionBtn}
            onPress={() => setDrawerVisible(true)}
            accessibilityLabel="Open Navigation Menu"
          >
            <Ionicons name="menu" size={22} color={COLORS.text} />
          </TouchableOpacity>
        </View>
      </View>

      <View style={styles.body}>
        {visitedTabs.dashboard && (
          <View style={[styles.screenPane, activeTab !== 'dashboard' && styles.hiddenPane]}>
            <DashboardScreen
              onNavigateTab={handleSelectTab}
              onQuickTransaction={handleQuickTransaction}
              showWelcomeCard={guide.showWelcomeCard}
              onOpenGuide={guide.openGuide}
              onDismissGuide={guide.dismissForever}
            />
          </View>
        )}
        {visitedTabs.inventory && (
          <View style={[styles.screenPane, activeTab !== 'inventory' && styles.hiddenPane]}>
            <InventoryScreen />
          </View>
        )}
        {visitedTabs.transactions && (
          <View style={[styles.screenPane, activeTab !== 'transactions' && styles.hiddenPane]}>
            <TransactionsScreen launchRequest={txLaunch} />
          </View>
        )}
        {isOwner && visitedTabs.purchases && (
          <View style={[styles.screenPane, activeTab !== 'purchases' && styles.hiddenPane]}>
            <PurchasesSuppliersScreen launchRequest={purchasesLaunch} />
          </View>
        )}
        {isOwner && visitedTabs.reports && (
          <View style={[styles.screenPane, activeTab !== 'reports' && styles.hiddenPane]}>
            <ReportsScreen />
          </View>
        )}
        {isOwner && visitedTabs.staff && (
          <View style={[styles.screenPane, activeTab !== 'staff' && styles.hiddenPane]}>
            <StaffAuditScreen launchRequest={staffLaunch} />
          </View>
        )}
        {visitedTabs.settings && (
          <View style={[styles.screenPane, activeTab !== 'settings' && styles.hiddenPane]}>
            <SettingsProfileScreen launchRequest={settingsLaunch} />
          </View>
        )}
      </View>

      {/* Bottom 4-Tab Bar Matching Reference Phone Layout */}
      <View style={styles.bottomNav}>
        {bottomTabs.map((item) => {
          const active = activeTab === item.key;
          return (
            <TouchableOpacity
              key={item.key}
              style={styles.navItem}
              onPress={() => handleSelectTab(item.key, item.settingsSubTab)}
            >
              {active && <View style={styles.navActiveBar} />}
              <Ionicons
                name={active ? item.activeIcon : item.icon}
                size={21}
                color={active ? COLORS.primary : '#9E9E9E'}
              />
              <Text
                style={[
                  styles.navLabel,
                  { color: active ? COLORS.primary : '#9E9E9E', fontWeight: active ? '700' : '600' },
                ]}
                numberOfLines={1}
              >
                {item.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>

      <MobileDrawerMenu
        visible={drawerVisible}
        activeKey={activeDrawerKey}
        user={user}
        businessName={business?.business_name || 'MyBatchoy'}
        onClose={() => setDrawerVisible(false)}
        onNavigate={handleDrawerNavigate}
        onOpenHelp={guide.openGuide}
        onLogout={logout}
      />

      <FirstTimeGuideModal
        visible={guide.guideVisible}
        isOwner={isOwner}
        showDismissForever={guide.showWelcomeCard}
        onClose={guide.closeGuide}
        onDismissForever={guide.dismissForever}
      />

      <NotificationsModal
        visible={notifVisible}
        notifications={notifData.notifications}
        counts={notifData.counts}
        totalCount={notifData.total_count}
        loading={notifLoading}
        error={notifError}
        onClose={() => setNotifVisible(false)}
        onRefresh={() => loadNotifications(true)}
        onSelectNotification={handleSelectNotification}
        onViewInventoryAlerts={() => {
          setNotifVisible(false);
          handleSelectTab('inventory');
        }}
        onViewSpoilageHistory={() => {
          setNotifVisible(false);
          handleQuickTransaction('history', undefined, 'spoilage');
        }}
      />
    </SafeAreaView>
  );
};

export default function App() {
  return (
    <AuthProvider>
      <StatusBar barStyle="light-content" />
      <MainShell />
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  centered: {
    flex: 1,
    backgroundColor: COLORS.background,
    alignItems: 'center',
    justifyContent: 'center',
  },
  loadingText: {
    color: COLORS.textSecondary,
    marginTop: 10,
    fontSize: 13,
  },
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: COLORS.navBackground,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#242424',
  },
  brandLockup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 9,
  },
  mascotWrap: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 2,
    borderColor: COLORS.secondary,
    overflow: 'hidden',
    backgroundColor: COLORS.card,
  },
  mascotImg: {
    width: '100%',
    height: '100%',
  },
  brandScript: {
    color: COLORS.text,
    fontSize: 14,
    fontStyle: 'italic',
    fontWeight: '700',
  },
  brandText: {
    color: COLORS.secondary,
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
  unliBadge: {
    alignSelf: 'flex-start',
    backgroundColor: COLORS.primary,
    borderRadius: 4,
    paddingHorizontal: 5,
    paddingVertical: 1,
    marginTop: 2,
  },
  unliText: {
    color: '#FFFFFF',
    fontSize: 8,
    fontWeight: '800',
    letterSpacing: 0.8,
  },
  topBarRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  iconActionBtn: {
    position: 'relative',
    width: 38,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#171717',
    borderWidth: 1,
    borderColor: '#2E2E2E',
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    minWidth: 17,
    height: 17,
    paddingHorizontal: 4,
    borderRadius: 999,
    backgroundColor: COLORS.primary,
    borderWidth: 1.5,
    borderColor: COLORS.navBackground,
    alignItems: 'center',
    justifyContent: 'center',
  },
  bellBadgeText: {
    color: '#FFFFFF',
    fontSize: 9,
    fontWeight: '800',
  },
  body: {
    flex: 1,
  },
  screenPane: {
    flex: 1,
  },
  hiddenPane: {
    display: 'none',
  },
  bottomNav: {
    flexDirection: 'row',
    backgroundColor: COLORS.navBackground,
    borderTopWidth: 1,
    borderTopColor: '#262626',
    paddingVertical: 6,
    minHeight: 58,
  },
  navItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 5,
    position: 'relative',
  },
  navActiveBar: {
    position: 'absolute',
    top: -6,
    width: '44%',
    height: 2.5,
    borderBottomLeftRadius: 3,
    borderBottomRightRadius: 3,
    backgroundColor: COLORS.primary,
  },
  navLabel: {
    fontSize: 10,
    marginTop: 3,
  },
});
