export type UserRole = 'owner' | 'staff';

export interface UserProfile {
  id: number;
  name: string;
  username: string;
  email: string;
  phone: string | null;
  role: UserRole;
  role_label: string;
  is_active: boolean;
  mfa_enabled: boolean;
  last_login_at: string | null;
  created_at?: string | null;
}

export interface BusinessSummary {
  business_name: string;
  session_timeout_minutes: number;
  default_minimum_stock_level: number;
  default_reorder_level: number;
}

export interface InventoryItemModel {
  id: number;
  name: string;
  sku: string;
  description: string | null;
  category_id: number;
  category_name: string;
  unit_id: number;
  unit_name: string;
  unit_short_name: string;
  current_quantity: number;
  minimum_stock_level: number;
  reorder_level: number;
  status: 'Available' | 'Low Stock' | 'Out of Stock';
  is_low_stock: boolean;
  is_out_of_stock: boolean;
  is_active: boolean;
  last_counted_at: string | null;
  updated_at?: string | null;
}

export interface CategoryOption {
  id: number;
  name: string;
  description?: string | null;
  is_active: boolean;
  items_count?: number;
}

export interface UnitOption {
  id: number;
  name: string;
  short_name: string;
  description?: string | null;
  is_active: boolean;
  items_count?: number;
}

export type TransactionType = 'stock_in' | 'stock_out' | 'adjustment' | 'spoilage';

export interface StockTransactionModel {
  id: number;
  inventory_item_id: number;
  item_name: string;
  item_sku: string;
  unit_short_name: string;
  user_id: number;
  user_name: string;
  transaction_type: TransactionType;
  quantity: number;
  previous_quantity: number;
  updated_quantity: number;
  reason: string | null;
  transaction_date: string | null;
}

export interface SupplierModel {
  id: number;
  name: string;
  contact_person: string | null;
  phone: string | null;
  email: string | null;
  address: string | null;
  notes: string | null;
  is_active: boolean;
  purchases_count: number;
  created_at: string | null;
}

export interface PurchaseLineItemModel {
  id: number;
  inventory_item_id: number;
  item_name: string;
  item_sku: string;
  unit_short_name: string;
  quantity: number;
  unit_cost: number;
  total_cost: number;
}

export interface PurchaseModel {
  id: number;
  purchase_number: string;
  supplier_id: number | null;
  supplier_name: string;
  created_by: number;
  created_by_name: string;
  purchase_date: string;
  total_amount: number;
  notes: string | null;
  items_count: number;
  items?: PurchaseLineItemModel[];
  created_at?: string | null;
}

export interface AuditLogModel {
  id: number;
  user_id: number | null;
  user_name: string;
  username: string | null;
  user_role: string | null;
  action: string;
  model_type: string | null;
  model_id: number | null;
  details: string | null;
  ip_address: string | null;
  created_at: string | null;
}

