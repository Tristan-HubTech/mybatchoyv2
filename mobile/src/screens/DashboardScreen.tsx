import React, { useCallback, useEffect, useState } from 'react';
import {
  Image,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../services/api';
import { COLORS } from '../theme/colors';
import { InventoryItemModel, StockTransactionModel } from '../types/models';
import {
  BannerMessage,
  EmptyState,
  FilterChips,
  MetricCard,
  SkeletonList,
  StatusBadge,
} from '../components/UI';
import { FirstTimeWelcomeBanner } from '../components/FirstTimeGuide';

const RANGE_OPTIONS = [
  { key: 'today', label: 'Today' },
  { key: '7days', label: 'Last 7 Days' },
  { key: '30days', label: 'Last 30 Days' },
  { key: 'this_month', label: 'This Month' },
];

type QuickTxMode = 'history' | 'stock_in' | 'stock_out' | 'adjustment' | 'spoilage';

const txLabel = (type: string) => {
  switch (type) {
    case 'stock_in':
      return 'Stock In';
    case 'stock_out':
      return 'Stock Out';
    case 'adjustment':
      return 'Adjustment';
    case 'spoilage':
      return 'Spoilage';
    default:
      return type.replace('_', ' ');
  }
};

function formatTxDate(iso?: string | null): string {
  if (!iso) return '';
  const dt = new Date(iso);
  if (Number.isNaN(dt.getTime())) return '';
  const month = dt.toLocaleString('en-US', { month: 'short' });
  const day = dt.getDate();
  const hh = String(dt.getHours()).padStart(2, '0');
  const mm = String(dt.getMinutes()).padStart(2, '0');
  return `${month} ${day}, ${hh}:${mm}`;
}

export const DashboardScreen: React.FC<{
  onNavigateTab: (tab: string) => void;
  onQuickTransaction?: (mode: QuickTxMode, itemId?: number) => void;
  showWelcomeCard?: boolean;
  onOpenGuide?: () => void;
  onDismissGuide?: () => void;
}> = ({
  onNavigateTab,
  onQuickTransaction,
  showWelcomeCard,
  onOpenGuide,
  onDismissGuide,
}) => {
  const { user } = useAuth();
  const [range, setRange] = useState<string>('7days');
  const [loading, setLoading] = useState<boolean>(true);
  const [refreshing, setRefreshing] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  const [data, setData] = useState<any>(null);

  const loadDashboard = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        const res = await apiRequest('/dashboard', { params: { range } });
        setData(res);
      } catch (err: any) {
        setError(err?.message || 'Failed to load dashboard.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [range]
  );

  useEffect(() => {
    loadDashboard();
  }, [loadDashboard]);

  const isOwner = user?.role === 'owner';
  const summary = data?.summary || {};
  const urgentItems: InventoryItemModel[] = data?.urgent_items || [];
  const recentTransactions: StockTransactionModel[] = data?.recent_transactions || [];
  const recentPurchases: any[] = data?.recent_purchases || [];
  const movementChart = data?.charts?.stock_movements;

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={() => loadDashboard(true)}
          tintColor={COLORS.primary}
        />
      }
    >
      {/* 1. Hero Welcome Banner Matching Target Phone Reference */}
      <View style={styles.heroBanner}>
        <Image
          source={require('../../assets/eustace-bowl-hero.jpg')}
          style={styles.heroBowlImage}
          resizeMode="cover"
        />
        <View style={styles.heroRedSlash} />
        <View style={styles.heroYellowSlash} />
        <View style={styles.heroFadeLeft} />
        <View style={styles.heroFadeMid} />

        <View style={styles.heroTextCol}>
          <Text style={styles.greeting}>Welcome back,</Text>
          <Text style={styles.greetingName} numberOfLines={1}>
            {user?.name || 'Team Member'}!
          </Text>
          <Text style={styles.heroSubtitle}>Here&apos;s what&apos;s happening today.</Text>
        </View>
      </View>

      <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />

      {loading && !data ? (
        <SkeletonList count={5} height={92} />
      ) : (
        <>
          {/* 2. Centered 2x2 KPI Grid Matching Reference Order & Shape */}
          <View style={styles.metricsGrid}>
            <MetricCard
              label="Items"
              value={summary.total_items ?? 0}
              icon="cube"
              accentColor={COLORS.primary}
              onPress={() => onNavigateTab('inventory')}
            />
            {isOwner ? (
              <MetricCard
                label="Purchases"
                value={summary.purchases_count ?? 0}
                icon="cart"
                accentColor={COLORS.secondary}
                onPress={() => onNavigateTab('purchases')}
              />
            ) : (
              <MetricCard
                label="In Stock"
                value={summary.in_stock_count ?? 0}
                icon="checkmark-circle"
                accentColor={COLORS.success}
                onPress={() => onNavigateTab('inventory')}
              />
            )}
            <MetricCard
              label="Movements"
              value={summary.period_movements_count ?? 0}
              icon="swap-horizontal"
              accentColor={COLORS.success}
              onPress={() =>
                onQuickTransaction ? onQuickTransaction('history') : onNavigateTab('transactions')
              }
            />
            <MetricCard
              label="Low Stock"
              value={(summary.low_stock_count ?? 0) + (summary.out_of_stock_count ?? 0)}
              icon="warning"
              accentColor={COLORS.danger}
              subtitle={
                (summary.out_of_stock_count ?? 0) > 0
                  ? `${summary.out_of_stock_count} out of stock`
                  : undefined
              }
              onPress={() => onNavigateTab('inventory')}
            />
          </View>

          {/* 3. Recent Transactions Card Matching Phone Reference */}
          <View style={styles.txCardContainer}>
            <View style={styles.txCardHeader}>
              <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                <View style={styles.sectionYellowBar} />
                <Text style={styles.sectionHeading}>Recent Transactions</Text>
              </View>
              <TouchableOpacity onPress={() => onNavigateTab('transactions')}>
                <Text style={styles.viewAllLink}>View All →</Text>
              </TouchableOpacity>
            </View>

            {recentTransactions.length === 0 ? (
              <EmptyState
                icon="swap-horizontal-outline"
                title="No recent stock activity"
                subtitle="When stock is added, removed, or counted, it appears here."
              />
            ) : (
              recentTransactions.slice(0, 5).map((tx, idx) => {
                const isStockIn = tx.transaction_type === 'stock_in';
                const isAdjust = tx.transaction_type === 'adjustment';
                const accent = isStockIn
                  ? COLORS.success
                  : isAdjust
                  ? COLORS.secondary
                  : COLORS.danger;
                const iconName = isAdjust ? 'create' : 'swap-horizontal';
                const iconFg = isAdjust ? '#111111' : '#FFFFFF';
                const sign =
                  isStockIn || (isAdjust && Number(tx.updated_quantity) >= Number(tx.previous_quantity))
                    ? '+'
                    : '-';
                const timeText = formatTxDate(tx.transaction_date);

                return (
                  <View
                    key={tx.id}
                    style={[
                      styles.txRow,
                      idx === Math.min(recentTransactions.length, 5) - 1 && { borderBottomWidth: 0 },
                    ]}
                  >
                    <View style={[styles.txIconCircle, { backgroundColor: accent }]}>
                      <Ionicons name={iconName} size={16} color={iconFg} />
                    </View>
                    <View style={{ flex: 1, marginRight: 8 }}>
                      <Text style={[styles.txRowType, { color: accent }]}>
                        {txLabel(tx.transaction_type)}
                      </Text>
                      <Text style={styles.itemName} numberOfLines={1}>
                        {tx.item_name}
                      </Text>
                    </View>
                    <View style={{ alignItems: 'flex-end' }}>
                      <Text style={[styles.txRowQty, { color: accent }]}>
                        {sign}
                        {tx.quantity} {tx.unit_short_name}
                      </Text>
                      <Text style={styles.txRowMeta}>
                        {timeText || `By ${tx.user_name}`}
                      </Text>
                    </View>
                  </View>
                );
              })
            )}
          </View>

          {/* 4. Quick Stock Actions */}
          <View style={styles.quickGridCard}>
            <Text style={styles.quickGridTitle}>Quick Stock Actions</Text>
            <View style={styles.quickGrid}>
              <TouchableOpacity
                style={[styles.quickActionTile, { borderColor: COLORS.success }]}
                onPress={() =>
                  onQuickTransaction ? onQuickTransaction('stock_in') : onNavigateTab('transactions')
                }
              >
                <Ionicons name="add-circle-outline" size={20} color={COLORS.success} />
                <Text style={styles.quickTileLabel}>+ Add Stock</Text>
                <Text style={styles.quickTileHint}>New delivery</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.quickActionTile, { borderColor: COLORS.primary }]}
                onPress={() =>
                  onQuickTransaction ? onQuickTransaction('stock_out') : onNavigateTab('transactions')
                }
              >
                <Ionicons name="remove-circle-outline" size={20} color={COLORS.primary} />
                <Text style={styles.quickTileLabel}>- Remove Stock</Text>
                <Text style={styles.quickTileHint}>Kitchen usage</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.quickActionTile, { borderColor: COLORS.secondary }]}
                onPress={() =>
                  onQuickTransaction ? onQuickTransaction('adjustment') : onNavigateTab('inventory')
                }
              >
                <Ionicons name="create-outline" size={20} color={COLORS.secondary} />
                <Text style={styles.quickTileLabel}>Correct Stock</Text>
                <Text style={styles.quickTileHint}>After counting</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.quickActionTile, { borderColor: COLORS.danger }]}
                onPress={() =>
                  onQuickTransaction ? onQuickTransaction('spoilage') : onNavigateTab('transactions')
                }
              >
                <Ionicons name="warning-outline" size={20} color={COLORS.danger} />
                <Text style={styles.quickTileLabel}>Spoilage</Text>
                <Text style={styles.quickTileHint}>Expired or waste</Text>
              </TouchableOpacity>
            </View>
          </View>

          <FilterChips options={RANGE_OPTIONS} selected={range} onSelect={setRange} />

          {/* 5. Attention Callout */}
          <View
            style={[
              styles.attentionCard,
              {
                borderColor:
                  (summary.out_of_stock_count ?? 0) > 0 || (summary.low_stock_count ?? 0) > 0
                    ? COLORS.warning
                    : COLORS.success,
              },
            ]}
          >
            <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
              <Ionicons
                name={
                  (summary.out_of_stock_count ?? 0) > 0 || (summary.low_stock_count ?? 0) > 0
                    ? 'alert-circle-outline'
                    : 'checkmark-circle-outline'
                }
                size={20}
                color={
                  (summary.out_of_stock_count ?? 0) > 0 || (summary.low_stock_count ?? 0) > 0
                    ? COLORS.warning
                    : COLORS.success
                }
                style={{ marginRight: 8 }}
              />
              <Text style={styles.attentionTitle}>What Needs Attention Today?</Text>
            </View>
            {(summary.out_of_stock_count ?? 0) > 0 || (summary.low_stock_count ?? 0) > 0 ? (
              <Text style={styles.attentionText}>
                {summary.out_of_stock_count ?? 0} item(s) are out of stock and{' '}
                {summary.low_stock_count ?? 0} item(s) are running low. Tap &quot;Add Stock&quot; on
                any item below when deliveries arrive.
              </Text>
            ) : (
              <Text style={styles.attentionText}>
                All items have enough stock today. Nothing needs urgent restocking right now.
              </Text>
            )}
          </View>

          {movementChart && movementChart.labels?.length > 0 && (
            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>
                {isOwner ? 'Daily Stock Added vs Removed' : 'My Daily Stock Activity'}
              </Text>
              {movementChart.labels.slice(-7).map((label: string, idx: number) => {
                const inVal = Number(movementChart.stock_in?.[idx] ?? 0);
                const outVal = Number(movementChart.stock_out?.[idx] ?? 0);
                return (
                  <View key={`${label}-${idx}`} style={styles.chartRow}>
                    <Text style={styles.chartLabel}>{label}</Text>
                    <View style={styles.chartValues}>
                      <Text style={[styles.chartChip, { color: COLORS.success }]}>
                        +{inVal} Added
                      </Text>
                      <Text style={[styles.chartChip, { color: COLORS.danger }]}>
                        -{outVal} Removed
                      </Text>
                    </View>
                  </View>
                );
              })}
            </View>
          )}

          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionHeading}>Items Running Low or Out</Text>
            <TouchableOpacity onPress={() => onNavigateTab('inventory')}>
              <Text style={styles.viewAllLink}>View All →</Text>
            </TouchableOpacity>
          </View>

          {urgentItems.length === 0 ? (
            <EmptyState
              icon="checkmark-done-circle-outline"
              title="All items are well stocked"
              subtitle="No low-stock or out-of-stock warnings right now."
            />
          ) : (
            urgentItems.map((item) => (
              <View key={item.id} style={styles.itemCard}>
                <View style={styles.itemCardTop}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.itemName}>{item.name}</Text>
                    <Text style={styles.itemMeta}>
                      Code: {item.sku} • {item.category_name}
                    </Text>
                  </View>
                  <StatusBadge status={item.status} />
                </View>
                <View style={styles.itemCardBottom}>
                  <View>
                    <Text style={styles.qtyText}>
                      On hand:{' '}
                      <Text style={{ color: COLORS.text, fontWeight: '700' }}>
                        {item.current_quantity} {item.unit_short_name}
                      </Text>
                    </Text>
                    <Text style={styles.qtyText}>
                      Minimum: {item.minimum_stock_level} {item.unit_short_name}
                    </Text>
                  </View>
                  {onQuickTransaction && (
                    <TouchableOpacity
                      style={styles.restockMiniBtn}
                      onPress={() => onQuickTransaction('stock_in', item.id)}
                    >
                      <Ionicons name="add-circle" size={16} color="#111111" />
                      <Text style={styles.restockMiniBtnText}>Add Stock</Text>
                    </TouchableOpacity>
                  )}
                </View>
              </View>
            ))
          )}

          {isOwner && recentPurchases.length > 0 && (
            <>
              <View style={styles.sectionHeaderRow}>
                <Text style={styles.sectionHeading}>Recent Supply Orders</Text>
                <TouchableOpacity onPress={() => onNavigateTab('purchases')}>
                  <Text style={styles.viewAllLink}>View Orders →</Text>
                </TouchableOpacity>
              </View>
              {recentPurchases.map((po) => (
                <View key={po.id} style={styles.itemCard}>
                  <View style={styles.itemCardTop}>
                    <Text style={styles.itemName}>{po.purchase_number}</Text>
                    <Text style={{ color: COLORS.secondary, fontWeight: '700' }}>
                      ₱{Number(po.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}
                    </Text>
                  </View>
                  <Text style={styles.itemMeta}>
                    {po.supplier_name} • {po.items_count} item(s) • {po.purchase_date}
                  </Text>
                </View>
              ))}
            </>
          )}

          {showWelcomeCard && onOpenGuide && onDismissGuide && (
            <View style={{ marginTop: 10 }}>
              <FirstTimeWelcomeBanner onOpenGuide={onOpenGuide} onDismiss={onDismissGuide} />
            </View>
          )}
        </>
      )}
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  content: {
    padding: 14,
    paddingBottom: 28,
  },
  heroBanner: {
    position: 'relative',
    backgroundColor: '#141414',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#2E2E2E',
    overflow: 'hidden',
    minHeight: 118,
    marginBottom: 14,
    justifyContent: 'center',
  },
  heroBowlImage: {
    position: 'absolute',
    right: -14,
    top: 0,
    bottom: 0,
    width: '48%',
    height: '100%',
    opacity: 0.94,
  },
  heroRedSlash: {
    position: 'absolute',
    right: '36%',
    top: -20,
    bottom: -20,
    width: 34,
    backgroundColor: 'rgba(229, 57, 53, 0.34)',
    transform: [{ skewX: '-22deg' }],
  },
  heroYellowSlash: {
    position: 'absolute',
    right: '31%',
    top: -20,
    bottom: -20,
    width: 14,
    backgroundColor: 'rgba(255, 193, 7, 0.3)',
    transform: [{ skewX: '-22deg' }],
  },
  heroFadeLeft: {
    position: 'absolute',
    left: 0,
    top: 0,
    bottom: 0,
    width: '56%',
    backgroundColor: 'rgba(20, 20, 20, 0.92)',
  },
  heroFadeMid: {
    position: 'absolute',
    left: '48%',
    top: 0,
    bottom: 0,
    width: '18%',
    backgroundColor: 'rgba(20, 20, 20, 0.56)',
  },
  heroTextCol: {
    paddingVertical: 16,
    paddingLeft: 16,
    paddingRight: '42%',
    zIndex: 2,
  },
  greeting: {
    color: COLORS.text,
    fontSize: 19,
    fontWeight: '800',
    lineHeight: 23,
  },
  greetingName: {
    color: COLORS.text,
    fontSize: 19,
    fontWeight: '800',
    lineHeight: 24,
  },
  heroSubtitle: {
    color: '#CCCCCC',
    fontSize: 12,
    lineHeight: 16,
    marginTop: 5,
  },
  metricsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  txCardContainer: {
    backgroundColor: COLORS.card,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#2D2D2D',
    paddingHorizontal: 14,
    paddingTop: 12,
    paddingBottom: 4,
    marginBottom: 14,
  },
  txCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#282828',
  },
  sectionYellowBar: {
    width: 3.5,
    height: 16,
    borderRadius: 2,
    backgroundColor: COLORS.secondary,
    marginRight: 8,
  },
  txRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 11,
    borderBottomWidth: 1,
    borderBottomColor: '#282828',
  },
  txIconCircle: {
    width: 34,
    height: 34,
    borderRadius: 17,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 11,
  },
  txRowType: {
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 1,
  },
  txRowQty: {
    fontSize: 13,
    fontWeight: '800',
  },
  txRowMeta: {
    color: COLORS.textSecondary,
    fontSize: 11,
    marginTop: 2,
  },
  quickGridCard: {
    backgroundColor: COLORS.card,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#2D2D2D',
    padding: 12,
    marginBottom: 12,
  },
  quickGridTitle: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 10,
  },
  quickGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 8,
  },
  quickActionTile: {
    width: '48.5%',
    backgroundColor: COLORS.navBackground,
    borderRadius: 10,
    borderWidth: 1,
    padding: 11,
  },
  quickTileLabel: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 6,
  },
  quickTileHint: {
    color: COLORS.textSecondary,
    fontSize: 11,
    marginTop: 2,
  },
  attentionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    padding: 13,
    marginBottom: 12,
  },
  attentionTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  attentionText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 18,
  },
  restockMiniBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.secondary,
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 8,
  },
  restockMiniBtnText: {
    color: '#111111',
    fontSize: 12,
    fontWeight: '700',
    marginLeft: 4,
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#2D2D2D',
    padding: 14,
    marginVertical: 8,
  },
  sectionTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 10,
  },
  chartRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
    borderBottomWidth: 1,
    borderBottomColor: '#282828',
  },
  chartLabel: {
    color: COLORS.textSecondary,
    fontSize: 12,
  },
  chartValues: {
    flexDirection: 'row',
    gap: 12,
  },
  chartChip: {
    fontSize: 12,
    fontWeight: '700',
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 14,
    marginBottom: 8,
  },
  sectionHeading: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '800',
  },
  viewAllLink: {
    color: COLORS.primary,
    fontSize: 12,
    fontWeight: '700',
  },
  itemCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#2D2D2D',
    padding: 13,
    marginBottom: 9,
  },
  itemCardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 4,
  },
  itemName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  itemMeta: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginTop: 2,
  },
  itemCardBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#282828',
  },
  qtyText: {
    color: COLORS.textSecondary,
    fontSize: 12,
  },
});
