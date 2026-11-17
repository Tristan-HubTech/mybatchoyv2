import React, { useState } from 'react';
import {
  Image,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme/colors';
import { UserProfile } from '../types/models';
import { ConfirmModal } from './UI';

export interface DrawerAction {
  key: string;
  tab: string;
  subTab?: string;
}

interface DrawerItem {
  key: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  iconColor?: string;
  tab: string;
  subTab?: string;
}

interface DrawerGroup {
  title: string;
  items: DrawerItem[];
}

export const MobileDrawerMenu: React.FC<{
  visible: boolean;
  activeKey: string;
  user: UserProfile;
  businessName: string;
  onClose: () => void;
  onNavigate: (action: DrawerAction) => void;
  onOpenHelp: () => void;
  onLogout: () => Promise<void> | void;
}> = React.memo(
  ({ visible, activeKey, user, businessName, onClose, onNavigate, onOpenHelp, onLogout }) => {
    const isOwner = user.role === 'owner';
    const isOpsRole = user.role === 'owner' || user.role === 'staff';
    const [logoutConfirm, setLogoutConfirm] = useState(false);

    const groups: DrawerGroup[] = [];

    if (isOpsRole) {
      groups.push(
        {
          title: 'OVERVIEW',
          items: [{ key: 'dashboard', label: 'Dashboard', icon: 'home-outline', tab: 'dashboard' }],
        },
        {
          title: 'INVENTORY',
          items: [
            { key: 'inventory', label: 'Inventory', icon: 'cube-outline', iconColor: COLORS.secondary, tab: 'inventory' },
            { key: 'transactions:history', label: 'Stock Transactions', icon: 'swap-horizontal-outline', iconColor: COLORS.success, tab: 'transactions', subTab: 'history' },
            { key: 'transactions:stock_in', label: '+ Add Stock', icon: 'add-circle-outline', iconColor: COLORS.success, tab: 'transactions', subTab: 'stock_in' },
            { key: 'transactions:stock_out', label: '- Remove Stock', icon: 'remove-circle-outline', iconColor: COLORS.primary, tab: 'transactions', subTab: 'stock_out' },
            { key: 'transactions:adjustment', label: 'Correct Stock', icon: 'create-outline', iconColor: COLORS.secondary, tab: 'transactions', subTab: 'adjustment' },
            { key: 'transactions:spoilage', label: 'Record Spoilage', icon: 'warning-outline', iconColor: COLORS.danger, tab: 'transactions', subTab: 'spoilage' },
          ],
        }
      );
    }

    if (isOwner) {
      groups.push(
        {
          title: 'PURCHASING',
          items: [
            { key: 'purchases:suppliers', label: 'Suppliers', icon: 'people-outline', tab: 'purchases', subTab: 'suppliers' },
            { key: 'purchases:purchases', label: 'Purchases', icon: 'cart-outline', tab: 'purchases', subTab: 'purchases' },
            { key: 'purchases:new_purchase', label: '+ New Supply Order', icon: 'add-outline', iconColor: COLORS.secondary, tab: 'purchases', subTab: 'new_purchase' },
          ],
        },
        {
          title: 'ANALYTICS',
          items: [
            { key: 'reports', label: 'Reports', icon: 'bar-chart-outline', tab: 'reports' },
          ],
        },
        {
          title: 'ADMINISTRATION',
          items: [
            { key: 'staff:staff', label: 'Staff Management', icon: 'person-add-outline', tab: 'staff', subTab: 'staff' },
            { key: 'staff:audit', label: 'Audit Logs', icon: 'document-text-outline', tab: 'staff', subTab: 'audit' },
          ],
        }
      );
    }

    groups.push({
      title: 'ACCOUNT',
      items: [
        { key: 'settings:profile', label: 'My Profile', icon: 'person-circle-outline', tab: 'settings', subTab: 'profile' },
        ...(isOwner
          ? [{ key: 'settings:business', label: 'Settings', icon: 'settings-outline' as const, tab: 'settings', subTab: 'business' }]
          : []),
      ],
    });

    return (
      <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
        <View style={styles.backdrop}>
          <View style={styles.drawerPanel}>
            <View style={styles.drawerHeader}>
              <View style={styles.brandRow}>
                <Image source={require('../../assets/eustace-mascot.jpg')} style={styles.mascotImg} />
                <View style={{ flex: 1 }}>
                  <Text style={styles.brandScript}>Eustace</Text>
                  <Text style={styles.brandMain}>BATCHOY</Text>
                  <View style={styles.unliPill}>
                    <Text style={styles.unliText}>UNLI SABAW</Text>
                  </View>
                </View>
              </View>
              <TouchableOpacity onPress={onClose} style={styles.closeBtn} accessibilityLabel="Close navigation menu">
                <Ionicons name="close" size={22} color={COLORS.text} />
              </TouchableOpacity>
            </View>

            <View style={styles.userBanner}>
              <View style={styles.avatarCircle}>
                <Text style={styles.avatarInitial}>
                  {(user.name || 'U').charAt(0).toUpperCase()}
                </Text>
              </View>
              <View style={{ flex: 1 }}>
                <Text style={styles.userName} numberOfLines={1}>{user.name}</Text>
                <Text style={styles.userRole}>
                  {isOwner ? 'Owner / Admin' : 'Staff Member'} • {businessName}
                </Text>
              </View>
            </View>

            <ScrollView style={styles.scroll} showsVerticalScrollIndicator={false}>
              {groups.map((group) => (
                <View key={group.title} style={styles.groupBlock}>
                  <Text style={styles.groupTitle}>{group.title}</Text>
                  {group.items.map((item) => {
                    const isActive = activeKey === item.key || activeKey === item.tab;
                    return (
                      <TouchableOpacity
                        key={item.key}
                        style={[styles.navRow, isActive && styles.navRowActive]}
                        onPress={() => {
                          onClose();
                          onNavigate({ key: item.key, tab: item.tab, subTab: item.subTab });
                        }}
                      >
                        <Ionicons
                          name={item.icon}
                          size={19}
                          color={isActive ? '#FFFFFF' : item.iconColor || COLORS.textSecondary}
                          style={{ marginRight: 11 }}
                        />
                        <Text style={[styles.navLabel, isActive && styles.navLabelActive]}>
                          {item.label}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              ))}

              <View style={styles.footerActions}>
                <TouchableOpacity
                  style={styles.navRow}
                  onPress={() => {
                    onClose();
                    onOpenHelp();
                  }}
                >
                  <Ionicons name="help-circle-outline" size={19} color={COLORS.secondary} style={{ marginRight: 11 }} />
                  <Text style={styles.navLabel}>Quick Help Guide</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.navRow, styles.logoutRow]}
                  onPress={() => setLogoutConfirm(true)}
                >
                  <Ionicons name="log-out-outline" size={19} color={COLORS.secondary} style={{ marginRight: 11 }} />
                  <Text style={[styles.navLabel, { color: COLORS.secondary }]}>Logout</Text>
                </TouchableOpacity>
              </View>
            </ScrollView>
          </View>
          <TouchableOpacity style={styles.overlayTouch} activeOpacity={1} onPress={onClose} />
        </View>

        <ConfirmModal
          visible={logoutConfirm}
          title="Sign Out"
          message="Are you sure you want to log out of MyBatchoy Mobile?"
          confirmText="Logout"
          confirmVariant="danger"
          onCancel={() => setLogoutConfirm(false)}
          onConfirm={async () => {
            setLogoutConfirm(false);
            onClose();
            await onLogout();
          }}
        />
      </Modal>
    );
  }
);

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    flexDirection: 'row',
    backgroundColor: 'rgba(0, 0, 0, 0.78)',
  },
  overlayTouch: {
    flex: 1,
  },
  drawerPanel: {
    width: '78%',
    maxWidth: 300,
    backgroundColor: COLORS.navBackground,
    borderRightWidth: 1,
    borderRightColor: '#262626',
    paddingTop: 14,
    paddingHorizontal: 12,
    paddingBottom: 16,
  },
  drawerHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#222222',
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  mascotImg: {
    width: 42,
    height: 42,
    borderRadius: 21,
    borderWidth: 2,
    borderColor: COLORS.secondary,
    marginRight: 10,
  },
  brandScript: {
    color: COLORS.text,
    fontSize: 13,
    fontStyle: 'italic',
    fontWeight: '700',
    lineHeight: 15,
  },
  brandMain: {
    color: COLORS.secondary,
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: 0.5,
    lineHeight: 17,
  },
  unliPill: {
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
  closeBtn: {
    width: 34,
    height: 34,
    borderRadius: 8,
    backgroundColor: '#171717',
    borderWidth: 1,
    borderColor: '#2E2E2E',
    alignItems: 'center',
    justifyContent: 'center',
  },
  userBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#161616',
    borderWidth: 1,
    borderColor: '#2A2A2A',
    borderRadius: 10,
    padding: 10,
    marginTop: 10,
    marginBottom: 8,
  },
  avatarCircle: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: '#242424',
    borderWidth: 1.5,
    borderColor: COLORS.secondary,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 9,
  },
  avatarInitial: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '800',
  },
  userName: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
  },
  userRole: {
    color: COLORS.textSecondary,
    fontSize: 11,
    marginTop: 1,
  },
  scroll: {
    flex: 1,
  },
  groupBlock: {
    marginTop: 8,
  },
  groupTitle: {
    color: '#888888',
    fontSize: 10,
    fontWeight: '700',
    letterSpacing: 1,
    paddingHorizontal: 8,
    marginBottom: 4,
  },
  navRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 10,
    paddingHorizontal: 10,
    borderRadius: 10,
    marginBottom: 2,
  },
  navRowActive: {
    backgroundColor: COLORS.primary,
  },
  navLabel: {
    color: '#E5E5E5',
    fontSize: 14,
    fontWeight: '600',
  },
  navLabelActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },
  footerActions: {
    marginTop: 10,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#222222',
  },
  logoutRow: {
    marginTop: 2,
  },
});

