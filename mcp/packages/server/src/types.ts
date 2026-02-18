export type Role = 'bidder' | 'donor' | 'admin';

export interface User {
  id: number;
  slug: string;
  name: string;
  email: string;
  role: Role;
  email_verified: boolean;
  gift_aid_eligible: boolean;
}

export interface Event {
  id: number;
  slug: string;
  title: string;
  description?: string;
  status: string;
  starts_at?: string;
  ends_at?: string;
  venue?: string;
  created_by?: number;
  item_count?: number;
}

export interface Item {
  id: number;
  slug: string;
  title: string;
  description?: string;
  event_id: number;
  event_title?: string;
  event_slug?: string;
  category_id?: number;
  category_name?: string;
  donor_id?: number;
  starting_bid: number;
  min_increment: number;
  buy_now_price?: number;
  market_value?: number;
  current_bid: number;
  bid_count: number;
  status: string;
  image?: string;
  lot_number?: string;
}

export interface Bid {
  id: number;
  item_id: number;
  user_id: number;
  amount: number;
  is_buy_now: boolean;
  created_at: string;
}

export interface Payment {
  id: number;
  user_id: number;
  item_id: number;
  amount: number;
  status: string;
  stripe_session_id?: string;
  gift_aid_claimed?: boolean;
  gift_aid_amount?: number;
}

export interface LoginResponse {
  data: {
    token: string;
    user: User;
    expires_in: number;
  };
}

export interface ApiResponse<T> {
  data: T;
  meta?: {
    total: number;
    page: number;
    per_page: number;
    pages: number;
  };
}

export interface ApiError {
  error: string;
}
