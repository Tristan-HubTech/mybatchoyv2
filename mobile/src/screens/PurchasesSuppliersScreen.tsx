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
import { apiRequest, generateIdempotencyKey } from '../services/api';
import { COLORS } from '../theme/colors';
import { InventoryItemModel, PurchaseModel, SupplierModel } from '../types/models';
import {
  BannerMessage,
  EmptyState,
  FilterChips,
  FormField,
  PrimaryButton,
  SearchBar,
  SkeletonList,
} from '../components/UI';

const SUB_TABS = [
  { key: 'purchases', label: 'Purchases' },
  { key: 'new_purchase', label: '+ Record Purchase' },
  { key: 'suppliers', label: 'Suppliers' },
];

interface DraftLineItem {
  inventory_item_id: number;
  quantity: string;
  unit_cost: string;
}

export const PurchasesSuppliersScreen: React.FC = () => {
  const [tab, setTab] = useState<'purchases' | 'new_purchase' | 'suppliers'>('purchases');

  const [purchases, setPurchases] = useState<PurchaseModel[]>([]);
  const [suppliers, setSuppliers] = useState<SupplierModel[]>([]);
  const [items, setItems] = useState<InventoryItemModel[]>([]);

  const [supplierFilter, setSupplierFilter] = useState<string>('all');
  const [supplierSearch, setSupplierSearch] = useState('');

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Purchase form state
  const [poSupplierId, setPoSupplierId] = useState<number | null>(null);
  const [poDate, setPoDate] = useState(new Date().toISOString().slice(0, 10));
  const [poNotes, setPoNotes] = useState('');
  const [poLines, setPoLines] = useState<DraftLineItem[]>([]);
  const [poSubmitting, setPoSubmitting] = useState(false);
  const [idempotencyKey, setIdempotencyKey] = useState(generateIdempotencyKey());

  // Supplier modal & detail state
  const [selectedSupplier, setSelectedSupplier] = useState<SupplierModel | null>(null);
  const [supplierPurchases, setSupplierPurchases] = useState<PurchaseModel[]>([]);
  const [supplierFormVisible, setSupplierFormVisible] = useState(false);
  const [editingSupplier, setEditingSupplier] = useState<SupplierModel | null>(null);
  const [supName, setSupName] = useState('');
  const [supContact, setSupContact] = useState('');
  const [supPhone, setSupPhone] = useState('');
  const [supEmail, setSupEmail] = useState('');
  const [supAddress, setSupAddress] = useState('');
  const [supNotes, setSupNotes] = useState('');

  const loadAll = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        const [poRes, supRes] = await Promise.all([
          apiRequest<{
            purchases: PurchaseModel[];
            items: InventoryItemModel[];
          }>('/purchases', {
            params: {
              supplier: supplierFilter !== 'all' ? Number(supplierFilter) : undefined,
            },
          }),
          apiRequest<{ suppliers: SupplierModel[] }>('/suppliers', {
            params: { search: supplierSearch.trim() || undefined },
          }),
        ]);
        setPurchases(poRes.purchases);
        setItems(poRes.items);
        setSuppliers(supRes.suppliers);

        if (poLines.length === 0 && poRes.items.length > 0) {
          setPoLines([
            {
              inventory_item_id: poRes.items[0].id,
              quantity: '1',
              unit_cost: '0',
            },
          ]);
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to load purchasing & supplier data.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [supplierFilter, supplierSearch, poLines.length]
  );

  useEffect(() => {
    loadAll();
  }, [loadAll]);

  const addLineItem = () => {
    if (items.length === 0) return;
    setPoLines((prev) => [
      ...prev,
      { inventory_item_id: items[0].id, quantity: '1', unit_cost: '0' },
    ]);
  };

  const removeLineItem = (idx: number) => {
    setPoLines((prev) => prev.filter((_, i) => i !== idx));
  };

  const updateLineItem = (idx: number, patch: Partial<DraftLineItem>) => {
    setPoLines((prev) => prev.map((line, i) => (i === idx ? { ...line, ...patch } : line)));
  };

  const computedGrandTotal = poLines.reduce((acc, line) => {
    const q = Number(line.quantity) || 0;
    const c = Number(line.unit_cost) || 0;
    return acc + q * c;
  }, 0);

  const handleCreatePurchase = async () => {
    if (!poDate.trim() || poLines.length === 0) {
      setError('Provide a purchase date and at least one purchased item.');
      return;
    }

    for (const line of poLines) {
      if (!line.inventory_item_id || Number(line.quantity) <= 0 || Number(line.unit_cost) < 0) {
        setError('Each item must have a quantity greater than 0 and a valid unit cost.');
        return;
      }
    }

    setPoSubmitting(true);
    setError(null);
    try {
      const res = await apiRequest<{ message: string }>('/purchases', {
        method: 'POST',
        idempotencyKey,
        body: {
          supplier_id: poSupplierId,
          purchase_date: poDate.trim(),
          notes: poNotes.trim() || null,
          items: poLines.map((l) => ({
            inventory_item_id: l.inventory_item_id,
            quantity: Number(l.quantity),
            unit_cost: Number(l.unit_cost),
          })),
        },
      });
      setSuccess(res.message);
      setPoNotes('');
      setIdempotencyKey(generateIdempotencyKey());
      setTab('purchases');
      await loadAll(true);
    } catch (err: any) {
      setError(err?.message || 'Unable to record purchase.');
    } finally {
      setPoSubmitting(false);
    }
  };

  const openSupplierDetails = async (sup: SupplierModel) => {
    setSelectedSupplier(sup);
    try {
      const res = await apiRequest<{ supplier: SupplierModel; purchases: PurchaseModel[] }>(
        `/suppliers/${sup.id}`
      );
      setSelectedSupplier(res.supplier);
      setSupplierPurchases(res.purchases || []);
    } catch {
      setSupplierPurchases([]);
    }
  };

  const openSupplierForm = (sup?: SupplierModel) => {
    setSelectedSupplier(null);
    setEditingSupplier(sup || null);
    setSupName(sup?.name || '');
    setSupContact(sup?.contact_person || '');
    setSupPhone(sup?.phone || '');
    setSupEmail(sup?.email || '');
    setSupAddress(sup?.address || '');
    setSupNotes(sup?.notes || '');
    setIdempotencyKey(generateIdempotencyKey());
    setSupplierFormVisible(true);
  };

  const handleSaveSupplier = async () => {
    if (!supName.trim()) {
      setError('Supplier name is required.');
      return;
    }
    setPoSubmitting(true);
    setError(null);
    try {
      const body = {
        name: supName.trim(),
        contact_person: supContact.trim() || null,
        phone: supPhone.trim() || null,
        email: supEmail.trim() || null,
        address: supAddress.trim() || null,
        notes: supNotes.trim() || null,
      };
      if (editingSupplier) {
        await apiRequest(`/suppliers/${editingSupplier.id}`, {
          method: 'PUT',
          body,
          idempotencyKey,
        });
        setSuccess('Supplier updated successfully.');
      } else {
        await apiRequest('/suppliers', {
          method: 'POST',
          body,
          idempotencyKey,
        });
        setSuccess('Supplier created successfully.');
      }
      setSupplierFormVisible(false);
      await loadAll(true);
    } catch (err: any) {
      setError(err?.message || 'Failed to save supplier.');
    } finally {
      setPoSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadAll(true)}
            tintColor={COLORS.primary}
          />
        }
        keyboardShouldPersistTaps="handled"
      >
        <FilterChips
          options={SUB_TABS}
          selected={tab}
          onSelect={(k) => {
            setTab(k as any);
            setError(null);
          }}
        />

        <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
        <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

        {tab === 'purchases' && (
          <>
            <FilterChips
              options={[
                { key: 'all', label: 'All Suppliers' },
                ...suppliers.map((s) => ({ key: String(s.id), label: s.name })),
              ]}
              selected={supplierFilter}
              onSelect={setSupplierFilter}
            />

            {loading && purchases.length === 0 ? (
              <SkeletonList count={4} height={94} />
            ) : purchases.length === 0 ? (
              <EmptyState
                icon="cart-outline"
                title="No purchase records found"
                subtitle="Record purchases to automatically increment stock quantities."
              />
            ) : (
              purchases.map((po) => (
                <View key={po.id} style={styles.card}>
                  <View style={styles.cardTop}>
                    <View>
                      <Text style={styles.cardTitle}>{po.purchase_number}</Text>
                      <Text style={styles.cardSub}>
                        {po.supplier_name} • {po.purchase_date}
                      </Text>
                    </View>
                    <Text style={styles.totalAmount}>
                      ₱{po.total_amount.toLocaleString('en-PH', { minimumFractionDigits: 2 })}
                    </Text>
                  </View>
                  {po.items && po.items.length > 0 && (
                    <View style={styles.linesBox}>
                      {po.items.map((line) => (
                        <Text key={line.id} style={styles.lineText}>
                          • {line.item_name}: +{line.quantity} {line.unit_short_name} @ ₱
                          {line.unit_cost.toFixed(2)} = ₱{line.total_cost.toFixed(2)}
                        </Text>
                      ))}
                    </View>
                  )}
                  <Text style={styles.cardFooter}>Recorded by {po.created_by_name}</Text>
                </View>
              ))
            )}
          </>
        )}

        {tab === 'new_purchase' && (
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Record New Purchase Order</Text>

            <Text style={[styles.cardSub, { marginTop: 10, marginBottom: 6 }]}>Supplier</Text>
            <FilterChips
              options={[
                { key: 'none', label: 'Direct / Walk-in' },
                ...suppliers.map((s) => ({ key: String(s.id), label: s.name })),
              ]}
              selected={poSupplierId ? String(poSupplierId) : 'none'}
              onSelect={(k) => setPoSupplierId(k === 'none' ? null : Number(k))}
            />

            <FormField
              label="Purchase Date (YYYY-MM-DD)"
              value={poDate}
              onChangeText={setPoDate}
              placeholder="2026-11-14"
              required
            />

            <View style={styles.linesHeader}>
              <Text style={styles.cardTitle}>Purchased Items</Text>
              <TouchableOpacity onPress={addLineItem} style={styles.addLineBtn}>
                <Ionicons name="add-circle" size={18} color={COLORS.secondary} />
                <Text style={styles.addLineText}>Add Line</Text>
              </TouchableOpacity>
            </View>

            {poLines.map((line, index) => (
              <View key={index} style={styles.lineFormBox}>
                <View style={styles.cardTop}>
                  <Text style={styles.cardSub}>Item #{index + 1}</Text>
                  {poLines.length > 1 && (
                    <TouchableOpacity onPress={() => removeLineItem(index)}>
                      <Ionicons name="trash-outline" size={18} color={COLORS.danger} />
                    </TouchableOpacity>
                  )}
                </View>
                <FilterChips
                  options={items.map((it) => ({
                    key: String(it.id),
                    label: `${it.name} (${it.unit_short_name})`,
                  }))}
                  selected={String(line.inventory_item_id)}
                  onSelect={(k) => updateLineItem(index, { inventory_item_id: Number(k) })}
                />
                <View style={{ flexDirection: 'row', gap: 10 }}>
                  <View style={{ flex: 1 }}>
                    <FormField
                      label="Quantity"
                      value={line.quantity}
                      onChangeText={(v) => updateLineItem(index, { quantity: v })}
                      keyboardType="decimal-pad"
                      required
                    />
                  </View>
                  <View style={{ flex: 1 }}>
                    <FormField
                      label="Unit Cost (₱)"
                      value={line.unit_cost}
                      onChangeText={(v) => updateLineItem(index, { unit_cost: v })}
                      keyboardType="decimal-pad"
                      required
                    />
                  </View>
                </View>
              </View>
            ))}

            <FormField
              label="Purchase Notes"
              value={poNotes}
              onChangeText={setPoNotes}
              placeholder="Invoice / delivery receipt notes"
              multiline
            />

            <View style={styles.grandTotalRow}>
              <Text style={styles.cardTitle}>Calculated Total</Text>
              <Text style={styles.totalAmount}>
                ₱{computedGrandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 })}
              </Text>
            </View>

            <PrimaryButton
              title="Save Purchase & Update Stock"
              icon="checkmark-circle-outline"
              onPress={handleCreatePurchase}
              loading={poSubmitting}
            />
          </View>
        )}

        {tab === 'suppliers' && (
          <>
            <View style={[styles.cardTop, { marginBottom: 10 }]}>
              <Text style={styles.cardTitle}>Suppliers ({suppliers.length})</Text>
              <PrimaryButton
                title="Add Supplier"
                icon="person-add-outline"
                onPress={() => openSupplierForm()}
                style={{ minHeight: 38, paddingVertical: 6 }}
              />
            </View>

            <SearchBar
              value={supplierSearch}
              onChangeText={setSupplierSearch}
              placeholder="Search supplier or contact person..."
            />

            {suppliers.length === 0 ? (
              <EmptyState
                icon="business-outline"
                title="No suppliers found"
                subtitle="Add suppliers to link them with inventory purchases."
              />
            ) : (
              suppliers.map((sup) => (
                <TouchableOpacity
                  key={sup.id}
                  style={styles.card}
                  onPress={() => openSupplierDetails(sup)}
                >
                  <View style={styles.cardTop}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.cardTitle}>{sup.name}</Text>
                      <Text style={styles.cardSub}>
                        Contact: {sup.contact_person || 'N/A'} • {sup.phone || 'No phone'}
                      </Text>
                    </View>
                    <TouchableOpacity onPress={() => openSupplierForm(sup)}>
                      <Ionicons name="create-outline" size={20} color={COLORS.secondary} />
                    </TouchableOpacity>
                  </View>
                  <Text style={styles.cardFooter}>
                    {sup.purchases_count} recorded purchase order(s)
                  </Text>
                </TouchableOpacity>
              ))
            )}
          </>
        )}
      </ScrollView>

      {/* Supplier Purchase History Modal */}
      <Modal
        visible={!!selectedSupplier}
        transparent
        animationType="slide"
        onRequestClose={() => setSelectedSupplier(null)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            {selectedSupplier && (
              <ScrollView>
                <View style={styles.cardTop}>
                  <Text style={styles.cardTitle}>{selectedSupplier.name}</Text>
                  <TouchableOpacity onPress={() => setSelectedSupplier(null)}>
                    <Ionicons name="close-circle" size={26} color={COLORS.textSecondary} />
                  </TouchableOpacity>
                </View>
                <Text style={styles.cardSub}>
                  Contact: {selectedSupplier.contact_person || 'N/A'} | Phone:{' '}
                  {selectedSupplier.phone || 'N/A'}
                </Text>
                <Text style={styles.cardSub}>
                  Email: {selectedSupplier.email || 'N/A'} | Address:{' '}
                  {selectedSupplier.address || 'N/A'}
                </Text>
                <Text style={[styles.cardTitle, { marginTop: 14, marginBottom: 8 }]}>
                  Supplier Purchase Records ({supplierPurchases.length})
                </Text>
                {supplierPurchases.length === 0 ? (
                  <Text style={styles.cardSub}>No purchases recorded for this supplier yet.</Text>
                ) : (
                  supplierPurchases.map((po) => (
                    <View key={po.id} style={styles.lineFormBox}>
                      <View style={styles.cardTop}>
                        <Text style={styles.cardTitle}>{po.purchase_number}</Text>
                        <Text style={styles.totalAmount}>₱{po.total_amount.toFixed(2)}</Text>
                      </View>
                      <Text style={styles.cardSub}>Date: {po.purchase_date}</Text>
                    </View>
                  ))
                )}
              </ScrollView>
            )}
          </View>
        </View>
      </Modal>

      {/* Add/Edit Supplier Modal */}
      <Modal
        visible={supplierFormVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setSupplierFormVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <ScrollView keyboardShouldPersistTaps="handled">
              <View style={[styles.cardTop, { marginBottom: 12 }]}>
                <Text style={styles.cardTitle}>
                  {editingSupplier ? 'Edit Supplier' : 'Add Supplier'}
                </Text>
                <TouchableOpacity onPress={() => setSupplierFormVisible(false)}>
                  <Ionicons name="close-circle" size={26} color={COLORS.textSecondary} />
                </TouchableOpacity>
              </View>
              <FormField label="Supplier Name" value={supName} onChangeText={setSupName} required />
              <FormField
                label="Contact Person"
                value={supContact}
                onChangeText={setSupContact}
              />
              <FormField
                label="Phone"
                value={supPhone}
                onChangeText={setSupPhone}
                keyboardType="phone-pad"
              />
              <FormField
                label="Email"
                value={supEmail}
                onChangeText={setSupEmail}
                keyboardType="email-address"
              />
              <FormField label="Address" value={supAddress} onChangeText={setSupAddress} />
              <FormField label="Notes" value={supNotes} onChangeText={setSupNotes} multiline />
              <PrimaryButton
                title={editingSupplier ? 'Update Supplier' : 'Save Supplier'}
                onPress={handleSaveSupplier}
                loading={poSubmitting}
              />
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  content: { padding: 16, paddingBottom: 32 },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 10,
  },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  cardTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  cardSub: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginTop: 2,
  },
  totalAmount: {
    color: COLORS.secondary,
    fontSize: 15,
    fontWeight: '700',
  },
  linesBox: {
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  lineText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginBottom: 3,
  },
  cardFooter: {
    color: COLORS.textMuted,
    fontSize: 11,
    marginTop: 6,
  },
  linesHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginVertical: 10,
  },
  addLineBtn: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  addLineText: {
    color: COLORS.secondary,
    fontSize: 13,
    fontWeight: '700',
    marginLeft: 4,
  },
  lineFormBox: {
    backgroundColor: COLORS.navBackground,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 12,
    marginBottom: 10,
  },
  grandTotalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginVertical: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
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
    maxHeight: '85%',
  },
});

