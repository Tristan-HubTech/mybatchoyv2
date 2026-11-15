import React, { useCallback, useEffect, useState } from 'react';
import {
  Modal,
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
import {
  CategoryOption,
  InventoryItemModel,
  StockTransactionModel,
  UnitOption,
} from '../types/models';
import {
  BannerMessage,
  EmptyState,
  FilterChips,
  FormField,
  PrimaryButton,
  SearchBar,
  SkeletonList,
  StatusBadge,
} from '../components/UI';

const STATUS_FILTERS = [
  { key: 'all', label: 'All Items' },
  { key: 'available', label: 'Available' },
  { key: 'low_stock', label: 'Low Stock' },
  { key: 'out_of_stock', label: 'Out of Stock' },
];

export const InventoryScreen: React.FC = () => {
  const { user } = useAuth();
  const isOwner = user?.role === 'owner';

  const [items, setItems] = useState<InventoryItemModel[]>([]);
  const [categories, setCategories] = useState<CategoryOption[]>([]);
  const [units, setUnits] = useState<UnitOption[]>([]);
  const [defaults, setDefaults] = useState({ minimum_stock_level: 0, reorder_level: 0 });

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [categoryFilter, setCategoryFilter] = useState<string>('all');

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Detail modal state
  const [selectedItem, setSelectedItem] = useState<InventoryItemModel | null>(null);
  const [itemTransactions, setItemTransactions] = useState<StockTransactionModel[]>([]);
  const [detailLoading, setDetailLoading] = useState(false);

  // Add/Edit form modal state (Owner only)
  const [formVisible, setFormVisible] = useState(false);
  const [editingItem, setEditingItem] = useState<InventoryItemModel | null>(null);
  const [name, setName] = useState('');
  const [sku, setSku] = useState('');
  const [categoryId, setCategoryId] = useState<number>(0);
  const [unitId, setUnitId] = useState<number>(0);
  const [currentQuantity, setCurrentQuantity] = useState('0');
  const [minimumStockLevel, setMinimumStockLevel] = useState('0');
  const [reorderLevel, setReorderLevel] = useState('0');
  const [description, setDescription] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [idempotencyKey, setIdempotencyKey] = useState(generateIdempotencyKey());

  const loadInventory = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        const res = await apiRequest<{
          items: InventoryItemModel[];
          categories: CategoryOption[];
          units: UnitOption[];
          defaults: { minimum_stock_level: number; reorder_level: number };
        }>('/inventory', {
          params: {
            search: search.trim() || undefined,
            status: statusFilter !== 'all' ? statusFilter : undefined,
            category: categoryFilter !== 'all' ? Number(categoryFilter) : undefined,
          },
        });
        setItems(res.items);
        setCategories(res.categories);
        setUnits(res.units);
        if (res.defaults) setDefaults(res.defaults);
      } catch (err: any) {
        setError(err?.message || 'Failed to load inventory.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [search, statusFilter, categoryFilter]
  );

  useEffect(() => {
    loadInventory();
  }, [loadInventory]);

  const openItemDetail = async (item: InventoryItemModel) => {
    setSelectedItem(item);
    setDetailLoading(true);
    try {
      const res = await apiRequest<{
        item: InventoryItemModel;
        recent_transactions: StockTransactionModel[];
      }>(`/inventory/${item.id}`);
      setSelectedItem(res.item);
      setItemTransactions(res.recent_transactions || []);
    } catch {
      setItemTransactions([]);
    } finally {
      setDetailLoading(false);
    }
  };

  const openCreateForm = () => {
    setEditingItem(null);
    setName('');
    setSku('');
    const activeCat = categories.find((c) => c.is_active) || categories[0];
    const activeUnit = units.find((u) => u.is_active) || units[0];
    setCategoryId(activeCat?.id || 0);
    setUnitId(activeUnit?.id || 0);
    setCurrentQuantity('0');
    setMinimumStockLevel(String(defaults.minimum_stock_level ?? 0));
    setReorderLevel(String(defaults.reorder_level ?? 0));
    setDescription('');
    setIdempotencyKey(generateIdempotencyKey());
    setFormVisible(true);
  };

  const openEditForm = (item: InventoryItemModel) => {
    setSelectedItem(null);
    setEditingItem(item);
    setName(item.name);
    setSku(item.sku);
    setCategoryId(item.category_id);
    setUnitId(item.unit_id);
    setCurrentQuantity(String(item.current_quantity));
    setMinimumStockLevel(String(item.minimum_stock_level));
    setReorderLevel(String(item.reorder_level));
    setDescription(item.description || '');
    setIdempotencyKey(generateIdempotencyKey());
    setFormVisible(true);
  };

  const handleSaveItem = async () => {
    if (!name.trim() || !categoryId || !unitId) {
      setError('Name, category, and measurement unit are required.');
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      const payload = {
        name: name.trim(),
        sku: sku.trim() || null,
        category_id: categoryId,
        unit_id: unitId,
        current_quantity: Number(currentQuantity || 0),
        minimum_stock_level: Number(minimumStockLevel || 0),
        reorder_level: Number(reorderLevel || 0),
        description: description.trim() || null,
      };

      if (editingItem) {
        await apiRequest(`/inventory/${editingItem.id}`, {
          method: 'PUT',
          body: payload,
          idempotencyKey,
        });
        setSuccess('Inventory item updated successfully.');
      } else {
        await apiRequest('/inventory', {
          method: 'POST',
          body: payload,
          idempotencyKey,
        });
        setSuccess('Inventory item created successfully.');
      }
      setFormVisible(false);
      await loadInventory(true);
    } catch (err: any) {
      setError(err?.message || 'Unable to save inventory item.');
    } finally {
      setSubmitting(false);
    }
  };

  const categoryChipOptions = [
    { key: 'all', label: 'All Categories' },
    ...categories.map((c) => ({ key: String(c.id), label: c.name })),
  ];

  return (
    <View style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadInventory(true)}
            tintColor={COLORS.primary}
          />
        }
      >
        <View style={styles.topRow}>
          <View style={{ flex: 1, paddingRight: 8 }}>
            <Text style={styles.heading}>Manage Stock ({items.length})</Text>
            <Text style={styles.subHeading}>
              See current stock amounts. Tap any item to inspect history and levels.
            </Text>
          </View>
          {isOwner && (
            <PrimaryButton
              title="+ New Item"
              icon="add-circle-outline"
              onPress={openCreateForm}
              style={{ minHeight: 40, paddingVertical: 6 }}
            />
          )}
        </View>

        <SearchBar
          value={search}
          onChangeText={setSearch}
          placeholder="Search item name or code..."
        />

        <FilterChips
          options={STATUS_FILTERS}
          selected={statusFilter}
          onSelect={setStatusFilter}
        />

        {categories.length > 0 && (
          <FilterChips
            options={categoryChipOptions}
            selected={categoryFilter}
            onSelect={setCategoryFilter}
          />
        )}

        <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
        <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

        {loading && items.length === 0 ? (
          <SkeletonList count={5} height={95} />
        ) : items.length === 0 ? (
          <EmptyState
            title="No matching inventory items"
            subtitle="Try adjusting your search or filter criteria."
          />
        ) : (
          items.map((item) => (
            <TouchableOpacity
              key={item.id}
              style={styles.card}
              onPress={() => openItemDetail(item)}
              activeOpacity={0.8}
            >
              <View style={styles.cardHeader}>
                <View style={{ flex: 1, paddingRight: 8 }}>
                  <Text style={styles.itemName}>{item.name}</Text>
                  <Text style={styles.itemSub}>
                    Code: {item.sku} • {item.category_name}
                  </Text>
                </View>
                <StatusBadge status={item.status} />
              </View>

              <View style={styles.cardMetrics}>
                <View>
                  <Text style={styles.metricSmallLabel}>Current Qty</Text>
                  <Text style={styles.metricSmallValue}>
                    {item.current_quantity} {item.unit_short_name}
                  </Text>
                </View>
                <View>
                  <Text style={styles.metricSmallLabel}>Min Stock</Text>
                  <Text style={styles.metricSmallValue}>
                    {item.minimum_stock_level} {item.unit_short_name}
                  </Text>
                </View>
                <View>
                  <Text style={styles.metricSmallLabel}>Reorder Level</Text>
                  <Text style={styles.metricSmallValue}>
                    {item.reorder_level} {item.unit_short_name}
                  </Text>
                </View>
              </View>
            </TouchableOpacity>
          ))
        )}
      </ScrollView>

      {/* Item Details Modal */}
      <Modal
        visible={!!selectedItem}
        transparent
        animationType="slide"
        onRequestClose={() => setSelectedItem(null)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            {selectedItem && (
              <ScrollView>
                <View style={styles.sheetHeader}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.sheetTitle}>{selectedItem.name}</Text>
                    <Text style={styles.itemSub}>
                      Code: {selectedItem.sku} • {selectedItem.category_name}
                    </Text>
                  </View>
                  <TouchableOpacity onPress={() => setSelectedItem(null)}>
                    <Ionicons name="close-circle" size={26} color={COLORS.textSecondary} />
                  </TouchableOpacity>
                </View>

                <View style={{ marginVertical: 10 }}>
                  <StatusBadge status={selectedItem.status} />
                </View>

                <View style={styles.detailBox}>
                  <Text style={styles.detailLine}>
                    Current Quantity:{' '}
                    <Text style={styles.detailBold}>
                      {selectedItem.current_quantity} {selectedItem.unit_name} (
                      {selectedItem.unit_short_name})
                    </Text>
                  </Text>
                  <Text style={styles.detailLine}>
                    Minimum Stock (Warn below): {selectedItem.minimum_stock_level}{' '}
                    {selectedItem.unit_short_name}
                  </Text>
                  <Text style={styles.detailLine}>
                    Reorder Level: {selectedItem.reorder_level} {selectedItem.unit_short_name}
                  </Text>
                  {selectedItem.description ? (
                    <Text style={styles.detailLine}>Notes: {selectedItem.description}</Text>
                  ) : null}
                </View>

                {isOwner && (
                  <PrimaryButton
                    title="Edit Item Details"
                    icon="create-outline"
                    onPress={() => openEditForm(selectedItem)}
                    style={{ marginVertical: 10 }}
                  />
                )}

                <Text style={[styles.sheetTitle, { fontSize: 15, marginTop: 10 }]}>
                  Recent Stock History
                </Text>
                {detailLoading ? (
                  <SkeletonList count={2} height={64} />
                ) : itemTransactions.length === 0 ? (
                  <Text style={[styles.itemSub, { marginVertical: 10 }]}>
                    No recorded movements for this item yet.
                  </Text>
                ) : (
                  itemTransactions.map((tx) => (
                    <View key={tx.id} style={styles.txItem}>
                      <Text style={{ color: COLORS.text, fontWeight: '700', fontSize: 13 }}>
                        {tx.transaction_type.toUpperCase()} ({tx.quantity} {tx.unit_short_name})
                      </Text>
                      <Text style={styles.itemSub}>
                        {tx.previous_quantity} → {tx.updated_quantity} • By {tx.user_name}
                      </Text>
                      {tx.reason ? <Text style={styles.itemSub}>Reason: {tx.reason}</Text> : null}
                    </View>
                  ))
                )}
              </ScrollView>
            )}
          </View>
        </View>
      </Modal>

      {/* Owner Add / Edit Item Modal */}
      <Modal
        visible={formVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setFormVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <ScrollView keyboardShouldPersistTaps="handled">
              <View style={styles.sheetHeader}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.sheetTitle}>
                    {editingItem ? 'Edit Stock Item' : 'Add New Stock Item'}
                  </Text>
                  <Text style={styles.itemSub}>
                    Fill in steps 1–6 so the team can easily track this ingredient.
                  </Text>
                </View>
                <TouchableOpacity onPress={() => setFormVisible(false)}>
                  <Ionicons name="close-circle" size={26} color={COLORS.textSecondary} />
                </TouchableOpacity>
              </View>

              <FormField
                label="1. Item Name"
                value={name}
                onChangeText={setName}
                placeholder="e.g. Pork Belly or Fresh Miki Noodles"
                required
              />
              <FormField
                label="Item Code (Optional — created automatically if blank)"
                value={sku}
                onChangeText={setSku}
                placeholder="e.g. PORK-001"
              />

              <Text style={styles.pickerLabel}>2. Category *</Text>
              <FilterChips
                options={categories.map((c) => ({ key: String(c.id), label: c.name }))}
                selected={String(categoryId)}
                onSelect={(k) => setCategoryId(Number(k))}
              />

              <Text style={styles.pickerLabel}>3. Measurement Unit *</Text>
              <FilterChips
                options={units.map((u) => ({
                  key: String(u.id),
                  label: `${u.name} (${u.short_name})`,
                }))}
                selected={String(unitId)}
                onSelect={(k) => setUnitId(Number(k))}
              />

              <FormField
                label="4. Current Quantity on Hand"
                value={currentQuantity}
                onChangeText={setCurrentQuantity}
                placeholder="e.g. 20"
                keyboardType="decimal-pad"
                required
              />
              <FormField
                label="5. Minimum Stock (Warn when below this number)"
                value={minimumStockLevel}
                onChangeText={setMinimumStockLevel}
                placeholder="e.g. 5"
                keyboardType="decimal-pad"
                required
              />
              <FormField
                label="6. Reorder Level (Suggested level to buy again)"
                value={reorderLevel}
                onChangeText={setReorderLevel}
                placeholder="e.g. 10"
                keyboardType="decimal-pad"
              />
              <FormField
                label="Notes (Optional)"
                value={description}
                onChangeText={setDescription}
                placeholder="Storage tips or pack size notes"
                multiline
              />

              <View style={{ flexDirection: 'row', marginTop: 10 }}>
                <PrimaryButton
                  title="Cancel"
                  variant="outline"
                  onPress={() => setFormVisible(false)}
                  style={{ flex: 1, marginRight: 8 }}
                />
                <PrimaryButton
                  title={editingItem ? 'Save Changes' : 'Save Item'}
                  onPress={handleSaveItem}
                  loading={submitting}
                  style={{ flex: 1 }}
                />
              </View>
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
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
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  heading: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '700',
  },
  subHeading: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 17,
    marginTop: 2,
  },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 10,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
  },
  itemName: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  itemSub: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginTop: 2,
  },
  cardMetrics: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  metricSmallLabel: {
    color: COLORS.textMuted,
    fontSize: 11,
  },
  metricSmallValue: {
    color: COLORS.text,
    fontSize: 13,
    fontWeight: '700',
    marginTop: 2,
  },
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
  sheetHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  sheetTitle: {
    color: COLORS.text,
    fontSize: 17,
    fontWeight: '700',
  },
  detailBox: {
    backgroundColor: COLORS.navBackground,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
  },
  detailLine: {
    color: COLORS.textSecondary,
    fontSize: 13,
    marginBottom: 5,
  },
  detailBold: {
    color: COLORS.text,
    fontWeight: '700',
  },
  txItem: {
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
  pickerLabel: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 6,
  },
});

