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
import { apiRequest, generateIdempotencyKey } from '../services/api';
import { COLORS } from '../theme/colors';
import { InventoryItemModel, StockTransactionModel, TransactionType } from '../types/models';
import {
  BannerMessage,
  ConfirmModal,
  EmptyState,
  FilterChips,
  FormField,
  PrimaryButton,
  SearchBar,
  SkeletonList,
} from '../components/UI';

export type ScreenMode = 'history' | 'stock_in' | 'stock_out' | 'adjustment' | 'spoilage';

export interface TransactionLaunchRequest {
  mode: ScreenMode;
  itemId?: number;
  nonce: number;
}

const MODE_TABS: { key: ScreenMode; label: string }[] = [
  { key: 'history', label: 'Stock History' },
  { key: 'stock_in', label: '+ Add Stock' },
  { key: 'stock_out', label: '- Remove Stock' },
  { key: 'adjustment', label: 'Correct Stock' },
  { key: 'spoilage', label: 'Spoilage' },
];

const TYPE_FILTERS = [
  { key: 'all', label: 'All Activity' },
  { key: 'stock_in', label: 'Added Stock' },
  { key: 'stock_out', label: 'Removed Stock' },
  { key: 'adjustment', label: 'Corrected Qty' },
  { key: 'spoilage', label: 'Spoilage' },
];

const REASON_PRESETS: Record<Exclude<ScreenMode, 'history'>, string[]> = {
  stock_in: ['Morning delivery', 'Market purchase', 'Supplier restock'],
  stock_out: ['Kitchen cooking prep', 'Used for batchoy broth', 'Transferred to serving'],
  adjustment: ['Daily physical count', 'Scale / count recheck', 'Fix earlier count'],
  spoilage: ['Expired ingredient', 'Spoiled / sour', 'Damaged or spilled'],
};

const formatTxTypeLabel = (t: TransactionType) => {
  switch (t) {
    case 'stock_in':
      return 'ADDED STOCK';
    case 'stock_out':
      return 'REMOVED STOCK';
    case 'adjustment':
      return 'CORRECTED COUNT';
    case 'spoilage':
      return 'SPOILAGE';
    default:
      return String(t).replace('_', ' ').toUpperCase();
  }
};

export const TransactionsScreen: React.FC<{
  launchRequest?: TransactionLaunchRequest | null;
}> = ({ launchRequest }) => {
  const { user } = useAuth();
  const [mode, setMode] = useState<ScreenMode>('history');
  const [transactions, setTransactions] = useState<StockTransactionModel[]>([]);
  const [items, setItems] = useState<InventoryItemModel[]>([]);

  const [typeFilter, setTypeFilter] = useState('all');
  const [search, setSearch] = useState('');
  const [itemFilter, setItemFilter] = useState('');

  const [selectedItemId, setSelectedItemId] = useState<number>(0);
  const [quantity, setQuantity] = useState('');
  const [reason, setReason] = useState('');
  const [idempotencyKey, setIdempotencyKey] = useState(generateIdempotencyKey());
  const [confirmVisible, setConfirmVisible] = useState(false);

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    if (!launchRequest) return;
    setMode(launchRequest.mode);
    if (launchRequest.itemId) {
      setSelectedItemId(launchRequest.itemId);
    }
    setQuantity('');
    setReason('');
    setError(null);
    setSuccess(null);
    setIdempotencyKey(generateIdempotencyKey());
  }, [launchRequest]);

  const loadData = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        const res = await apiRequest<{
          transactions: StockTransactionModel[];
          items: InventoryItemModel[];
        }>('/transactions', {
          params: {
            type: typeFilter !== 'all' ? typeFilter : undefined,
            search: search.trim() || undefined,
          },
        });
        setTransactions(res.transactions);
        setItems(res.items);
        if (!selectedItemId && res.items.length > 0) {
          setSelectedItemId(res.items[0].id);
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to load transactions.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [typeFilter, search, selectedItemId]
  );

  useEffect(() => {
    loadData();
  }, [loadData]);

  const selectedItem = items.find((i) => i.id === selectedItemId) || null;
  const filteredPickerItems = items.filter((item) => {
    const q = itemFilter.trim().toLowerCase();
    if (!q) return true;
    return item.name.toLowerCase().includes(q) || item.sku.toLowerCase().includes(q);
  });

  const parsedNum = quantity.trim() === '' ? Number.NaN : Number(quantity);
  const hasValidNum =
    !Number.isNaN(parsedNum) && (mode === 'adjustment' ? parsedNum >= 0 : parsedNum > 0);
  const exceedsStock =
    (mode === 'stock_out' || mode === 'spoilage') &&
    Boolean(selectedItem) &&
    hasValidNum &&
    parsedNum > (selectedItem?.current_quantity ?? 0);

  const switchMode = (nextMode: ScreenMode) => {
    setMode(nextMode);
    setQuantity('');
    setReason('');
    setError(null);
    setIdempotencyKey(generateIdempotencyKey());
  };

  const validateAndPrompt = () => {
    setError(null);
    setSuccess(null);

    if (!selectedItem) {
      setError('Step 1: Please choose an item first.');
      return;
    }

    const numVal = Number(quantity);
    if (
      quantity.trim() === '' ||
      Number.isNaN(numVal) ||
      (mode !== 'adjustment' && numVal <= 0) ||
      numVal < 0
    ) {
      setError(
        mode === 'adjustment'
          ? 'Enter a valid physical count (0 or higher).'
          : 'Enter a quantity greater than 0.'
      );
      return;
    }

    if ((mode === 'stock_out' || mode === 'spoilage') && numVal > selectedItem.current_quantity) {
      setError(
        `Not enough stock. Currently available: ${selectedItem.current_quantity} ${selectedItem.unit_short_name}.`
      );
      return;
    }

    if ((mode === 'adjustment' || mode === 'spoilage') && !reason.trim()) {
      setError(
        mode === 'spoilage'
          ? 'Please enter a short spoilage reason (example: Expired or damaged).'
          : 'Please explain why you are correcting this stock quantity.'
      );
      return;
    }

    if (mode === 'stock_out' || mode === 'adjustment' || mode === 'spoilage') {
      setConfirmVisible(true);
    } else {
      executeTransaction();
    }
  };

  const executeTransaction = async () => {
    if (!selectedItem) return;
    setConfirmVisible(false);
    setSubmitting(true);
    setError(null);

    const numVal = Number(quantity);
    const endpointMap: Record<Exclude<ScreenMode, 'history'>, string> = {
      stock_in: '/transactions/stock-in',
      stock_out: '/transactions/stock-out',
      adjustment: '/transactions/adjustment',
      spoilage: '/transactions/spoilage',
    };

    const body =
      mode === 'adjustment'
        ? {
            inventory_item_id: selectedItem.id,
            current_quantity: numVal,
            reason: reason.trim(),
          }
        : {
            inventory_item_id: selectedItem.id,
            quantity: numVal,
            reason: reason.trim() || undefined,
          };

    try {
      const res = await apiRequest<{ message: string }>(
        endpointMap[mode as Exclude<ScreenMode, 'history'>],
        {
          method: 'POST',
          body,
          idempotencyKey,
        }
      );
      setSuccess(res.message);
      setQuantity('');
      setReason('');
      setIdempotencyKey(generateIdempotencyKey());
      setMode('history');
      await loadData(true);
    } catch (err: any) {
      setError(err?.message || 'Failed to record stock transaction.');
    } finally {
      setSubmitting(false);
    }
  };

  const typeBadgeColor = (t: TransactionType) => {
    if (t === 'stock_in') return COLORS.success;
    if (t === 'adjustment') return COLORS.secondary;
    return COLORS.danger;
  };

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={() => loadData(true)}
          tintColor={COLORS.primary}
        />
      }
      keyboardShouldPersistTaps="handled"
    >
      <View style={styles.pageHeader}>
        <Text style={styles.pageTitle}>Stock Actions &amp; History</Text>
        <Text style={styles.pageSubtitle}>
          Add new deliveries, remove ingredients used in the kitchen, fix wrong counts, or check past activity.
        </Text>
      </View>

      <FilterChips
        options={MODE_TABS}
        selected={mode}
        onSelect={(k) => switchMode(k as ScreenMode)}
      />

      <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
      <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

      {mode !== 'history' ? (
        <View style={styles.formCard}>
          <Text style={styles.formTitle}>
            {mode === 'stock_in'
              ? 'Add Stock (New Delivery or Restock)'
              : mode === 'stock_out'
              ? 'Remove Stock (Kitchen Usage)'
              : mode === 'adjustment'
              ? 'Correct Stock Quantity (Physical Count)'
              : 'Record Spoilage (Expired or Damaged)'}
          </Text>
          <Text style={styles.formSubtitle}>
            {mode === 'stock_in'
              ? 'Use this when new supplies arrive and need to be added to what we have.'
              : mode === 'stock_out'
              ? 'Use this when taking ingredients out for cooking, broth, or prep.'
              : mode === 'adjustment'
              ? 'Use this after counting items on the shelf so the app matches the real amount.'
              : 'Use this when food spoils, expires, or spills. Warning: This reduces available stock.'}
          </Text>

          {mode === 'spoilage' && (
            <View style={styles.spoilageMetaBox}>
              <View style={{ flexDirection: 'row', alignItems: 'center', marginBottom: 4 }}>
                <Ionicons name="warning" size={16} color={COLORS.danger} style={{ marginRight: 6 }} />
                <Text style={styles.spoilageWarnTitle}>Spoilage reduces stock permanently</Text>
              </View>
              <Text style={styles.spoilageMetaText}>
                Date: {new Date().toLocaleDateString()} • Recorded by: {user?.name || 'Staff Member'}
              </Text>
            </View>
          )}

          <Text style={styles.pickerLabel}>Step 1: Choose Item *</Text>
          <SearchBar
            value={itemFilter}
            onChangeText={setItemFilter}
            placeholder="Type item name to find it faster..."
          />
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={{ marginBottom: 14 }}
          >
            {filteredPickerItems.map((item) => {
              const active = item.id === selectedItemId;
              return (
                <TouchableOpacity
                  key={item.id}
                  onPress={() => setSelectedItemId(item.id)}
                  style={[styles.itemChip, active && styles.itemChipActive]}
                >
                  <Text style={[styles.itemChipTitle, active && { color: COLORS.text }]}>
                    {item.name}
                  </Text>
                  <Text style={styles.itemChipSub}>
                    On hand: {item.current_quantity} {item.unit_short_name}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          {selectedItem && (
            <View style={styles.currentBalanceBox}>
              <Text style={styles.balanceLabel}>Currently Available:</Text>
              <Text style={styles.balanceValue}>
                {selectedItem.current_quantity} {selectedItem.unit_short_name} (
                {selectedItem.status})
              </Text>
            </View>
          )}

          <FormField
            label={
              mode === 'stock_in'
                ? `Step 2: Quantity Received (${selectedItem?.unit_short_name || 'units'})`
                : mode === 'stock_out'
                ? `Step 2: Quantity to Remove (${selectedItem?.unit_short_name || 'units'})`
                : mode === 'adjustment'
                ? `Step 2: Real Count on Shelf (${selectedItem?.unit_short_name || 'units'})`
                : `Step 2: Spoiled Quantity (${selectedItem?.unit_short_name || 'units'})`
            }
            value={quantity}
            onChangeText={setQuantity}
            placeholder="e.g. 5"
            keyboardType="decimal-pad"
            required
          />

          {exceedsStock && selectedItem && (
            <View style={styles.overStockWarn}>
              <Ionicons name="alert-circle" size={18} color={COLORS.danger} style={{ marginRight: 8 }} />
              <Text style={styles.overStockWarnText}>
                You entered {parsedNum} {selectedItem.unit_short_name}, which is more than the{' '}
                {selectedItem.current_quantity} {selectedItem.unit_short_name} currently available.
              </Text>
            </View>
          )}

          <FormField
            label={
              mode === 'stock_in'
                ? 'Step 3: Supplier or Receipt Note (Optional)'
                : mode === 'stock_out'
                ? 'Step 3: Why is this being removed? (Optional)'
                : mode === 'adjustment'
                ? 'Step 3: Why does the count need correcting?'
                : 'Step 3: Spoilage Reason'
            }
            value={reason}
            onChangeText={setReason}
            placeholder={
              mode === 'stock_in'
                ? 'e.g. Public Market Meat Shop / Receipt #104'
                : mode === 'stock_out'
                ? 'e.g. Kitchen cooking prep'
                : mode === 'adjustment'
                ? 'e.g. End-of-day physical recount'
                : 'e.g. Expired / damaged batch'
            }
            required={mode === 'adjustment' || mode === 'spoilage'}
          />

          <Text style={styles.quickReasonLabel}>Quick tap a common reason:</Text>
          <View style={styles.quickReasonRow}>
            {REASON_PRESETS[mode].map((preset) => (
              <TouchableOpacity
                key={preset}
                style={styles.quickReasonChip}
                onPress={() => setReason(preset)}
              >
                <Text style={styles.quickReasonText}>{preset}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {selectedItem && hasValidNum && (
            <View style={styles.reviewBox}>
              <Text style={styles.reviewTitle}>Step 4: Review Before Saving</Text>
              <Text style={styles.reviewLine}>
                Item: <Text style={styles.reviewBold}>{selectedItem.name}</Text>
              </Text>
              <Text style={styles.reviewLine}>
                Current Quantity:{' '}
                <Text style={styles.reviewBold}>
                  {selectedItem.current_quantity} {selectedItem.unit_short_name}
                </Text>
              </Text>
              {mode === 'stock_in' && (
                <Text style={[styles.reviewLine, { color: COLORS.success }]}>
                  New Quantity After Adding:{' '}
                  <Text style={styles.reviewBold}>
                    {(selectedItem.current_quantity + parsedNum).toFixed(2).replace(/\.00$/, '')}{' '}
                    {selectedItem.unit_short_name}
                  </Text>
                </Text>
              )}
              {(mode === 'stock_out' || mode === 'spoilage') && (
                <Text
                  style={[
                    styles.reviewLine,
                    { color: exceedsStock ? COLORS.danger : COLORS.warning },
                  ]}
                >
                  Remaining Stock After Saving:{' '}
                  <Text style={styles.reviewBold}>
                    {Math.max(0, selectedItem.current_quantity - parsedNum)
                      .toFixed(2)
                      .replace(/\.00$/, '')}{' '}
                    {selectedItem.unit_short_name}
                  </Text>
                </Text>
              )}
              {mode === 'adjustment' && (
                <>
                  <Text style={styles.reviewLine}>
                    Corrected Real Count:{' '}
                    <Text style={[styles.reviewBold, { color: COLORS.secondary }]}>
                      {parsedNum} {selectedItem.unit_short_name}
                    </Text>
                  </Text>
                  <Text style={styles.reviewLine}>
                    Difference:{' '}
                    <Text style={styles.reviewBold}>
                      {parsedNum - selectedItem.current_quantity >= 0 ? '+' : ''}
                      {(parsedNum - selectedItem.current_quantity).toFixed(2).replace(/\.00$/, '')}{' '}
                      {selectedItem.unit_short_name}
                    </Text>
                  </Text>
                  <Text style={styles.reviewLine}>
                    Reason:{' '}
                    <Text style={styles.reviewBold}>{reason.trim() || 'Required above'}</Text>
                  </Text>
                </>
              )}
            </View>
          )}

          <PrimaryButton
            title={
              mode === 'stock_in'
                ? 'Step 5: Confirm Add Stock'
                : mode === 'stock_out'
                ? 'Step 5: Review & Remove Stock'
                : mode === 'adjustment'
                ? 'Step 5: Review & Save Correction'
                : 'Step 5: Review & Record Spoilage'
            }
            variant={mode === 'spoilage' || mode === 'stock_out' ? 'danger' : 'primary'}
            disabled={exceedsStock}
            onPress={validateAndPrompt}
            loading={submitting}
          />
        </View>
      ) : (
        <>
          <SearchBar
            value={search}
            onChangeText={setSearch}
            placeholder="Search by item name, staff member, or note..."
          />
          <FilterChips
            options={TYPE_FILTERS}
            selected={typeFilter}
            onSelect={setTypeFilter}
          />

          {loading && transactions.length === 0 ? (
            <SkeletonList count={5} height={86} />
          ) : transactions.length === 0 ? (
            <EmptyState
              icon="swap-vertical-outline"
              title="No stock history found"
              subtitle="Recorded stock additions, removals, corrections, and spoilage appear here permanently."
            />
          ) : (
            transactions.map((tx) => (
              <View key={tx.id} style={styles.txCard}>
                <View style={styles.txHeader}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.txItemName}>{tx.item_name}</Text>
                    <Text style={styles.txMeta}>Code: {tx.item_sku}</Text>
                  </View>
                  <Text
                    style={[styles.txBadge, { color: typeBadgeColor(tx.transaction_type) }]}
                  >
                    {formatTxTypeLabel(tx.transaction_type)}
                  </Text>
                </View>

                <View style={styles.txNumbersRow}>
                  <Text style={styles.txQtyText}>
                    Qty: {tx.quantity} {tx.unit_short_name}
                  </Text>
                  <Text style={styles.txQtyText}>
                    {tx.previous_quantity} → {tx.updated_quantity} {tx.unit_short_name}
                  </Text>
                </View>

                <Text style={styles.txFooter}>
                  By {tx.user_name} •{' '}
                  {tx.transaction_date
                    ? new Date(tx.transaction_date).toLocaleString()
                    : 'Just now'}
                </Text>
                {tx.reason ? <Text style={styles.txReason}>Reason: {tx.reason}</Text> : null}
              </View>
            ))
          )}
        </>
      )}

      <ConfirmModal
        visible={confirmVisible}
        title={
          mode === 'spoilage'
            ? 'Confirm Spoilage Record'
            : mode === 'stock_out'
            ? 'Confirm Stock Removal'
            : 'Confirm Stock Correction'
        }
        message={
          mode === 'spoilage'
            ? `Record ${quantity} ${selectedItem?.unit_short_name} of ${selectedItem?.name} as spoiled? This reduces available stock from ${selectedItem?.current_quantity} to ${Math.max(0, (selectedItem?.current_quantity ?? 0) - Number(quantity || 0)).toFixed(2).replace(/\.00$/, '')} ${selectedItem?.unit_short_name}.`
            : mode === 'stock_out'
            ? `Remove ${quantity} ${selectedItem?.unit_short_name} of ${selectedItem?.name}? Remaining stock will be ${Math.max(0, (selectedItem?.current_quantity ?? 0) - Number(quantity || 0)).toFixed(2).replace(/\.00$/, '')} ${selectedItem?.unit_short_name}.`
            : `Correct ${selectedItem?.name} count from ${selectedItem?.current_quantity} to ${quantity} ${selectedItem?.unit_short_name}? Reason: "${reason.trim()}".`
        }
        confirmText="Confirm & Save"
        confirmVariant={mode === 'spoilage' || mode === 'stock_out' ? 'danger' : 'primary'}
        loading={submitting}
        onConfirm={executeTransaction}
        onCancel={() => setConfirmVisible(false)}
      />
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
  formCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 16,
  },
  formTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 4,
  },
  pageHeader: {
    marginBottom: 10,
  },
  pageTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
  },
  pageSubtitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 17,
    marginTop: 2,
  },
  formSubtitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 17,
    marginBottom: 12,
  },
  spoilageMetaBox: {
    backgroundColor: 'rgba(229, 57, 53, 0.14)',
    borderWidth: 1,
    borderColor: COLORS.danger,
    borderRadius: 10,
    padding: 10,
    marginBottom: 12,
  },
  spoilageWarnTitle: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '700',
  },
  spoilageMetaText: {
    color: COLORS.textSecondary,
    fontSize: 11,
  },
  overStockWarn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(229, 57, 53, 0.18)',
    borderWidth: 1,
    borderColor: COLORS.danger,
    borderRadius: 10,
    padding: 10,
    marginBottom: 12,
  },
  overStockWarnText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 12,
    lineHeight: 17,
  },
  quickReasonLabel: {
    color: COLORS.textSecondary,
    fontSize: 11,
    fontWeight: '600',
    marginBottom: 6,
  },
  quickReasonRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 14,
  },
  quickReasonChip: {
    backgroundColor: COLORS.navBackground,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 999,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  quickReasonText: {
    color: COLORS.textSecondary,
    fontSize: 11,
    fontWeight: '600',
  },
  reviewBox: {
    backgroundColor: COLORS.navBackground,
    borderWidth: 1,
    borderColor: COLORS.secondary,
    borderRadius: 10,
    padding: 12,
    marginBottom: 14,
  },
  reviewTitle: {
    color: COLORS.secondary,
    fontSize: 12,
    fontWeight: '800',
    marginBottom: 6,
  },
  reviewLine: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginBottom: 3,
  },
  reviewBold: {
    color: COLORS.text,
    fontWeight: '700',
  },
  pickerLabel: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 8,
  },
  itemChip: {
    backgroundColor: COLORS.navBackground,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginRight: 8,
  },
  itemChipActive: {
    borderColor: COLORS.primary,
    backgroundColor: 'rgba(229, 57, 53, 0.18)',
  },
  itemChipTitle: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '700',
  },
  itemChipSub: {
    color: COLORS.textMuted,
    fontSize: 11,
    marginTop: 2,
  },
  currentBalanceBox: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    backgroundColor: COLORS.navBackground,
    padding: 10,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginBottom: 12,
  },
  balanceLabel: {
    color: COLORS.textSecondary,
    fontSize: 12,
  },
  balanceValue: {
    color: COLORS.secondary,
    fontSize: 12,
    fontWeight: '700',
  },
  txCard: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 9,
  },
  txHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  txItemName: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
  },
  txMeta: {
    color: COLORS.textMuted,
    fontSize: 11,
  },
  txBadge: {
    fontSize: 11,
    fontWeight: '700',
  },
  txNumbersRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  txQtyText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '600',
  },
  txFooter: {
    color: COLORS.textSecondary,
    fontSize: 11,
    marginTop: 6,
  },
  txReason: {
    color: COLORS.textMuted,
    fontSize: 11,
    marginTop: 3,
  },
});

