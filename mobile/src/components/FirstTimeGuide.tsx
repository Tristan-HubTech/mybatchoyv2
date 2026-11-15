import React, { useCallback, useEffect, useState } from 'react';
import {
  Modal,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import * as SecureStore from 'expo-secure-store';
import { COLORS } from '../theme/colors';
import { PrimaryButton } from './UI';

const GUIDE_STORAGE_KEY = 'mybatchoy_mobile_guide_dismissed_v1';

async function readDismissedState(): Promise<boolean> {
  try {
    if (Platform.OS === 'web') {
      if (typeof window !== 'undefined' && window.localStorage) {
        return window.localStorage.getItem(GUIDE_STORAGE_KEY) === '1';
      }
      return false;
    }
    const stored = await SecureStore.getItemAsync(GUIDE_STORAGE_KEY);
    return stored === '1';
  } catch {
    return false;
  }
}

async function writeDismissedState(value: boolean): Promise<void> {
  try {
    if (Platform.OS === 'web') {
      if (typeof window !== 'undefined' && window.localStorage) {
        if (value) {
          window.localStorage.setItem(GUIDE_STORAGE_KEY, '1');
        } else {
          window.localStorage.removeItem(GUIDE_STORAGE_KEY);
        }
      }
      return;
    }
    if (value) {
      await SecureStore.setItemAsync(GUIDE_STORAGE_KEY, '1');
    } else {
      await SecureStore.deleteItemAsync(GUIDE_STORAGE_KEY);
    }
  } catch {
    // Ignore storage errors safely so the UI stays responsive.
  }
}

export function useFirstTimeGuide() {
  const [ready, setReady] = useState(false);
  const [dismissed, setDismissed] = useState(true);
  const [guideVisible, setGuideVisible] = useState(false);

  useEffect(() => {
    let active = true;
    readDismissedState().then((isDismissed) => {
      if (active) {
        setDismissed(isDismissed);
        setReady(true);
      }
    });
    return () => {
      active = false;
    };
  }, []);

  const dismissForever = useCallback(async () => {
    setDismissed(true);
    setGuideVisible(false);
    await writeDismissedState(true);
  }, []);

  return {
    ready,
    showWelcomeCard: ready && !dismissed,
    guideVisible,
    openGuide: () => setGuideVisible(true),
    closeGuide: () => setGuideVisible(false),
    dismissForever,
  };
}

export const FirstTimeWelcomeBanner: React.FC<{
  onOpenGuide: () => void;
  onDismiss: () => void;
}> = React.memo(({ onOpenGuide, onDismiss }) => (
  <View style={styles.bannerCard}>
    <View style={styles.bannerHeader}>
      <View style={styles.bannerIconWrap}>
        <Ionicons name="bulb-outline" size={20} color="#111111" />
      </View>
      <View style={{ flex: 1 }}>
        <Text style={styles.bannerTitle}>First time using MyBatchoy?</Text>
        <Text style={styles.bannerText}>
          Read our 1-minute picture-free guide for Add Stock, Remove Stock, Counting, and Spoilage.
        </Text>
      </View>
    </View>
    <View style={styles.bannerActions}>
      <TouchableOpacity style={styles.bannerPrimaryBtn} onPress={onOpenGuide}>
        <Ionicons name="book-outline" size={16} color="#111111" style={{ marginRight: 6 }} />
        <Text style={styles.bannerPrimaryBtnText}>Open Quick Guide</Text>
      </TouchableOpacity>
      <TouchableOpacity style={styles.bannerDismissBtn} onPress={onDismiss}>
        <Text style={styles.bannerDismissBtnText}>Don&apos;t show again</Text>
      </TouchableOpacity>
    </View>
  </View>
));

const GUIDE_STEPS: {
  title: string;
  where: string;
  description: string;
  icon: keyof typeof Ionicons.glyphMap;
  accent: string;
  ownerOnly?: boolean;
}[] = [
  {
    title: '1. Check What We Have (View Stock)',
    where: 'Stock tab',
    description:
      'See every ingredient and its current amount. Green = Available, Yellow = Low Stock (needs attention), Red = Out of Stock. Tap any item to inspect its details.',
    icon: 'cube-outline',
    accent: COLORS.success,
  },
  {
    title: '2. Add New Deliveries (Add Stock)',
    where: 'Add / Out tab -> + Add Stock',
    description:
      'Use when supplies arrive. Pick the item, enter how many arrived, select a supplier if needed, check the new total preview, and tap Confirm Add Stock.',
    icon: 'add-circle-outline',
    accent: COLORS.success,
  },
  {
    title: '3. Record Kitchen Usage (Remove Stock)',
    where: 'Add / Out tab -> - Remove Stock',
    description:
      'Use when ingredients are taken out for cooking or prep. The screen warns you if you ask for more than what is available and confirms before saving.',
    icon: 'remove-circle-outline',
    accent: COLORS.primary,
  },
  {
    title: '4. Fix Wrong Numbers (Correct Stock Quantity)',
    where: 'Add / Out tab -> Correct Stock',
    description:
      'After counting items on the shelf, enter the exact real amount on hand and explain why. The app shows the before/after change and confirms first.',
    icon: 'create-outline',
    accent: COLORS.warning,
  },
  {
    title: '5. Record Spoiled or Damaged Items (Spoilage)',
    where: 'Add / Out tab -> Spoilage',
    description:
      'Use when ingredients expire, spill, or spoil so stock counts stay accurate. Spoilage reduces available stock and saves the date, amount, and your name.',
    icon: 'warning-outline',
    accent: COLORS.danger,
  },
  {
    title: '6. Suppliers & Supply Orders',
    where: 'Orders tab (Owner only)',
    description:
      'Save supplier phone numbers and record multi-item supply purchases with total costs. Note: Only stock and purchase costs are tracked here — never sales.',
    icon: 'cart-outline',
    accent: COLORS.secondary,
    ownerOnly: true,
  },
];

export const FirstTimeGuideModal: React.FC<{
  visible: boolean;
  isOwner: boolean;
  showDismissForever: boolean;
  onClose: () => void;
  onDismissForever: () => void;
}> = React.memo(({ visible, isOwner, showDismissForever, onClose, onDismissForever }) => {
  const steps = GUIDE_STEPS.filter((step) => isOwner || !step.ownerOnly);

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.modalBackdrop}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHeader}>
            <View style={{ flex: 1 }}>
              <Text style={styles.modalTitle}>Quick Help Guide</Text>
              <Text style={styles.modalSubtitle}>
                Plain instructions for daily stock work. You can reopen this anytime from &quot;Help&quot; at the top right.
              </Text>
            </View>
            <TouchableOpacity onPress={onClose} style={{ padding: 4 }}>
              <Ionicons name="close-circle" size={28} color={COLORS.textSecondary} />
            </TouchableOpacity>
          </View>

          <ScrollView style={{ marginVertical: 10 }} showsVerticalScrollIndicator={false}>
            {steps.map((step) => (
              <View key={step.title} style={[styles.stepCard, { borderLeftColor: step.accent }]}>
                <View style={styles.stepTitleRow}>
                  <Ionicons name={step.icon} size={18} color={step.accent} style={{ marginRight: 8 }} />
                  <Text style={styles.stepTitle}>{step.title}</Text>
                </View>
                <Text style={styles.stepWhere}>Where: {step.where}</Text>
                <Text style={styles.stepDesc}>{step.description}</Text>
              </View>
            ))}
          </ScrollView>

          <PrimaryButton
            title={showDismissForever ? "Got It — Don't Show Welcome Card Again" : 'Got It, Close Guide'}
            variant="secondary"
            onPress={showDismissForever ? onDismissForever : onClose}
          />
          {showDismissForever && (
            <TouchableOpacity onPress={onClose} style={styles.justCloseBtn}>
              <Text style={styles.justCloseText}>Keep showing welcome tip for now</Text>
            </TouchableOpacity>
          )}
        </View>
      </View>
    </Modal>
  );
});

const styles = StyleSheet.create({
  bannerCard: {
    backgroundColor: COLORS.cardElevated,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: COLORS.secondary,
    padding: 14,
    marginBottom: 14,
  },
  bannerHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
  },
  bannerIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: COLORS.secondary,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  bannerTitle: {
    color: COLORS.text,
    fontSize: 15,
    fontWeight: '700',
  },
  bannerText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 18,
    marginTop: 3,
  },
  bannerActions: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginTop: 12,
  },
  bannerPrimaryBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: COLORS.secondary,
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: 8,
  },
  bannerPrimaryBtnText: {
    color: '#111111',
    fontSize: 13,
    fontWeight: '700',
  },
  bannerDismissBtn: {
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: COLORS.border,
  },
  bannerDismissBtnText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
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
  modalHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginBottom: 6,
  },
  modalTitle: {
    color: COLORS.text,
    fontSize: 18,
    fontWeight: '800',
  },
  modalSubtitle: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 18,
    marginTop: 3,
  },
  stepCard: {
    backgroundColor: COLORS.navBackground,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: COLORS.border,
    borderLeftWidth: 4,
    padding: 12,
    marginBottom: 10,
  },
  stepTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  stepTitle: {
    color: COLORS.text,
    fontSize: 14,
    fontWeight: '700',
    flex: 1,
  },
  stepWhere: {
    color: COLORS.secondary,
    fontSize: 11,
    fontWeight: '700',
    marginTop: 4,
  },
  stepDesc: {
    color: COLORS.textSecondary,
    fontSize: 12,
    lineHeight: 18,
    marginTop: 4,
  },
  justCloseBtn: {
    alignItems: 'center',
    paddingVertical: 10,
  },
  justCloseText: {
    color: COLORS.textSecondary,
    fontSize: 12,
    fontWeight: '600',
  },
});

