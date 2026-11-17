import { ApiError, apiRequest } from './api';
import {
  InventoryItemModel,
  StockNotificationCounts,
  StockNotificationItem,
  StockNotificationResponse,
  StockTransactionModel,
} from '../types/models';

const EMPTY_COUNTS: StockNotificationCounts = {
  out_of_stock: 0,
  low_stock: 0,
  reorder: 0,
  spoilage: 0,
};

function formatQty(value: number): string {
  const fixed = value.toFixed(3).replace(/0+$/, '').replace(/\.$/, '');
  return fixed === '' || fixed === '-0' ? '0' : fixed;
}

function buildFallbackNotifications(
  items: InventoryItemModel[],
  spoilageTransactions: StockTransactionModel[]
): StockNotificationResponse {
  const counts: StockNotificationCounts = { ...EMPTY_COUNTS };
  const stockAlerts: Array<StockNotificationItem & { priority: number; qty: number }> = [];

  for (const item of items) {
    if (!item.is_active) continue;

    const qty = Number(item.current_quantity);
    const min = Number(item.minimum_stock_level);
    const reorder = Number(item.reorder_level);
    const unit = item.unit_short_name || 'units';

    if (qty <= 0) {
      counts.out_of_stock += 1;
      stockAlerts.push({
        id: `stock-${item.id}`,
        category: 'out_of_stock',
        severity: 'danger',
        badge: 'Out of Stock',
        title: `${item.name} is out of stock`,
        message: `0 ${unit} remaining (minimum needed: ${formatQty(min)} ${unit}). Add stock now.`,
        item_id: item.id,
        transaction_id: null,
        action_type: 'stock_in',
        time_ago: null,
        created_at: item.updated_at ?? null,
        priority: 0,
        qty,
      });
    } else if (qty <= min) {
      counts.low_stock += 1;
      stockAlerts.push({
        id: `stock-${item.id}`,
        category: 'low_stock',
        severity: 'warning',
        badge: 'Low Stock',
        title: `${item.name} is running low`,
        message: `${formatQty(qty)} ${unit} left (minimum level: ${formatQty(min)} ${unit}).`,
        item_id: item.id,
        transaction_id: null,
        action_type: 'stock_in',
        time_ago: null,
        created_at: item.updated_at ?? null,
        priority: 1,
        qty,
      });
    } else if (reorder > 0 && qty <= reorder) {
      counts.reorder += 1;
      stockAlerts.push({
        id: `stock-${item.id}`,
        category: 'reorder',
        severity: 'info',
        badge: 'Reorder Soon',
        title: `Time to reorder ${item.name}`,
        message: `${formatQty(qty)} ${unit} left (reorder at ${formatQty(reorder)} ${unit}).`,
        item_id: item.id,
        transaction_id: null,
        action_type: 'stock_in',
        time_ago: null,
        created_at: item.updated_at ?? null,
        priority: 2,
        qty,
      });
    }
  }

  stockAlerts.sort((a, b) => a.priority - b.priority || a.qty - b.qty || a.title.localeCompare(b.title));

  const sevenDaysAgo = Date.now() - 7 * 24 * 60 * 60 * 1000;
  const recentSpoilage = spoilageTransactions.filter((tx) => {
    if (!tx.transaction_date) return true;
    const ts = Date.parse(tx.transaction_date);
    return Number.isNaN(ts) || ts >= sevenDaysAgo;
  });

  counts.spoilage = recentSpoilage.length;

  const spoilageAlerts: StockNotificationItem[] = recentSpoilage.slice(0, 5).map((tx) => {
    const unit = tx.unit_short_name || 'units';
    const by = tx.user_name ? ` by ${tx.user_name}` : '';
    const reason = tx.reason ? tx.reason.trim() : '';

    return {
      id: `spoilage-${tx.id}`,
      category: 'spoilage',
      severity: 'spoilage',
      badge: 'Spoilage',
      title: `Spoilage recorded for ${tx.item_name}`,
      message: `${formatQty(Number(tx.quantity))} ${unit} spoiled${by}${reason ? ` — ${reason}` : '.'}`,
      item_id: tx.inventory_item_id,
      transaction_id: tx.id,
      action_type: 'spoilage_history',
      time_ago: null,
      created_at: tx.transaction_date ?? null,
    };
  });

  const merged: StockNotificationItem[] = [
    ...stockAlerts.map(({ priority: _p, qty: _q, ...rest }) => rest),
    ...spoilageAlerts,
  ].slice(0, 20);

  return {
    total_count: counts.out_of_stock + counts.low_stock + counts.reorder + counts.spoilage,
    counts,
    notifications: merged,
  };
}

export async function fetchStockNotifications(
  skipCache = false
): Promise<StockNotificationResponse> {
  try {
    return await apiRequest<StockNotificationResponse>('/notifications', { skipCache });
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      const [inventoryRes, spoilageRes] = await Promise.all([
        apiRequest<{ items: InventoryItemModel[] }>('/inventory', { skipCache }),
        apiRequest<{ transactions: StockTransactionModel[] }>('/transactions', {
          params: { type: 'spoilage' },
          skipCache,
        }),
      ]);

      return buildFallbackNotifications(
        inventoryRes.items || [],
        spoilageRes.transactions || []
      );
    }

    throw err;
  }
}

export const EMPTY_NOTIFICATION_RESPONSE: StockNotificationResponse = {
  total_count: 0,
  counts: { ...EMPTY_COUNTS },
  notifications: [],
};

