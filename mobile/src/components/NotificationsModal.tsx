import React from 'react';
import {
  ActivityIndicator,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme/colors';
import { StockNotificationCounts, StockNotificationItem } from '../types/models';
import { PrimaryButton } from './UI';

const SEVERITY_COLORS: Record<StockNotificationItem['severity'], string> = {
  danger: COLORS.danger,
  warning: COLORS.warning,
  info: '#42A5F5',
  spoilage: '#FF7043',
};

const SEVERITY_BG: Record<StockNotificationItem['severity'], string> = {
  danger: 'rgba(229, 57, 53, 0.18)',
  warning: 'rgba(255, 193, 7, 0.16)',
  info: 'rgba(66, 165, 245, 0.16)',
  spoilage: 'rgba(255, 112, 67, 0.18)',
};

export const NotificationsModal: React.FC<{
  visible: boolean;
  notifications: StockNotificationItem[];
  counts: StockNotificationCounts;
  totalCount: number;
  loading: boolean;
  error: string | null;
  onClose: () => void;
  onRefresh: () => void;
  onSelectNotification: (item: StockNotificationItem) => void;
  onViewInventoryAlerts: () => void;
  onViewSpoilageHistory: () => void;
}> = React.memo(
  ({
    visible,
    notifications,
    counts,
    totalCount,
    loading,
    error,
    onClose,
    onRefresh,
    onSelectNotification,
    onViewInventoryAlerts,
    onViewSpoilageHistory,
  }) => {
    return (
      <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.modalTitle}>Notifications</Text>
                <Text style={styles.modalSubtitle}>
                  {totalCount > 0
                    ? `${totalCount} ${totalCount === 1 ? 'alert needs' : 'alerts need'} attention`
                    : 'No active stock issues'}
                </Text>
              </View>
              <TouchableOpacity
                onPress={onRefresh}
                style={styles.iconBtn}
                accessibilityLabel="Refresh notifications"
              >
                <Ionicons name="refresh" size={19} color={COLORS.secondary} />
              </TouchableOpacity>
              <TouchableOpacity
                onPress={onClose}
                style={styles.iconBtn}
                accessibilityLabel="Close notifications"
              >
                <Ionicons name="close-circle" size={26} color={COLORS.textSecondary} />
              </TouchableOpacity>
            </View>

            {totalCount > 0 && (
              <View style={styles.pillsRow}>
                {counts.out_of_stock > 0 && (
                  <View style={[styles.pill, { backgroundColor: SEVERITY_BG.danger, borderColor: SEVERITY_COLORS.danger }]}>
                    <Text style={[styles.pillText, { color: '#FF8A80' }]}>
                      {counts.out_of_stock} Out of Stock
                    </Text>
                  </View>
                )}
                {counts.low_stock > 0 && (
                  <View style={[styles.pill, { backgroundColor: SEVERITY_BG.warning, borderColor: SEVERITY_COLORS.warning }]}>
                    <Text style={[styles.pillText, { color: '#FFD54F' }]}>
                      {counts.low_stock} Low Stock
                    </Text>
                  </View>
                )}
                {counts.reorder > 0 && (
                  <View style={[styles.pill, { backgroundColor: SEVERITY_BG.info, borderColor: SEVERITY_COLORS.info }]}>
                    <Text style={[styles.pillText, { color: '#90CAF9' }]}>
                      {counts.reorder} Reorder
                    </Text>
                  </View>
                )}
                {counts.spoilage > 0 && (
                  <View style={[styles.pill, { backgroundColor: SEVERITY_BG.spoilage, borderColor: SEVERITY_COLORS.spoilage }]}>
                    <Text style={[styles.pillText, { color: '#FFAB91' }]}>
                      {counts.spoilage} Spoilage
                    </Text>
                  </View>
                )}
              </View>
            )}

            {error && (
              <View style={styles.errorBanner}>
                <Text style={styles.errorText}>{error}</Text>
              </View>
            )}

            {loading && notifications.length === 0 ? (
              <View style={styles.centerBox}>
                <ActivityIndicator color={COLORS.secondary} />
                <Text style={styles.emptySub}>Checking stock notifications...</Text>
              </View>
            ) : (
              <ScrollView style={styles.listScroll} showsVerticalScrollIndicator={false}>
                {notifications.length === 0 ? (
                  <View style={styles.centerBox}>
                    <Ionicons name="checkmark-circle-outline" size={34} color={COLORS.success} />
                    <Text style={styles.emptyTitle}>All stock levels look good</Text>
                    <Text style={styles.emptySub}>
                      No out-of-stock, low-stock, reorder warnings, or recent spoilage found.
                    </Text>
                  </View>
                ) : (
                  notifications.map((notif) => {
                    const accent = SEVERITY_COLORS[notif.severity] || COLORS.secondary;
                    const badgeBg = SEVERITY_BG[notif.severity] || COLORS.cardElevated;

                    return (
                      <TouchableOpacity
                        key={notif.id}
                        style={[styles.card, { borderLeftColor: accent }]}
                        onPress={() => onSelectNotification(notif)}
                        activeOpacity={0.85}
                      >
                        <View style={styles.cardTopRow}>
                          <View style={[styles.badge, { backgroundColor: badgeBg }]}>
                            <Text style={[styles.badgeText, { color: accent }]}>{notif.badge}</Text>
                          </View>
                          {notif.time_ago ? (
                            <Text style={styles.timeText}>{notif.time_ago}</Text>
                          ) : null}
                        </View>
                        <Text style={styles.cardTitle}>{notif.title}</Text>
                        <Text style={styles.cardMessage}>{notif.message}</Text>
                        <Text style={styles.cardCta}>
                          {notif.action_type === 'spoilage_history'
                            ? 'View spoilage history →'
                            : 'Tap to add stock →'}
                        </Text>
                      </TouchableOpacity>
                    );
                  })
                )}
              </ScrollView>
            )}

            <View style={styles.footerLinks}>
              <TouchableOpacity style={styles.footerBtn} onPress={onViewInventoryAlerts}>
                <Text style={styles.footerBtnText}>Stock Problems</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.footerBtn} onPress={onViewSpoilageHistory}>
                <Text style={styles.footerBtnText}>Spoilage History</Text>
              </TouchableOpacity>
            </View>

            <PrimaryButton title="Close" variant="secondary" onPress={onClose} />
          </View>
        </View>
      </Modal>
    );
  }
);

const styles = StyleSheet.create({
  modalBackdrop: {
    flex: 1,
    backgroundColor: COLORS.overlay,
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: COLORS.card,
    borderTopLeftRadius: 18,
    borderTopRightRadius: 18,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
    maxHeight: '88%',
  },
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  modalTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
  },
  modalSubtitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginTop: 2,
  },
  iconBtn: {
    padding: 5,
    marginLeft: 4,
  },
  pillsRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 10,
  },
  pill: {
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
    borderWidth: 1,
  },
  pillText: {
    fontSize: 11,
    fontWeight: '700',
  },
  errorBanner: {
    backgroundColor: 'rgba(229, 57, 53, 0.14)',
    borderColor: COLORS.danger,
    borderWidth: 1,
    borderRadius: 8,
    padding: 9,
    marginBottom: 8,
  },
  errorText: {
    color: '#FF8A80',
    fontSize: 12,
  },
  listScroll: {
    marginVertical: 6,
  },
  centerBox: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 28,
    paddingHorizontal: 14,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
    marginTop: 8,
  },
  emptySub: {
    color: COLORS.textSecondary,
    fontSize: 12,
    textAlign: 'center',
    marginTop: 4,
    lineHeight: 18,
  },
  card: {
    backgroundColor: COLORS.navBackground,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderLeftWidth: 4,
    padding: 12,
    marginBottom: 9,
  },
  cardTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 5,
  },
  badge: {
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 6,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '800',
    textTransform: 'uppercase',
  },
  timeText: {
    color: COLORS.textMuted,
    fontSize: 11,
  },
  cardTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  cardMessage: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 17,
    marginTop: 3,
  },
  cardCta: {
    color: COLORS.secondary,
    fontSize: 11,
    fontWeight: '700',
    marginTop: 6,
  },
  footerLinks: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 6,
    marginBottom: 10,
  },
  footerBtn: {
    flex: 1,
    backgroundColor: COLORS.navBackground,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 8,
    paddingVertical: 9,
    alignItems: 'center',
  },
  footerBtnText: {
    color: COLORS.secondary,
    fontSize: 12,
    fontWeight: '700',
  },
});

