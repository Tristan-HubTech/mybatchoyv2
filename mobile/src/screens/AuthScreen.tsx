import React, { useEffect, useState } from 'react';
import {
  Image,
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
import {
  IS_DEV_MODE,
  apiRequest,
  getApiBaseUrl,
  getApiPresets,
  setApiBaseUrl,
  testApiConnection,
} from '../services/api';
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
  const [testingUrl, setTestingUrl] = useState(false);
  const [serverTestMsg, setServerTestMsg] = useState<{ ok: boolean; text: string } | null>(null);
  const presets = IS_DEV_MODE ? getApiPresets() : [];

  useEffect(() => {
    getApiBaseUrl()
      .then(setServerUrl)
      .catch((err: any) => {
        setServerUrl('');
        if (!IS_DEV_MODE) {
          setError(err?.message || 'App build is missing a valid HTTPS EXPO_PUBLIC_API_URL.');
        }
      });
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
    setTestingUrl(true);
    setServerTestMsg(null);
    const saved = await setApiBaseUrl(serverUrl);
    setServerUrl(saved);
    const result = await testApiConnection(saved);
    setTestingUrl(false);

    if (result.ok) {
      setServerModalVisible(false);
      setError(null);
      setSuccess(result.message);
    } else {
      setServerTestMsg({ ok: false, text: result.message });
    }
  };

  const handleTestOnly = async () => {
    setTestingUrl(true);
    setServerTestMsg(null);
    const result = await testApiConnection(serverUrl);
    setServerUrl(result.url);
    setTestingUrl(false);
    setServerTestMsg({ ok: result.ok, text: result.message });
  };

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
        <View style={styles.brandHeader}>
          <View style={styles.logoCircle}>
            <Image source={require('../../assets/eustace-mascot.jpg')} style={styles.logoImg} />
          </View>
          <View style={{ flexDirection: 'row', alignItems: 'baseline', gap: 5 }}>
            <Text style={styles.brandScript}>Eustace</Text>
            <Text style={styles.brandTitle}>BATCHOY</Text>
          </View>
          <View style={styles.unliBadge}>
            <Text style={styles.unliText}>UNLI SABAW</Text>
          </View>
          <Text style={styles.brandSubtitle}>MyBatchoy Inventory Management System</Text>
        </View>

        <View style={styles.card}>
          <View style={styles.cardTopAccent} />
          <View style={styles.cardTopRow}>
            <Text style={styles.cardTitle}>
              {mode === 'login'
                ? 'Sign In to Account'
                : mode === 'forgot'
                ? 'Password Recovery'
                : 'Enter 6-Digit Reset Code'}
            </Text>
            {IS_DEV_MODE && (
              <TouchableOpacity
                onPress={() => {
                  setServerTestMsg(null);
                  setServerModalVisible(true);
                }}
                style={styles.serverBtn}
              >
                <Ionicons name="server-outline" size={16} color={COLORS.secondary} />
                <Text style={styles.serverBtnText}>API URL</Text>
              </TouchableOpacity>
            )}
          </View>

          {IS_DEV_MODE && serverUrl ? (
            <TouchableOpacity
              onPress={() => {
                setServerTestMsg(null);
                setServerModalVisible(true);
              }}
              style={styles.activeServerRow}
            >
              <Ionicons name="radio-button-on" size={12} color={COLORS.success} style={{ marginRight: 6 }} />
              <Text style={styles.activeServerText} numberOfLines={1}>
                API: {serverUrl}
              </Text>
            </TouchableOpacity>
          ) : null}

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

      {IS_DEV_MODE && (
        <Modal
          visible={serverModalVisible}
          transparent
          animationType="fade"
          onRequestClose={() => setServerModalVisible(false)}
        >
          <View style={styles.modalBackdrop}>
            <View style={styles.modalCard}>
              <Text style={styles.modalTitle}>Backend API URL</Text>
              <Text style={styles.helperText}>
                Pick a quick preset or paste your server URL. Missing /public/api/v1 is fixed automatically.
              </Text>

              <View style={styles.presetGrid}>
                {presets.map((preset) => {
                  const selected = serverUrl === preset.url;
                  return (
                    <TouchableOpacity
                      key={preset.label}
                      style={[styles.presetBtn, selected && styles.presetBtnActive]}
                      onPress={() => {
                        setServerUrl(preset.url);
                        setServerTestMsg(null);
                      }}
                    >
                      <Text style={[styles.presetLabel, selected && styles.presetLabelActive]}>
                        {preset.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              <FormField
                label="Server /api/v1 Endpoint"
                value={serverUrl}
                onChangeText={setServerUrl}
                placeholder="http://localhost/Mybatchoy/public/api/v1"
              />

              {serverTestMsg && (
                <View
                  style={[
                    styles.testMsgBox,
                    {
                      borderColor: serverTestMsg.ok ? COLORS.success : COLORS.danger,
                      backgroundColor: serverTestMsg.ok
                        ? 'rgba(76, 175, 80, 0.14)'
                        : 'rgba(229, 57, 53, 0.16)',
                    },
                  ]}
                >
                  <Text style={{ color: '#FFFFFF', fontSize: 12, lineHeight: 17 }}>
                    {serverTestMsg.text}
                  </Text>
                </View>
              )}

              <View style={{ flexDirection: 'row', marginTop: 8 }}>
                <PrimaryButton
                  title="Cancel"
                  variant="outline"
                  onPress={() => setServerModalVisible(false)}
                  disabled={testingUrl}
                  style={{ flex: 1, marginRight: 6 }}
                />
                <PrimaryButton
                  title="Test"
                  variant="secondary"
                  onPress={handleTestOnly}
                  loading={testingUrl}
                  style={{ flex: 1, marginRight: 6 }}
                />
                <PrimaryButton
                  title="Save"
                  onPress={handleSaveServerUrl}
                  loading={testingUrl}
                  style={{ flex: 1 }}
                />
              </View>
            </View>
          </View>
        </Modal>
      )}
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
    width: 72,
    height: 72,
    borderRadius: 36,
    borderWidth: 2,
    borderColor: COLORS.secondary,
    overflow: 'hidden',
    backgroundColor: COLORS.card,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
  },
  logoImg: {
    width: '100%',
    height: '100%',
  },
  brandScript: {
    color: COLORS.text,
    fontSize: 22,
    fontStyle: 'italic',
    fontWeight: '700',
  },
  brandTitle: {
    color: COLORS.secondary,
    fontSize: 24,
    fontWeight: '800',
    letterSpacing: 0.6,
  },
  unliBadge: {
    backgroundColor: COLORS.primary,
    borderRadius: 5,
    paddingHorizontal: 8,
    paddingVertical: 2,
    marginTop: 4,
    marginBottom: 4,
  },
  unliText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 1.1,
  },
  brandSubtitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    marginTop: 2,
  },
  card: {
    backgroundColor: COLORS.card,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#333333',
    padding: 20,
    overflow: 'hidden',
  },
  cardTopAccent: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 4,
    backgroundColor: COLORS.primary,
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
  activeServerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.navBackground,
    borderWidth: 1,
    borderColor: '#2A2A2A',
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 6,
    marginBottom: 12,
  },
  activeServerText: {
    flex: 1,
    color: COLORS.textSecondary,
    fontSize: 11,
    fontWeight: '600',
  },
  presetGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 6,
    marginBottom: 12,
  },
  presetBtn: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
    backgroundColor: COLORS.navBackground,
  },
  presetBtnActive: {
    borderColor: COLORS.secondary,
    backgroundColor: 'rgba(255, 193, 7, 0.14)',
  },
  presetLabel: {
    color: COLORS.textSecondary,
    fontSize: 11,
    fontWeight: '700',
  },
  presetLabelActive: {
    color: COLORS.secondary,
  },
  testMsgBox: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 9,
    marginBottom: 8,
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

