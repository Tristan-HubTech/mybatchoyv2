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
import { AuditLogModel, UserProfile } from '../types/models';
import {
  BannerMessage,
  ConfirmModal,
  EmptyState,
  FilterChips,
  FormField,
  PrimaryButton,
  SearchBar,
  SkeletonList,
  StatusBadge,
} from '../components/UI';

const SECTION_TABS = [
  { key: 'staff', label: 'Staff Accounts' },
  { key: 'audit', label: 'Audit Logs' },
];

export const StaffAuditScreen: React.FC = () => {
  const [section, setSection] = useState<'staff' | 'audit'>('staff');
  const [staffList, setStaffList] = useState<UserProfile[]>([]);
  const [auditLogs, setAuditLogs] = useState<AuditLogModel[]>([]);

  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  // Staff Create/Edit Modal
  const [staffFormVisible, setStaffFormVisible] = useState(false);
  const [editingStaff, setEditingStaff] = useState<UserProfile | null>(null);
  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [idempotencyKey, setIdempotencyKey] = useState(generateIdempotencyKey());

  // Password Reset Modal
  const [resetTarget, setResetTarget] = useState<UserProfile | null>(null);
  const [newPass, setNewPass] = useState('');
  const [newPassConfirm, setNewPassConfirm] = useState('');

  // Status Toggle Confirmation
  const [statusTarget, setStatusTarget] = useState<UserProfile | null>(null);

  const loadData = useCallback(
    async (isRefresh = false) => {
      if (isRefresh) setRefreshing(true);
      else setLoading(true);
      setError(null);

      try {
        if (section === 'staff') {
          const res = await apiRequest<{ staff: UserProfile[] }>('/staff', {
            params: {
              search: search.trim() || undefined,
              status: statusFilter !== 'all' ? statusFilter : undefined,
            },
          });
          setStaffList(res.staff);
        } else {
          const res = await apiRequest<{ logs: AuditLogModel[] }>('/audit-logs', {
            params: { search: search.trim() || undefined },
          });
          setAuditLogs(res.logs);
        }
      } catch (err: any) {
        setError(err?.message || 'Failed to load data.');
      } finally {
        setLoading(false);
        setRefreshing(false);
      }
    },
    [section, search, statusFilter]
  );

  useEffect(() => {
    loadData();
  }, [loadData]);

  const openStaffForm = (member?: UserProfile) => {
    setEditingStaff(member || null);
    setName(member?.name || '');
    setUsername(member?.username || '');
    setEmail(member?.email || '');
    setPhone(member?.phone || '');
    setPassword('');
    setPasswordConfirm('');
    setIdempotencyKey(generateIdempotencyKey());
    setStaffFormVisible(true);
  };

  const handleSaveStaff = async () => {
    if (!name.trim() || !username.trim() || !email.trim()) {
      setError('Name, username, and email are required.');
      return;
    }
    setSubmitting(true);
    setError(null);
    try {
      if (editingStaff) {
        await apiRequest(`/staff/${editingStaff.id}`, {
          method: 'PUT',
          idempotencyKey,
          body: {
            name: name.trim(),
            username: username.trim(),
            email: email.trim(),
            phone: phone.trim() || null,
          },
        });
        setSuccess('Staff account updated.');
      } else {
        await apiRequest('/staff', {
          method: 'POST',
          idempotencyKey,
          body: {
            name: name.trim(),
            username: username.trim(),
            email: email.trim(),
            phone: phone.trim() || null,
            password,
            password_confirmation: passwordConfirm,
            is_active: true,
          },
        });
        setSuccess('Staff account created.');
      }
      setStaffFormVisible(false);
      await loadData(true);
    } catch (err: any) {
      setError(err?.message || 'Failed to save staff account.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleToggleStatus = async () => {
    if (!statusTarget) return;
    setSubmitting(true);
    setError(null);
    try {
      await apiRequest(`/staff/${statusTarget.id}/status`, {
        method: 'PATCH',
        body: { is_active: !statusTarget.is_active },
      });
      setSuccess(
        `Staff account ${!statusTarget.is_active ? 'activated' : 'deactivated'} successfully.`
      );
      setStatusTarget(null);
      await loadData(true);
    } catch (err: any) {
      setError(err?.message || 'Unable to update staff status.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleResetStaffPassword = async () => {
    if (!resetTarget) return;
    setSubmitting(true);
    setError(null);
    try {
      await apiRequest(`/staff/${resetTarget.id}/password`, {
        method: 'PUT',
        body: {
          password: newPass,
          password_confirmation: newPassConfirm,
        },
      });
      setSuccess(`Password reset for ${resetTarget.name}. Active mobile sessions revoked.`);
      setResetTarget(null);
      setNewPass('');
      setNewPassConfirm('');
    } catch (err: any) {
      setError(err?.message || 'Password reset failed.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => loadData(true)}
            tintColor={COLORS.primary}
          />
        }
      >
        <FilterChips
          options={SECTION_TABS}
          selected={section}
          onSelect={(k) => {
            setSection(k as 'staff' | 'audit');
            setSearch('');
            setError(null);
          }}
        />

        <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
        <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

        {section === 'staff' ? (
          <>
            <View style={styles.topRow}>
              <Text style={styles.heading}>Staff Accounts ({staffList.length})</Text>
              <PrimaryButton
                title="Add Staff"
                icon="person-add-outline"
                onPress={() => openStaffForm()}
                style={{ minHeight: 38, paddingVertical: 6 }}
              />
            </View>

            <SearchBar
              value={search}
              onChangeText={setSearch}
              placeholder="Search staff name, username, or email..."
            />

            <FilterChips
              options={[
                { key: 'all', label: 'All Staff' },
                { key: 'active', label: 'Active' },
                { key: 'inactive', label: 'Inactive' },
              ]}
              selected={statusFilter}
              onSelect={setStatusFilter}
            />

            {loading && staffList.length === 0 ? (
              <SkeletonList count={4} height={95} />
            ) : staffList.length === 0 ? (
              <EmptyState icon="people-outline" title="No staff accounts found" />
            ) : (
              staffList.map((member) => (
                <View key={member.id} style={styles.card}>
                  <View style={styles.rowBetween}>
                    <View style={{ flex: 1 }}>
                      <Text style={styles.cardTitle}>{member.name}</Text>
                      <Text style={styles.cardSub}>
                        @{member.username} • {member.email}
                      </Text>
                    </View>
                    <StatusBadge status={member.is_active ? 'Available' : 'Inactive'} />
                  </View>

                  <View style={styles.staffActionRow}>
                    <TouchableOpacity
                      style={styles.smallBtn}
                      onPress={() => openStaffForm(member)}
                    >
                      <Ionicons name="create-outline" size={16} color={COLORS.text} />
                      <Text style={styles.smallBtnText}>Edit</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={styles.smallBtn}
                      onPress={() => {
                        setResetTarget(member);
                        setNewPass('');
                        setNewPassConfirm('');
                      }}
                    >
                      <Ionicons name="key-outline" size={16} color={COLORS.secondary} />
                      <Text style={[styles.smallBtnText, { color: COLORS.secondary }]}>
                        Reset Password
                      </Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={styles.smallBtn}
                      onPress={() => setStatusTarget(member)}
                    >
                      <Ionicons
                        name={member.is_active ? 'ban-outline' : 'checkmark-circle-outline'}
                        size={16}
                        color={member.is_active ? COLORS.danger : COLORS.success}
                      />
                      <Text
                        style={[
                          styles.smallBtnText,
                          { color: member.is_active ? COLORS.danger : COLORS.success },
                        ]}
                      >
                        {member.is_active ? 'Deactivate' : 'Activate'}
                      </Text>
                    </TouchableOpacity>
                  </View>
                </View>
              ))
            )}
          </>
        ) : (
          <>
            <Text style={[styles.heading, { marginBottom: 10 }]}>
              System Audit Logs (Immutable)
            </Text>
            <SearchBar
              value={search}
              onChangeText={setSearch}
              placeholder="Search action, user, or details..."
            />

            {loading && auditLogs.length === 0 ? (
              <SkeletonList count={5} height={82} />
            ) : auditLogs.length === 0 ? (
              <EmptyState icon="shield-checkmark-outline" title="No audit logs found" />
            ) : (
              auditLogs.map((log) => (
                <View key={log.id} style={styles.card}>
                  <View style={styles.rowBetween}>
                    <Text style={[styles.cardTitle, { color: COLORS.secondary }]}>
                      {log.action}
                    </Text>
                    <Text style={styles.cardSub}>
                      {log.created_at ? new Date(log.created_at).toLocaleString() : ''}
                    </Text>
                  </View>
                  <Text style={[styles.cardSub, { color: COLORS.text, marginTop: 4 }]}>
                    {log.details}
                  </Text>
                  <Text style={styles.cardSub}>
                    User: {log.user_name} {log.ip_address ? `• IP: ${log.ip_address}` : ''}
                  </Text>
                </View>
              ))
            )}
          </>
        )}
      </ScrollView>

      {/* Add/Edit Staff Modal */}
      <Modal
        visible={staffFormVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setStaffFormVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <ScrollView keyboardShouldPersistTaps="handled">
              <View style={[styles.rowBetween, { marginBottom: 12 }]}>
                <Text style={styles.heading}>
                  {editingStaff ? 'Edit Staff Account' : 'Add Staff Account'}
                </Text>
                <TouchableOpacity onPress={() => setStaffFormVisible(false)}>
                  <Ionicons name="close-circle" size={26} color={COLORS.textSecondary} />
                </TouchableOpacity>
              </View>
              <FormField label="Full Name" value={name} onChangeText={setName} required />
              <FormField label="Username" value={username} onChangeText={setUsername} required />
              <FormField
                label="Email"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                required
              />
              <FormField
                label="Phone"
                value={phone}
                onChangeText={setPhone}
                keyboardType="phone-pad"
              />
              {!editingStaff && (
                <>
                  <FormField
                    label="Password (min 12 chars, upper/lower, number, symbol)"
                    value={password}
                    onChangeText={setPassword}
                    secureTextEntry
                    required
                  />
                  <FormField
                    label="Confirm Password"
                    value={passwordConfirm}
                    onChangeText={setPasswordConfirm}
                    secureTextEntry
                    required
                  />
                </>
              )}
              <PrimaryButton
                title={editingStaff ? 'Save Changes' : 'Create Staff Account'}
                onPress={handleSaveStaff}
                loading={submitting}
              />
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* Reset Staff Password Modal */}
      <Modal
        visible={!!resetTarget}
        transparent
        animationType="fade"
        onRequestClose={() => setResetTarget(null)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalSheet}>
            <Text style={styles.heading}>Reset Password: {resetTarget?.name}</Text>
            <Text style={[styles.cardSub, { marginVertical: 8 }]}>
              Resetting a staff password immediately invalidates their active web and mobile
              sessions.
            </Text>
            <FormField
              label="New Password (min 12 chars, mixed case, number, symbol)"
              value={newPass}
              onChangeText={setNewPass}
              secureTextEntry
              required
            />
            <FormField
              label="Confirm New Password"
              value={newPassConfirm}
              onChangeText={setNewPassConfirm}
              secureTextEntry
              required
            />
            <View style={{ flexDirection: 'row', marginTop: 8 }}>
              <PrimaryButton
                title="Cancel"
                variant="outline"
                onPress={() => setResetTarget(null)}
                style={{ flex: 1, marginRight: 8 }}
              />
              <PrimaryButton
                title="Reset Password"
                variant="danger"
                onPress={handleResetStaffPassword}
                loading={submitting}
                style={{ flex: 1 }}
              />
            </View>
          </View>
        </View>
      </Modal>

      <ConfirmModal
        visible={!!statusTarget}
        title={statusTarget?.is_active ? 'Deactivate Staff Account' : 'Activate Staff Account'}
        message={
          statusTarget?.is_active
            ? `Deactivate ${statusTarget.name}? They will be signed out of all devices immediately.`
            : `Reactivate ${statusTarget?.name} so they can sign in again?`
        }
        confirmText={statusTarget?.is_active ? 'Deactivate' : 'Activate'}
        confirmVariant={statusTarget?.is_active ? 'danger' : 'primary'}
        loading={submitting}
        onConfirm={handleToggleStatus}
        onCancel={() => setStatusTarget(null)}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: COLORS.background },
  content: { padding: 16, paddingBottom: 32 },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  heading: { color: COLORS.text, fontSize: 17, fontWeight: '700' },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 14,
    marginBottom: 9,
  },
  rowBetween: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  cardTitle: { color: COLORS.text, fontSize: 14, fontWeight: '700' },
  cardSub: { color: COLORS.textSecondary, fontSize: 12, marginTop: 2 },
  staffActionRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: COLORS.border,
  },
  smallBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 4,
    paddingHorizontal: 8,
  },
  smallBtnText: {
    color: COLORS.text,
    fontSize: 12,
    fontWeight: '600',
    marginLeft: 4,
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

