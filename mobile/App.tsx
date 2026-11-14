import React, { useCallback, useMemo, useState } from 'react';
import {
  ActivityIndicator,
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
import { TransactionsScreen } from './src/screens/TransactionsScreen';
import { PurchasesSuppliersScreen } from './src/screens/PurchasesSuppliersScreen';
import { ReportsScreen } from './src/screens/ReportsScreen';
import { StaffAuditScreen } from './src/screens/StaffAuditScreen';
import { SettingsProfileScreen } from './src/screens/SettingsProfileScreen';

interface NavItem {
  key: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  ownerOnly?: boolean;
}

const NAV_ITEMS: NavItem[] = [
  { key: 'dashboard', label: 'Dashboard', icon: 'grid-outline' },
  { key: 'inventory', label: 'Inventory', icon: 'cube-outline' },
  { key: 'transactions', label: 'Stock Ops', icon: 'swap-vertical-outline' },
  { key: 'purchases', label: 'Purchases', icon: 'cart-outline', ownerOnly: true },
  { key: 'reports', label: 'Reports', icon: 'bar-chart-outline', ownerOnly: true },
  { key: 'staff', label: 'Staff/Audit', icon: 'people-outline', ownerOnly: true },
  { key: 'settings', label: 'Account', icon: 'settings-outline' },
];

const MainShell: React.FC = () => {
  const { user, business, initializing } = useAuth();
  const [activeTab, setActiveTab] = useState<string>('dashboard');
  const [visitedTabs, setVisitedTabs] = useState<Record<string, boolean>>({
    dashboard: true,
  });

  const handleSelectTab = useCallback((tabKey: string) => {
    setVisitedTabs((prev) => (prev[tabKey] ? prev : { ...prev, [tabKey]: true }));
    setActiveTab(tabKey);
  }, []);

  const isOwner = user?.role === 'owner';
  const visibleTabs = useMemo(
    () => NAV_ITEMS.filter((item) => !item.ownerOnly || isOwner),
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

  return (
    <SafeAreaView style={styles.container}>
      <View style={styles.topBar}>
        <View style={styles.brandRow}>
          <View style={styles.brandDot} />
          <Text style={styles.brandText}>
            {business?.business_name || 'MyBatchoy'}
          </Text>
        </View>
        <View style={styles.rolePill}>
          <Text style={styles.rolePillText}>
            {isOwner ? 'OWNER / ADMIN' : 'STAFF'}
          </Text>
        </View>
      </View>

      <View style={styles.body}>
        {visitedTabs.dashboard && (
          <View style={[styles.screenPane, activeTab !== 'dashboard' && styles.hiddenPane]}>
            <DashboardScreen onNavigateTab={handleSelectTab} />
          </View>
        )}
        {visitedTabs.inventory && (
          <View style={[styles.screenPane, activeTab !== 'inventory' && styles.hiddenPane]}>
            <InventoryScreen />
          </View>
        )}
        {visitedTabs.transactions && (
          <View style={[styles.screenPane, activeTab !== 'transactions' && styles.hiddenPane]}>
            <TransactionsScreen />
          </View>
        )}
        {isOwner && visitedTabs.purchases && (
          <View style={[styles.screenPane, activeTab !== 'purchases' && styles.hiddenPane]}>
            <PurchasesSuppliersScreen />
          </View>
        )}
        {isOwner && visitedTabs.reports && (
          <View style={[styles.screenPane, activeTab !== 'reports' && styles.hiddenPane]}>
            <ReportsScreen />
          </View>
        )}
        {isOwner && visitedTabs.staff && (
          <View style={[styles.screenPane, activeTab !== 'staff' && styles.hiddenPane]}>
            <StaffAuditScreen />
          </View>
        )}
        {visitedTabs.settings && (
          <View style={[styles.screenPane, activeTab !== 'settings' && styles.hiddenPane]}>
            <SettingsProfileScreen />
          </View>
        )}
      </View>

      <View style={styles.bottomNav}>
        {visibleTabs.map((item) => {
          const active = activeTab === item.key;
          return (
            <TouchableOpacity
              key={item.key}
              style={styles.navItem}
              onPress={() => handleSelectTab(item.key)}
            >
              <Ionicons
                name={item.icon}
                size={20}
                color={active ? COLORS.primary : COLORS.textSecondary}
              />
              <Text
                style={[
                  styles.navLabel,
                  { color: active ? COLORS.primary : COLORS.textSecondary },
                ]}
                numberOfLines={1}
              >
                {item.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>
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
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  brandDot: {
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: COLORS.primary,
    marginRight: 8,
  },
  brandText: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '800',
  },
  rolePill: {
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 9,
    paddingVertical: 3,
    borderRadius: 6,
  },
  rolePillText: {
    color: COLORS.secondary,
    fontSize: 10,
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
    borderTopColor: COLORS.border,
    paddingVertical: 6,
  },
  navItem: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 4,
  },
  navLabel: {
    fontSize: 10,
    fontWeight: '600',
    marginTop: 2,
  },
});
