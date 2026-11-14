import React, { useEffect, useRef } from 'react';
import {
  ActivityIndicator,
  Animated,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { COLORS } from '../theme/colors';

export const SkeletonBlock: React.FC<{ height?: number; width?: number | string; style?: any }> =
  React.memo(({ height = 72, width = '100%', style }) => {
    const opacity = useRef(new Animated.Value(0.35)).current;

    useEffect(() => {
      const loop = Animated.loop(
        Animated.sequence([
          Animated.timing(opacity, { toValue: 0.75, duration: 650, useNativeDriver: true }),
          Animated.timing(opacity, { toValue: 0.35, duration: 650, useNativeDriver: true }),
        ])
      );
      loop.start();
      return () => loop.stop();
    }, [opacity]);

    return (
      <Animated.View
        style={[
          {
            height,
            width,
            backgroundColor: COLORS.card,
            borderRadius: 12,
            borderWidth: 1,
            borderColor: COLORS.border,
            opacity,
            marginBottom: 10,
          },
          style,
        ]}
      />
    );
  });

export const SkeletonList: React.FC<{ count?: number; height?: number }> = React.memo(
  ({ count = 4, height = 84 }) => (
    <View style={{ paddingVertical: 6 }}>
      {Array.from({ length: count }).map((_, idx) => (
        <SkeletonBlock key={idx} height={height} />
      ))}
    </View>
  )
);

export const StatusBadge: React.FC<{
  status: 'Available' | 'Low Stock' | 'Out of Stock' | string;
}> = React.memo(({ status }) => {
  let bg = 'rgba(76, 175, 80, 0.16)';
  let border: string = COLORS.success;
  let color: string = COLORS.success;

  if (status === 'Out of Stock' || status === 'Inactive') {
    bg = 'rgba(229, 57, 53, 0.18)';
    border = COLORS.danger;
    color = COLORS.danger;
  } else if (status === 'Low Stock' || status === 'Warning') {
    bg = 'rgba(255, 193, 7, 0.18)';
    border = COLORS.warning;
    color = COLORS.warning;
  }

  return (
    <View style={[styles.badge, { backgroundColor: bg, borderColor: border }]}>
      <Text style={[styles.badgeText, { color }]}>{status}</Text>
    </View>
  );
});

export const BannerMessage: React.FC<{
  type: 'success' | 'error' | 'warning';
  message: string | null;
  onDismiss?: () => void;
}> = React.memo(({ type, message, onDismiss }) => {
  if (!message) return null;

  const palette =
    type === 'success'
      ? { bg: 'rgba(76, 175, 80, 0.16)', border: COLORS.success, icon: 'checkmark-circle' as const }
      : type === 'warning'
        ? { bg: 'rgba(255, 193, 7, 0.16)', border: COLORS.warning, icon: 'warning' as const }
        : { bg: 'rgba(229, 57, 53, 0.18)', border: COLORS.danger, icon: 'alert-circle' as const };

  return (
    <View style={[styles.banner, { backgroundColor: palette.bg, borderColor: palette.border }]}>
      <Ionicons name={palette.icon} size={20} color={palette.border} style={{ marginRight: 8 }} />
      <Text style={styles.bannerText}>{message}</Text>
      {onDismiss && (
        <TouchableOpacity onPress={onDismiss} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
          <Ionicons name="close" size={18} color={COLORS.textSecondary} />
        </TouchableOpacity>
      )}
    </View>
  );
});

export const SearchBar: React.FC<{
  value: string;
  onChangeText: (text: string) => void;
  placeholder?: string;
}> = React.memo(({ value, onChangeText, placeholder = 'Search...' }) => (
  <View style={styles.searchContainer}>
    <Ionicons name="search" size={18} color={COLORS.textSecondary} style={{ marginRight: 8 }} />
    <TextInput
      value={value}
      onChangeText={onChangeText}
      placeholder={placeholder}
      placeholderTextColor={COLORS.textMuted}
      style={styles.searchInput}
      autoCapitalize="none"
    />
    {value.length > 0 && (
      <TouchableOpacity onPress={() => onChangeText('')}>
        <Ionicons name="close-circle" size={18} color={COLORS.textSecondary} />
      </TouchableOpacity>
    )}
  </View>
));

export const FilterChips: React.FC<{
  options: { key: string; label: string }[];
  selected: string;
  onSelect: (key: string) => void;
}> = React.memo(({ options, selected, onSelect }) => (
  <ScrollView
    horizontal
    showsHorizontalScrollIndicator={false}
    contentContainerStyle={styles.chipsRow}
  >
    {options.map((opt) => {
      const active = opt.key === selected;
      return (
        <TouchableOpacity
          key={opt.key}
          onPress={() => onSelect(opt.key)}
          style={[styles.chip, active && styles.chipActive]}
        >
          <Text style={[styles.chipText, active && styles.chipTextActive]}>{opt.label}</Text>
        </TouchableOpacity>
      );
    })}
  </ScrollView>
));

export const MetricCard: React.FC<{
  label: string;
  value: string | number;
  icon: keyof typeof Ionicons.glyphMap;
  accentColor?: string;
  subtitle?: string;
}> = React.memo(({ label, value, icon, accentColor = COLORS.primary, subtitle }) => (
  <View style={[styles.metricCard, { borderLeftColor: accentColor }]}>
    <View style={styles.metricHeader}>
      <Text style={styles.metricLabel}>{label}</Text>
      <Ionicons name={icon} size={18} color={accentColor} />
    </View>
    <Text style={styles.metricValue}>{value}</Text>
    {subtitle ? <Text style={styles.metricSub}>{subtitle}</Text> : null}
  </View>
));

export const PrimaryButton: React.FC<{
  title: string;
  onPress: () => void;
  loading?: boolean;
  disabled?: boolean;
  variant?: 'primary' | 'secondary' | 'outline' | 'danger';
  icon?: keyof typeof Ionicons.glyphMap;
  style?: any;
}> = React.memo(({ title, onPress, loading, disabled, variant = 'primary', icon, style }) => {
  const isDisabled = disabled || loading;
  const bg =
    variant === 'secondary'
      ? COLORS.secondary
      : variant === 'outline'
        ? 'transparent'
        : variant === 'danger'
          ? COLORS.danger
          : COLORS.primary;
  const textColor = variant === 'secondary' ? '#111111' : COLORS.text;

  return (
    <TouchableOpacity
      onPress={onPress}
      disabled={isDisabled}
      style={[
        styles.button,
        {
          backgroundColor: bg,
          borderColor: variant === 'outline' ? COLORS.border : bg,
          opacity: isDisabled ? 0.55 : 1,
        },
        style,
      ]}
    >
      {loading ? (
        <ActivityIndicator size="small" color={textColor} />
      ) : (
        <View style={{ flexDirection: 'row', alignItems: 'center' }}>
          {icon && (
            <Ionicons name={icon} size={18} color={textColor} style={{ marginRight: 6 }} />
          )}
          <Text style={[styles.buttonText, { color: textColor }]}>{title}</Text>
        </View>
      )}
    </TouchableOpacity>
  );
});

export const FormField: React.FC<{
  label: string;
  value: string;
  onChangeText: (val: string) => void;
  placeholder?: string;
  keyboardType?: 'default' | 'numeric' | 'decimal-pad' | 'email-address' | 'phone-pad';
  secureTextEntry?: boolean;
  multiline?: boolean;
  required?: boolean;
}> = React.memo(
  ({
    label,
    value,
    onChangeText,
    placeholder,
    keyboardType = 'default',
    secureTextEntry,
    multiline,
    required,
  }) => (
    <View style={styles.fieldGroup}>
      <Text style={styles.fieldLabel}>
        {label} {required ? <Text style={{ color: COLORS.primary }}>*</Text> : null}
      </Text>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor={COLORS.textMuted}
        keyboardType={keyboardType}
        secureTextEntry={secureTextEntry}
        multiline={multiline}
        style={[styles.fieldInput, multiline && { minHeight: 74, textAlignVertical: 'top' }]}
        autoCapitalize={
          keyboardType === 'email-address' || secureTextEntry ? 'none' : 'sentences'
        }
      />
    </View>
  )
);

export const ConfirmModal: React.FC<{
  visible: boolean;
  title: string;
  message: string;
  confirmText?: string;
  confirmVariant?: 'primary' | 'danger' | 'secondary';
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}> = React.memo(
  ({
    visible,
    title,
    message,
    confirmText = 'Confirm',
    confirmVariant = 'primary',
    loading = false,
    onConfirm,
    onCancel,
  }) => (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onCancel}>
      <View style={styles.modalBackdrop}>
        <View style={styles.modalCard}>
          <Text style={styles.modalTitle}>{title}</Text>
          <Text style={styles.modalMessage}>{message}</Text>
          <View style={styles.modalActions}>
            <PrimaryButton
              title="Cancel"
              variant="outline"
              onPress={onCancel}
              disabled={loading}
              style={{ flex: 1, marginRight: 8 }}
            />
            <PrimaryButton
              title={confirmText}
              variant={confirmVariant}
              onPress={onConfirm}
              loading={loading}
              style={{ flex: 1 }}
            />
          </View>
        </View>
      </View>
    </Modal>
  )
);

export const EmptyState: React.FC<{
  icon?: keyof typeof Ionicons.glyphMap;
  title: string;
  subtitle?: string;
}> = React.memo(({ icon = 'cube-outline', title, subtitle }) => (
  <View style={styles.emptyBox}>
    <Ionicons name={icon} size={38} color={COLORS.textMuted} />
    <Text style={styles.emptyTitle}>{title}</Text>
    {subtitle ? <Text style={styles.emptySubtitle}>{subtitle}</Text> : null}
  </View>
));

const styles = StyleSheet.create({
  badge: {
    paddingHorizontal: 9,
    paddingVertical: 3,
    borderRadius: 999,
    borderWidth: 1,
    alignSelf: 'flex-start',
  },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
  },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    marginBottom: 12,
  },
  bannerText: {
    flex: 1,
    color: COLORS.text,
    fontSize: 13,
    lineHeight: 18,
  },
  searchContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.card,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    paddingHorizontal: 12,
    height: 44,
    marginBottom: 10,
  },
  searchInput: {
    flex: 1,
    color: COLORS.text,
    fontSize: 14,
  },
  chipsRow: {
    paddingBottom: 10,
    gap: 8,
  },
  chip: {
    paddingHorizontal: 13,
    paddingVertical: 7,
    borderRadius: 999,
    backgroundColor: COLORS.card,
    borderWidth: 1,
    borderColor: COLORS.border,
    marginRight: 8,
  },
  chipActive: {
    backgroundColor: COLORS.primary,
    borderColor: COLORS.primary,
  },
  chipText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
  },
  chipTextActive: {
    color: COLORS.text,
  },
  metricCard: {
    width: '48%',
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderLeftWidth: 4,
    padding: 12,
    marginBottom: 10,
  },
  metricHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  metricLabel: {
    color: COLORS.textSecondary,
    fontSize: 11,
    fontWeight: '600',
    textTransform: 'uppercase',
  },
  metricValue: {
    color: COLORS.text,
    fontSize: 20,
    fontWeight: '700',
  },
  metricSub: {
    color: COLORS.textMuted,
    fontSize: 11,
    marginTop: 2,
  },
  button: {
    minHeight: 46,
    borderRadius: 10,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  buttonText: {
    fontSize: 14,
    fontWeight: '700',
  },
  fieldGroup: {
    marginBottom: 12,
  },
  fieldLabel: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 5,
  },
  fieldInput: {
    backgroundColor: COLORS.navBackground,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderRadius: 10,
    color: COLORS.text,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
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
    fontSize: 17,
    fontWeight: '700',
    marginBottom: 8,
  },
  modalMessage: {
    color: COLORS.textSecondary,
    fontSize: 14,
    lineHeight: 20,
    marginBottom: 16,
  },
  modalActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  emptyBox: {
    backgroundColor: COLORS.card,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.border,
    padding: 28,
    alignItems: 'center',
    marginVertical: 10,
  },
  emptyTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
    marginTop: 10,
  },
  emptySubtitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    textAlign: 'center',
    marginTop: 4,
  },
});
