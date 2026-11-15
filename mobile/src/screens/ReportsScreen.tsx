import React, { useCallback, useEffect, useState } from 'react';
import {
  Platform,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { apiRequest, getApiBaseUrl, getStoredToken } from '../services/api';
import { COLORS } from '../theme/colors';
import {
  BannerMessage,
  EmptyState,
  FilterChips,
  FormField,
  MetricCard,
  PrimaryButton,
  SkeletonList,
  StatusBadge,
} from '../components/UI';

const REPORT_TABS = [
  { key: 'inventory', label: 'Stock Summary' },
  { key: 'low_stock', label: 'Low / Out of Stock' },
  { key: 'movements', label: 'Stock Activity' },
  { key: 'purchases', label: 'Supply Orders' },
];

export const ReportsScreen: React.FC = () => {
  const [tab, setTab] = useState('inventory');
  const [fromDate, setFromDate] = useState('');
  const [toDate, setToDate] = useState('');
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [exporting, setExporting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const loadReports = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        const res = await apiRequest('/reports', {
          params: {
            from: fromDate.trim() || undefined,
            to: toDate.trim() || undefined,
          },
        });
        setData(res);
      } catch (err: any) {
        setError(err?.message || 'Failed to load reports.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [fromDate, toDate]
  );

  useEffect(() => {
    loadReports();
  }, [loadReports]);

  const handleExportCsv = async () => {
    setExporting(true);
    setError(null);
    setSuccess(null);
    try {
      const baseUrl = await getApiBaseUrl();
      const token = await getStoredToken();
      const url = `${baseUrl}/reports/export`;

      if (Platform.OS === 'web') {
        setSuccess('CSV export generated via /api/v1/reports/export.');
        return;
      }

      const fileUri = `${FileSystem.documentDirectory}mybatchoy_inventory_report.csv`;
      const downloadRes = await FileSystem.downloadAsync(url, fileUri, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });

      if (downloadRes.status !== 200) {
        throw new Error('Server returned status ' + downloadRes.status);
      }

      const canShare = await Sharing.isAvailableAsync();
      if (canShare) {
        await Sharing.shareAsync(downloadRes.uri, {
          mimeType: 'text/csv',
          dialogTitle: 'Export MyBatchoy Inventory CSV Report',
        });
      }
      setSuccess('CSV report exported successfully.');
    } catch (err: any) {
      setError(err?.message || 'Unable to export CSV report.');
    } finally {
      setExporting(false);
    }
  };

  const summary = data?.summary || {};

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={() => loadReports(true)}
          tintColor={COLORS.primary}
        />
      }
    >
      <View style={styles.topRow}>
        <View style={{ flex: 1, paddingRight: 8 }}>
          <Text style={styles.heading}>Stock &amp; Purchase Summary</Text>
          <Text style={[styles.cardSub, { marginTop: 2 }]}>
            Stock value, low items, activity, and supply costs. (Sales are not tracked here.)
          </Text>
        </View>
        <PrimaryButton
          title="Save CSV"
          icon="download-outline"
          variant="secondary"
          onPress={handleExportCsv}
          loading={exporting}
          style={{ minHeight: 38, paddingVertical: 6 }}
        />
      </View>

      <View style={styles.dateFilterRow}>
        <View style={{ flex: 1, marginRight: 8 }}>
          <FormField
            label="From Date (Optional)"
            value={fromDate}
            onChangeText={setFromDate}
            placeholder="YYYY-MM-DD"
          />
        </View>
        <View style={{ flex: 1 }}>
          <FormField
            label="To Date (Optional)"
            value={toDate}
            onChangeText={setToDate}
            placeholder="YYYY-MM-DD"
          />
        </View>
      </View>

      <FilterChips options={REPORT_TABS} selected={tab} onSelect={setTab} />

      <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
      <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

      {loading && !data ? (
        <SkeletonList count={4} height={90} />
      ) : (
        <>
          <View style={styles.metricsGrid}>
            <MetricCard
              label="Est. Stock Value"
              value={`₱${Number(summary.inventory_value ?? 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
              })}`}
              icon="cash-outline"
              accentColor={COLORS.secondary}
              subtitle="Based on unit costs"
            />
            <MetricCard
              label="Supply Spend"
              value={`₱${Number(summary.total_purchases ?? 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
              })}`}
              icon="cart-outline"
              accentColor={COLORS.primary}
              subtitle="Supply orders cost"
            />
            <MetricCard
              label="Added / Removed"
              value={`+${summary.total_stock_in_qty ?? 0} / -${summary.total_usage_qty ?? 0}`}
              icon="swap-vertical-outline"
              accentColor={COLORS.success}
              subtitle="Stock in vs used"
            />
            <MetricCard
              label="Spoilage Qty"
              value={summary.total_spoilage_qty ?? 0}
              icon="trash-outline"
              accentColor={COLORS.danger}
              subtitle="Expired or damaged"
            />
          </View>

          {tab === 'inventory' && (
            <>
              <Text style={styles.subHeading}>Category Breakdown</Text>
              {(data?.categories || []).map((c: any, idx: number) => (
                <View key={idx} style={styles.card}>
                  <View style={styles.rowBetween}>
                    <Text style={styles.cardTitle}>{c.name}</Text>
                    <Text style={styles.cardSub}>{c.items_count} item(s)</Text>
                  </View>
                  <Text style={styles.cardSub}>Total Quantity: {c.total_quantity}</Text>
                </View>
              ))}
            </>
          )}

          {tab === 'low_stock' && (
            <>
              <Text style={styles.subHeading}>
                Low Stock ({data?.low_stock_items?.length ?? 0}) & Out of Stock (
                {data?.out_of_stock_items?.length ?? 0})
              </Text>
              {[...(data?.out_of_stock_items || []), ...(data?.low_stock_items || [])].length ===
              0 ? (
                <EmptyState
                  icon="checkmark-circle-outline"
                  title="No low or depleted items"
                />
              ) : (
                [...(data?.out_of_stock_items || []), ...(data?.low_stock_items || [])].map(
                  (item: any) => (
                    <View key={item.id} style={styles.card}>
                      <View style={styles.rowBetween}>
                        <Text style={styles.cardTitle}>{item.name}</Text>
                        <StatusBadge status={item.status} />
                      </View>
                      <Text style={styles.cardSub}>
                        SKU: {item.sku} • Current: {item.current_quantity}{' '}
                        {item.unit_short_name} (Min: {item.minimum_stock_level})
                      </Text>
                    </View>
                  )
                )
              )}
            </>
          )}

          {tab === 'movements' && (
            <>
              <Text style={styles.subHeading}>Recent Stock Movements</Text>
              {(data?.recent_transactions || []).map((tx: any) => (
                <View key={tx.id} style={styles.card}>
                  <View style={styles.rowBetween}>
                    <Text style={styles.cardTitle}>{tx.item_name}</Text>
                    <Text style={{ color: COLORS.secondary, fontWeight: '700', fontSize: 12 }}>
                      {tx.transaction_type.toUpperCase()} ({tx.quantity} {tx.unit_short_name})
                    </Text>
                  </View>
                  <Text style={styles.cardSub}>
                    {tx.previous_quantity} → {tx.updated_quantity} • By {tx.user_name}
                  </Text>
                </View>
              ))}
            </>
          )}

          {tab === 'purchases' && (
            <>
              <Text style={styles.subHeading}>Recent Purchase Orders</Text>
              {(data?.recent_purchases || []).map((po: any) => (
                <View key={po.id} style={styles.card}>
                  <View style={styles.rowBetween}>
                    <Text style={styles.cardTitle}>{po.purchase_number}</Text>
                    <Text style={{ color: COLORS.secondary, fontWeight: '700' }}>
                      ₱{Number(po.total_amount).toFixed(2)}
                    </Text>
                  </View>
                  <Text style={styles.cardSub}>
                    {po.supplier_name} • {po.purchase_date}
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
  container: { flex: 1, backgroundColor: COLORS.background },
  content: { padding: 16, paddingBottom: 32 },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  heading: { color: COLORS.text, fontSize: 18, fontWeight: '700' },
  subHeading: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
    marginVertical: 10,
  },
  dateFilterRow: {
    flexDirection: 'row',
  },
  metricsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
  },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 13,
    marginBottom: 9,
  },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  cardTitle: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  cardSub: { color: COLORS.textSecondary, fontSize: 12, marginTop: 3 },
});

