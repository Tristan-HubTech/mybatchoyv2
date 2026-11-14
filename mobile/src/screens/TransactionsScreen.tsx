import React, { useCallback, useEffect, useState } from 'react';
import {
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
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

type ScreenMode = 'history' | 'stock_in' | 'stock_out' | 'adjustment' | 'spoilage';

const MODE_TABS: { key: ScreenMode; label: string }[] = [
  { key: 'history', label: 'History' },
  { key: 'stock_in', label: '+ Stock In' },
  { key: 'stock_out', label: '- Stock Out' },
  { key: 'adjustment', label: 'Adjustment' },
  { key: 'spoilage', label: 'Spoilage' },
];

const TYPE_FILTERS = [
  { key: 'all', label: 'All Types' },
  { key: 'stock_in', label: 'Stock In' },
  { key: 'stock_out', label: 'Stock Out' },
  { key: 'adjustment', label: 'Adjustment' },
  { key: 'spoilage', label: 'Spoilage' },
];

export const TransactionsScreen: React.FC = () => {
  const [mode, setMode] = useState<ScreenMode>('history');
  const [transactions, setTransactions] = useState<StockTransactionModel[]>([]);
  const [items, setItems] = useState<InventoryItemModel[]>([]);

  const [typeFilter, setTypeFilter] = useState('all');
  const [search, setSearch] = useState('');

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
      setError('Select an inventory item.');
      return;
    }

    const numVal = Number(quantity);
    if (Number.isNaN(numVal) || (mode !== 'adjustment' && numVal <= 0) || numVal < 0) {
      setError('Enter a valid positive quantity.');
      return;
    }

    if ((mode === 'stock_out' || mode === 'spoilage') && numVal > selectedItem.current_quantity) {
      setError(
        `Insufficient stock. Current available quantity is ${selectedItem.current_quantity} ${selectedItem.unit_short_name}.`
      );
      return;
    }

    if ((mode === 'adjustment' || mode === 'spoilage') && !reason.trim()) {
      setError('A reason is required for stock adjustments and spoilage records.');
      return;
    }

    if (mode === 'adjustment' || mode === 'spoilage') {
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
              ? 'Record Stock In'
              : mode === 'stock_out'
              ? 'Record Stock Out'
              : mode === 'adjustment'
              ? 'Stock Physical Adjustment'
              : 'Record Spoilage'}
          </Text>

          <Text style={styles.pickerLabel}>Select Inventory Item *</Text>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={{ marginBottom: 14 }}
          >
            {items.map((item) => {
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
                    {item.current_quantity} {item.unit_short_name}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          {selectedItem && (
            <View style={styles.currentBalanceBox}>
              <Text style={styles.balanceLabel}>Selected Item Balance:</Text>
              <Text style={styles.balanceValue}>
                {selectedItem.current_quantity} {selectedItem.unit_short_name} (
                {selectedItem.status})
              </Text>
            </View>
          )}

          <FormField
            label={
              mode === 'adjustment'
                ? `New Actual Count (${selectedItem?.unit_short_name || 'units'})`
                : `Quantity (${selectedItem?.unit_short_name || 'units'})`
            }
            value={quantity}
            onChangeText={setQuantity}
            placeholder="0.00"
            keyboardType="decimal-pad"
            required
          />

          <FormField
            label="Reason / Reference Note"
            value={reason}
            onChangeText={setReason}
            placeholder={
              mode === 'stock_in'
                ? 'e.g. Morning delivery'
                : mode === 'stock_out'
                ? 'e.g. Kitchen preparation'
                : mode === 'adjustment'
                ? 'e.g. End-of-day physical recount'
                : 'e.g. Expired / damaged batch'
            }
            required={mode === 'adjustment' || mode === 'spoilage'}
          />

          <PrimaryButton
            title={`Submit ${mode.replace('_', ' ').toUpperCase()}`}
            variant={mode === 'spoilage' ? 'danger' : 'primary'}
            onPress={validateAndPrompt}
            loading={submitting}
          />
        </View>
      ) : (
        <>
          <SearchBar
            value={search}
            onChangeText={setSearch}
            placeholder="Search item, SKU, user, or reason..."
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
              title="No stock transactions found"
              subtitle="Recorded stock movements appear here and cannot be modified or deleted."
            />
          ) : (
            transactions.map((tx) => (
              <View key={tx.id} style={styles.txCard}>
                <View style={styles.txHeader}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.txItemName}>{tx.item_name}</Text>
                    <Text style={styles.txMeta}>SKU: {tx.item_sku}</Text>
                  </View>
                  <Text
                    style={[styles.txBadge, { color: typeBadgeColor(tx.transaction_type) }]}
                  >
                    {tx.transaction_type.replace('_', ' ').toUpperCase()}
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
        title={mode === 'spoilage' ? 'Confirm Spoilage Record' : 'Confirm Stock Adjustment'}
        message={
          mode === 'spoilage'
            ? `Deduct ${quantity} ${selectedItem?.unit_short_name} from ${selectedItem?.name} as spoilage? Transaction history is permanent.`
            : `Set ${selectedItem?.name} stock level from ${selectedItem?.current_quantity} to ${quantity} ${selectedItem?.unit_short_name}?`
        }
        confirmText="Confirm & Save"
        confirmVariant={mode === 'spoilage' ? 'danger' : 'primary'}
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
    marginBottom: 12,
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

