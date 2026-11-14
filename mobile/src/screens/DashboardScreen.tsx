import React, { useCallback, useEffect, useState } from 'react';
import {
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

const RANGE_OPTIONS = [
  { key: 'today', label: 'Today' },
  { key: '7days', label: 'Last 7 Days' },
  { key: '30days', label: 'Last 30 Days' },
  { key: 'this_month', label: 'This Month' },
];

export const DashboardScreen: React.FC<{
  onNavigateTab: (tab: string) => void;
}> = ({ onNavigateTab }) => {
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
      <View style={styles.welcomeCard}>
        <View style={{ flex: 1 }}>
          <Text style={styles.greeting}>Welcome back, {user?.name}</Text>
          <Text style={styles.roleBadgeText}>
            {isOwner ? 'Owner / Admin Dashboard' : 'Staff Operations Dashboard'} •{' '}
            {data?.range?.label || 'Last 7 Days'}
          </Text>
        </View>
        <View style={styles.quickBtnRow}>
          <TouchableOpacity
            style={styles.quickActionPill}
            onPress={() => onNavigateTab('transactions')}
          >
            <Ionicons name="swap-vertical" size={16} color={COLORS.text} />
            <Text style={styles.quickActionText}>Stock Ops</Text>
          </TouchableOpacity>
        </View>
      </View>

      <FilterChips options={RANGE_OPTIONS} selected={range} onSelect={setRange} />

      <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />

      {loading && !data ? (
        <SkeletonList count={5} height={92} />
      ) : (
        <>
          <View style={styles.metricsGrid}>
            <MetricCard
              label="Active Items"
              value={summary.total_items ?? 0}
              icon="cube-outline"
              accentColor={COLORS.primary}
              subtitle={`${summary.in_stock_count ?? 0} healthy stock`}
            />
            <MetricCard
              label="Low Stock"
              value={summary.low_stock_count ?? 0}
              icon="warning-outline"
              accentColor={COLORS.warning}
              subtitle="Needs restock"
            />
            <MetricCard
              label="Out of Stock"
              value={summary.out_of_stock_count ?? 0}
              icon="alert-circle-outline"
              accentColor={COLORS.danger}
              subtitle="Depleted items"
            />
            <MetricCard
              label={isOwner ? 'Movements' : 'My Movements'}
              value={summary.period_movements_count ?? 0}
              icon="pulse-outline"
              accentColor={COLORS.success}
              subtitle={data?.range?.label}
            />
            {isOwner && (
              <>
                <MetricCard
                  label="Purchase Spend"
                  value={`₱${Number(summary.purchasing_expenses ?? 0).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                  })}`}
                  icon="cart-outline"
                  accentColor={COLORS.secondary}
                  subtitle={`${summary.purchases_count ?? 0} orders in period`}
                />
                <MetricCard
                  label="Healthy Stock"
                  value={summary.in_stock_count ?? 0}
                  icon="checkmark-circle-outline"
                  accentColor={COLORS.success}
                  subtitle="Above minimum level"
                />
              </>
            )}
          </View>

          {movementChart && movementChart.labels?.length > 0 && (
            <View style={styles.sectionCard}>
              <Text style={styles.sectionTitle}>
                {isOwner ? 'Stock Movements (In vs Out)' : 'My Stock Movements'}
              </Text>
              {movementChart.labels.slice(-7).map((label: string, idx: number) => {
                const inVal = Number(movementChart.stock_in?.[idx] ?? 0);
                const outVal = Number(movementChart.stock_out?.[idx] ?? 0);
                return (
                  <View key={`${label}-${idx}`} style={styles.chartRow}>
                    <Text style={styles.chartLabel}>{label}</Text>
                    <View style={styles.chartValues}>
                      <Text style={[styles.chartChip, { color: COLORS.success }]}>
                        +{inVal} In
                      </Text>
                      <Text style={[styles.chartChip, { color: COLORS.danger }]}>
                        -{outVal} Out
                      </Text>
                    </View>
                  </View>
                );
              })}
            </View>
          )}

          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionHeading}>Urgent Stock Alerts</Text>
            <TouchableOpacity onPress={() => onNavigateTab('inventory')}>
              <Text style={styles.viewAllLink}>View Inventory</Text>
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
                      SKU: {item.sku} • {item.category_name}
                    </Text>
                  </View>
                  <StatusBadge status={item.status} />
                </View>
                <View style={styles.itemCardBottom}>
                  <Text style={styles.qtyText}>
                    Current:{' '}
                    <Text style={{ color: COLORS.text, fontWeight: '700' }}>
                      {item.current_quantity} {item.unit_short_name}
                    </Text>
                  </Text>
                  <Text style={styles.qtyText}>
                    Min: {item.minimum_stock_level} {item.unit_short_name}
                  </Text>
                </View>
              </View>
            ))
          )}

          <View style={styles.sectionHeaderRow}>
            <Text style={styles.sectionHeading}>
              {isOwner ? 'Recent Stock Transactions' : 'My Recent Transactions'}
            </Text>
            <TouchableOpacity onPress={() => onNavigateTab('transactions')}>
              <Text style={styles.viewAllLink}>All History</Text>
            </TouchableOpacity>
          </View>

          {recentTransactions.length === 0 ? (
            <EmptyState
              icon="swap-horizontal-outline"
              title="No recent transactions"
              subtitle="Stock movements recorded will appear here."
            />
          ) : (
            recentTransactions.map((tx) => (
              <View key={tx.id} style={styles.itemCard}>
                <View style={styles.itemCardTop}>
                  <Text style={styles.itemName}>{tx.item_name}</Text>
                  <Text
                    style={[
                      styles.txTypeTag,
                      {
                        color:
                          tx.transaction_type === 'stock_in'
                            ? COLORS.success
                            : tx.transaction_type === 'adjustment'
                            ? COLORS.secondary
                            : COLORS.danger,
                      },
                    ]}
                  >
                    {tx.transaction_type.replace('_', ' ').toUpperCase()} ({tx.quantity}{' '}
                    {tx.unit_short_name})
                  </Text>
                </View>
                <Text style={styles.itemMeta}>
                  {tx.previous_quantity} → {tx.updated_quantity} {tx.unit_short_name} • By{' '}
                  {tx.user_name}
                </Text>
                {tx.reason ? <Text style={styles.reasonText}>Note: {tx.reason}</Text> : null}
              </View>
            ))
          )}

          {isOwner && recentPurchases.length > 0 && (
            <>
              <View style={styles.sectionHeaderRow}>
                <Text style={styles.sectionHeading}>Recent Purchases</Text>
                <TouchableOpacity onPress={() => onNavigateTab('purchases')}>
                  <Text style={styles.viewAllLink}>View Purchases</Text>
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
    padding: 16,
    paddingBottom: 32,
  },
  welcomeCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 12,
  },
  greeting: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '700',
  },
  roleBadgeText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginTop: 2,
  },
  quickBtnRow: {
    marginLeft: 10,
  },
  quickActionPill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.primary,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  quickActionText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
    marginLeft: 4,
  },
  metricsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginTop: 4,
  },
  sectionCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
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
    borderBottomColor: COLORS.border,
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
    fontWeight: '700',
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
    borderColor: COLORS.border,
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
    borderTopColor: COLORS.border,
  },
  qtyText: {
    color: COLORS.textSecondary,
    fontSize: 12,
  },
  txTypeTag: {
    fontSize: 11,
    fontWeight: '700',
  },
  reasonText: {
    color: COLORS.textMuted,
    fontSize: 11,
    marginTop: 4,
  },
});

