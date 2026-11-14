import React, { useEffect, useState } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuth } from '../context/AuthContext';
import { apiRequest, getApiBaseUrl, setApiBaseUrl } from '../services/api';
import { COLORS } from '../theme/colors';
import { BannerMessage, FormField, PrimaryButton } from '../components/UI';

type AuthMode = 'login' | 'forgot' | 'reset';

export const AuthScreen: React.FC = () => {
  const { login, sessionNotice, clearSessionNotice } = useAuth();
  const [mode, setMode] = useState<AuthMode>('login');

  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const [email, setEmail] = useState('');
  const [resetCode, setResetCode] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const [serverModalVisible, setServerModalVisible] = useState(false);
  const [serverUrl, setServerUrl] = useState('');

  useEffect(() => {
    getApiBaseUrl().then(setServerUrl);
  }, []);

  const handleLogin = async () => {
    if (!username.trim() || !password) {
      setError('Enter your username and password.');
      return;
    }
    setLoading(true);
    setError(null);
    setSuccess(null);
    clearSessionNotice();
    try {
      await login(username, password);
    } catch (err: any) {
      setError(err?.message || 'Sign in failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleForgotPassword = async () => {
    if (!email.trim()) {
      setError('Enter your registered email address.');
      return;
    }
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiRequest<{ message: string }>('/auth/forgot-password', {
        method: 'POST',
        body: { email: email.trim() },
        skipAuthRedirect: true,
      });
      setSuccess(res.message);
      setMode('reset');
    } catch (err: any) {
      setError(err?.message || 'Unable to request password reset code.');
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async () => {
    if (!email.trim() || resetCode.trim().length !== 6 || !newPassword) {
      setError('Complete your email, 6-digit code, and new password.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setError('Password confirmation does not match.');
      return;
    }
    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiRequest<{ message: string }>('/auth/reset-password', {
        method: 'POST',
        body: {
          email: email.trim(),
          code: resetCode.trim(),
          password: newPassword,
          password_confirmation: confirmPassword,
        },
        skipAuthRedirect: true,
      });
      setSuccess(res.message);
      setResetCode('');
      setNewPassword('');
      setConfirmPassword('');
      setMode('login');
    } catch (err: any) {
      setError(err?.message || 'Password reset failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleSaveServerUrl = async () => {
    const saved = await setApiBaseUrl(serverUrl);
    setServerUrl(saved);
    setServerModalVisible(false);
    setSuccess(`Connected API endpoint updated to ${saved}`);
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.brandHeader}>
          <View style={styles.logoCircle}>
            <Ionicons name="restaurant" size={32} color={COLORS.text} />
          </View>
          <Text style={styles.brandTitle}>MyBatchoy</Text>
          <Text style={styles.brandSubtitle}>Inventory Management System</Text>
        </View>

        <View style={styles.card}>
          <View style={styles.cardTopRow}>
            <Text style={styles.cardTitle}>
              {mode === 'login'
                ? 'Sign In to Account'
                : mode === 'forgot'
                ? 'Password Recovery'
                : 'Enter 6-Digit Reset Code'}
            </Text>
            <TouchableOpacity
              onPress={() => setServerModalVisible(true)}
              style={styles.serverBtn}
            >
              <Ionicons name="server-outline" size={16} color={COLORS.secondary} />
              <Text style={styles.serverBtnText}>API URL</Text>
            </TouchableOpacity>
          </View>

          <BannerMessage
            type="warning"
            message={sessionNotice}
            onDismiss={clearSessionNotice}
          />
          <BannerMessage type="error" message={error} onDismiss={() => setError(null)} />
          <BannerMessage type="success" message={success} onDismiss={() => setSuccess(null)} />

          {mode === 'login' && (
            <>
              <FormField
                label="Username"
                value={username}
                onChangeText={setUsername}
                placeholder="Enter your username"
                required
              />

              <View style={{ marginBottom: 14 }}>
                <Text style={styles.label}>
                  Password <Text style={{ color: COLORS.primary }}>*</Text>
                </Text>
                <View style={styles.passwordRow}>
                  <TextInput
                    value={password}
                    onChangeText={setPassword}
                    placeholder="Enter your password"
                    placeholderTextColor={COLORS.textMuted}
                    secureTextEntry={!showPassword}
                    autoCapitalize="none"
                    style={styles.passwordInput}
                  />
                  <TouchableOpacity
                    onPress={() => setShowPassword(!showPassword)}
                    style={styles.eyeBtn}
                  >
                    <Ionicons
                      name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                      size={20}
                      color={COLORS.textSecondary}
                    />
                  </TouchableOpacity>
                </View>
              </View>

              <PrimaryButton
                title="Sign In"
                icon="log-in-outline"
                onPress={handleLogin}
                loading={loading}
              />

              <TouchableOpacity
                onPress={() => {
                  setError(null);
                  setSuccess(null);
                  setMode('forgot');
                }}
                style={styles.linkButton}
              >
                <Text style={styles.linkText}>Forgot your password?</Text>
              </TouchableOpacity>
            </>
          )}

          {mode === 'forgot' && (
            <>
              <Text style={styles.helperText}>
                Enter your active account email. A 6-digit recovery code valid for 10 minutes will
                be sent.
              </Text>
              <FormField
                label="Email Address"
                value={email}
                onChangeText={setEmail}
                placeholder="name@example.com"
                keyboardType="email-address"
                required
              />
              <PrimaryButton
                title="Send 6-Digit Code"
                icon="mail-outline"
                onPress={handleForgotPassword}
                loading={loading}
              />
              <TouchableOpacity
                onPress={() => setMode('reset')}
                style={[styles.linkButton, { marginTop: 10 }]}
              >
                <Text style={[styles.linkText, { color: COLORS.secondary }]}>
                  Already have a 6-digit code?
                </Text>
              </TouchableOpacity>
              <TouchableOpacity onPress={() => setMode('login')} style={styles.linkButton}>
                <Text style={styles.linkText}>Back to Sign In</Text>
              </TouchableOpacity>
            </>
          )}

          {mode === 'reset' && (
            <>
              <FormField
                label="Email Address"
                value={email}
                onChangeText={setEmail}
                placeholder="name@example.com"
                keyboardType="email-address"
                required
              />
              <FormField
                label="6-Digit Verification Code"
                value={resetCode}
                onChangeText={setResetCode}
                placeholder="123456"
                keyboardType="numeric"
                required
              />
              <FormField
                label="New Password (min 12 chars, mixed case, number, symbol)"
                value={newPassword}
                onChangeText={setNewPassword}
                placeholder="New strong password"
                secureTextEntry
                required
              />
              <FormField
                label="Confirm New Password"
                value={confirmPassword}
                onChangeText={setConfirmPassword}
                placeholder="Repeat new password"
                secureTextEntry
                required
              />
              <PrimaryButton
                title="Reset Password"
                icon="key-outline"
                onPress={handleResetPassword}
                loading={loading}
              />
              <TouchableOpacity onPress={() => setMode('login')} style={styles.linkButton}>
                <Text style={styles.linkText}>Back to Sign In</Text>
              </TouchableOpacity>
            </>
          )}
        </View>
      </ScrollView>

      <Modal
        visible={serverModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setServerModalVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <Text style={styles.modalTitle}>Backend API URL (Expo Go)</Text>
            <Text style={styles.helperText}>
              On a physical phone using Expo Go, use your computer&apos;s Wi-Fi LAN IP (e.g.
              http://192.168.1.10/Mybatchoy/public/api/v1).
            </Text>
            <FormField
              label="Server /api/v1 Endpoint"
              value={serverUrl}
              onChangeText={setServerUrl}
              placeholder="http://192.168.1.10/Mybatchoy/public/api/v1"
            />
            <View style={{ flexDirection: 'row', marginTop: 8 }}>
              <PrimaryButton
                title="Cancel"
                variant="outline"
                onPress={() => setServerModalVisible(false)}
                style={{ flex: 1, marginRight: 8 }}
              />
              <PrimaryButton
                title="Save URL"
                onPress={handleSaveServerUrl}
                style={{ flex: 1 }}
              />
            </View>
          </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 20,
  },
  brandHeader: {
    alignItems: 'center',
    marginBottom: 24,
  },
  logoCircle: {
    width: 66,
    height: 66,
    borderRadius: 33,
    backgroundColor: COLORS.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  brandTitle: {
    color: COLORS.text,
    fontSize: 26,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  brandSubtitle: {
    color: COLORS.textSecondary,
    fontSize: 13,
    marginTop: 2,
  },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 20,
  },
  cardTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  cardTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '700',
  },
  serverBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.navBackground,
  },
  serverBtnText: {
    color: COLORS.secondary,
    fontSize: 11,
    fontWeight: '700',
    marginLeft: 4,
  },
  label: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 5,
  },
  passwordRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.navBackground,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 10,
  },
  passwordInput: {
    flex: 1,
    color: COLORS.text,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
  },
  eyeBtn: {
    paddingHorizontal: 12,
    paddingVertical: 10,
  },
  linkButton: {
    alignItems: 'center',
    paddingVertical: 10,
  },
  linkText: {
    color: COLORS.textSecondary,
    fontSize: 13,
    fontWeight: '600',
  },
  helperText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 18,
    marginBottom: 14,
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: COLORS.overlay,
    justifyContent: 'center',
    padding: 20,
  },
  modalCard: {
    backgroundColor: COLORS.card,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 18,
  },
  modalTitle: {
    color: COLORS.text,
    fontSize: 16,
    fontWeight: '700',
    marginBottom: 6,
  },
});

