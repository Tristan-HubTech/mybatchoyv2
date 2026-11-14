import React, { useCallback, useEffect, useState } from 'react';
import {
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { apiRequest } from '../services/api';
import { COLORS } from '../theme/colors';
import {
  BannerMessage,
  ConfirmModal,
  FilterChips,
  FormField,
  PrimaryButton,
} from '../components/UI';

export const SettingsProfileScreen: React.FC = () => {
  const { user, logout, refreshProfile } = useAuth();
  const isOwner = user?.role === 'owner';

  const [tab, setTab] = useState<'profile' | 'business' | 'backup'>('profile');

  // Profile fields
  const [name, setName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [phone, setPhone] = useState(user?.phone || '');

  // Password fields
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  // Business settings fields (Owner only)
  const [businessName, setBusinessName] = useState('MyBatchoy');
  const [bizPhone, setBizPhone] = useState('');
  const [bizEmail, setBizEmail] = useState('');
  const [bizAddress, setBizAddress] = useState('');
  const [sessionTimeout, setSessionTimeout] = useState('60');
  const [defaultMinStock, setDefaultMinStock] = useState('0');
  const [defaultReorder, setDefaultReorder] = useState('0');
  const [systemInfo, setSystemInfo] = useState<any>(null);
  const [backupData, setBackupData] = useState<any>(null);

  const [newCategoryName, setNewCategoryName] = useState('');
  const [newUnitName, setNewUnitName] = useState('');
  const [newUnitShort, setNewUnitShort] = useState('');

  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [logoutModal, setLogoutModal] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const loadOwnerSettings = useCallback(async () => {
    if (!isOwner) return;
    try {
      const [settingsRes, backupRes] = await Promise.all([
        apiRequest('/settings'),
        apiRequest('/backup'),
      ]);
      const s = settingsRes.settings;
      if (s) {
        setBusinessName(s.business_name || '');
        setBizPhone(s.phone || '');
        setBizEmail(s.email || '');
        setBizAddress(s.address || '');
        setSessionTimeout(String(s.session_timeout_minutes ?? 60));
        setDefaultMinStock(String(s.default_minimum_stock_level ?? 0));
        setDefaultReorder(String(s.default_reorder_level ?? 0));
      }
      setSystemInfo(settingsRes.system_info);
      setBackupData(backupRes);
    } catch {
      // Ignore if not owner
    }
  }, [isOwner]);

  useEffect(() => {
    loadOwnerSettings();
  }, [loadOwnerSettings]);

  const handleUpdateProfile = async () => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiRequest<{ message: string }>('/profile', {
        method: 'PUT',
        body: {
          name: name.trim(),
          email: email.trim(),
          phone: phone.trim() || null,
        },
      });
      await refreshProfile();
      setSuccess(res.message);
    } catch (err: any) {
      setError(err?.message || 'Failed to update profile.');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdatePassword = async () => {
    if (!currentPassword || !newPassword) {
      setError('Enter your current password and new password.');
      return;
    }
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiRequest<{ message: string }>('/profile/password', {
        method: 'PUT',
        body: {
          current_password: currentPassword,
          password: newPassword,
          password_confirmation: confirmPassword,
        },
      });
      setCurrentPassword('');
      setNewPassword('');
      setConfirmPassword('');
      setSuccess(res.message);
    } catch (err: any) {
      setError(err?.message || 'Failed to change password.');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateBusinessSettings = async () => {
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiRequest<{ message: string }>('/settings', {
        method: 'PUT',
        body: {
          business_name: businessName.trim(),
          phone: bizPhone.trim() || null,
          email: bizEmail.trim() || null,
          address: bizAddress.trim() || null,
          session_timeout_minutes: Number(sessionTimeout || 60),
          default_minimum_stock_level: Number(defaultMinStock || 0),
          default_reorder_level: Number(defaultReorder || 0),
        },
      });
      await refreshProfile();
      setSuccess(res.message);
    } catch (err: any) {
      setError(err?.message || 'Failed to update business settings.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateCategory = async () => {
    if (!newCategoryName.trim()) return;
    setLoading(true);
    setError(null);
    try {
      await apiRequest('/settings/categories', {
        method: 'POST',
        body: { name: newCategoryName.trim() },
      });
      setNewCategoryName('');
      setSuccess('Inventory category added.');
    } catch (err: any) {
      setError(err?.message || 'Unable to add category.');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateUnit = async () => {
    if (!newUnitName.trim() || !newUnitShort.trim()) return;
    setLoading(true);
    setError(null);
    try {
      await apiRequest('/settings/units', {
        method: 'POST',
        body: { name: newUnitName.trim(), short_name: newUnitShort.trim() },
      });
      setNewUnitName('');
      setNewUnitShort('');
      setSuccess('Measurement unit added.');
    } catch (err: any) {
      setError(err?.message || 'Unable to add unit.');
    } finally {
      setLoading(false);
    }
  };

  const tabs = isOwner
    ? [
        { key: 'profile', label: 'My Profile & Password' },
        { key: 'business', label: 'Business Settings' },
        { key: 'backup', label: 'System & Backup' },
      ]
    : [{ key: 'profile', label: 'My Profile & Password' }];

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={
        <RefreshControl
          refreshing={refreshing}
          onRefresh={async () => {
            setRefreshing(true);
            await refreshProfile();
            await loadOwnerSettings();
            setRefreshing(false);
          }}
          tintColor={COLORS.primary}
        />
      }
      keyboardShouldPersistTaps="handled"
    >
      <FilterChips
        options={tabs}
        selected={tab}
        onSelect={(k) => {
          setTab(k as any);
          setError(null);
        }}
      />

      <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
      <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

      {tab === 'profile' && (
        <>
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Account Profile</Text>
            <Text style={styles.cardSub}>
              Username: @{user?.username} • Role: {user?.role_label}
            </Text>

            <View style={{ marginTop: 12 }}>
              <FormField label="Full Name" value={name} onChangeText={setName} required />
              <FormField
                label="Email Address"
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
              <PrimaryButton
                title="Update Profile"
                icon="save-outline"
                onPress={handleUpdateProfile}
                loading={loading}
              />
            </View>
          </View>

          <View style={styles.card}>
            <Text style={styles.cardTitle}>Change Password</Text>
            <Text style={[styles.cardSub, { marginBottom: 12 }]}>
              Minimum 12 characters with uppercase, lowercase, number, and symbol.
            </Text>
            <FormField
              label="Current Password"
              value={currentPassword}
              onChangeText={setCurrentPassword}
              secureTextEntry
              required
            />
            <FormField
              label="New Password"
              value={newPassword}
              onChangeText={setNewPassword}
              secureTextEntry
              required
            />
            <FormField
              label="Confirm New Password"
              value={confirmPassword}
              onChangeText={setConfirmPassword}
              secureTextEntry
              required
            />
            <PrimaryButton
              title="Change Password"
              icon="lock-closed-outline"
              onPress={handleUpdatePassword}
              loading={loading}
            />
          </View>

          <PrimaryButton
            title="Sign Out of Account"
            icon="log-out-outline"
            variant="danger"
            onPress={() => setLogoutModal(true)}
            style={{ marginTop: 6 }}
          />
        </>
      )}

      {isOwner && tab === 'business' && (
        <>
          <View style={styles.card}>
            <Text style={styles.cardTitle}>Business & Security Configuration</Text>
            <FormField
              label="Business Name"
              value={businessName}
              onChangeText={setBusinessName}
              required
            />
            <FormField
              label="Business Phone"
              value={bizPhone}
              onChangeText={setBizPhone}
              keyboardType="phone-pad"
            />
            <FormField
              label="Business Email"
              value={bizEmail}
              onChangeText={setBizEmail}
              keyboardType="email-address"
            />
            <FormField label="Address" value={bizAddress} onChangeText={setBizAddress} />
            <FormField
              label="Session Timeout (Minutes: 5–240)"
              value={sessionTimeout}
              onChangeText={setSessionTimeout}
              keyboardType="numeric"
              required
            />
            <FormField
              label="Default Minimum Stock Level"
              value={defaultMinStock}
              onChangeText={setDefaultMinStock}
              keyboardType="decimal-pad"
              required
            />
            <FormField
              label="Default Reorder Level"
              value={defaultReorder}
              onChangeText={setDefaultReorder}
              keyboardType="decimal-pad"
              required
            />
            <PrimaryButton
              title="Save Business Settings"
              onPress={handleUpdateBusinessSettings}
              loading={loading}
            />
          </View>

          <View style={styles.card}>
            <Text style={styles.cardTitle}>Quick Add Category & Measurement Unit</Text>
            <FormField
              label="New Category Name"
              value={newCategoryName}
              onChangeText={setNewCategoryName}
              placeholder="e.g. Condiments"
            />
            <PrimaryButton
              title="Add Category"
              variant="outline"
              onPress={handleCreateCategory}
              style={{ marginBottom: 14 }}
            />
            <FormField
              label="New Unit Name"
              value={newUnitName}
              onChangeText={setNewUnitName}
              placeholder="e.g. Liters"
            />
            <FormField
              label="Unit Short Name"
              value={newUnitShort}
              onChangeText={setNewUnitShort}
              placeholder="e.g. L"
            />
            <PrimaryButton title="Add Unit" variant="outline" onPress={handleCreateUnit} />
          </View>
        </>
      )}

      {isOwner && tab === 'backup' && (
        <View style={styles.card}>
          <Text style={styles.cardTitle}>System Diagnostics & Database Status</Text>
          <Text style={styles.cardSub}>
            Owner MFA Status: {systemInfo?.owner_mfa_status || 'Not enabled; no MFA flow is configured'}
          </Text>
          <Text style={styles.cardSub}>
            Database Status: {backupData?.system_info?.database_status || 'Connected'} (
            {backupData?.system_info?.database})
          </Text>
          <Text style={styles.cardSub}>
            Total Tables: {backupData?.system_info?.tables_count ?? 0} • Total Rows:{' '}
            {backupData?.system_info?.total_rows ?? 0}
          </Text>

          <Text style={[styles.cardTitle, { marginTop: 14, marginBottom: 6 }]}>
            Database Tables
          </Text>
          {(backupData?.tables || []).map((t: any, i: number) => (
            <View key={i} style={styles.tableRow}>
              <Text style={styles.cardSub}>{t.name}</Text>
              <Text style={{ color: COLORS.text, fontSize: 12, fontWeight: '700' }}>
                {t.rows} rows
              </Text>
            </View>
          ))}
        </View>
      )}

      <ConfirmModal
        visible={logoutModal}
        title="Sign Out"
        message="Are you sure you want to sign out of your MyBatchoy account on this device?"
        confirmText="Sign Out"
        confirmVariant="danger"
        onConfirm={logout}
        onCancel={() => setLogoutModal(false)}
      />
    </ScrollView>
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
    padding: 15,
    marginBottom: 12,
  },
  cardTitle: { color: COLORS.text, fontSize: 15, fontWeight: '700', marginBottom: 4 },
  cardSub: { color: COLORS.textSecondary, fontSize: 12, marginTop: 2 },
  tableRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
    borderBottomWidth: 1,
    borderBottomColor: COLORS.border,
  },
});

