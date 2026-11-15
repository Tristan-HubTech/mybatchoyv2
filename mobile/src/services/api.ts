import * as SecureStore from 'expo-secure-store';
import { NativeModules, Platform } from 'react-native';

const TOKEN_KEY = 'mybatchoy_auth_token_v1';
const API_URL_KEY = 'mybatchoy_api_base_url_v1';
const CACHE_TTL_MS = 8000;

const PRODUCTION_API_URL = 'https://mybatchoyv2-rho.vercel.app/api/v1';

function detectDefaultBaseUrl(): string {
  if (!__DEV__) {
    return PRODUCTION_API_URL;
  }

  // Default to the live Vercel cloud backend so web & mobile share the same DB anywhere.
  // Developers can still override this anytime from the Sign In "API Server URL" field.
  return PRODUCTION_API_URL;
}

const DEFAULT_BASE_URL = detectDefaultBaseUrl();

let memoryToken: string | null = null;
let tokenLoaded = false;
let memoryBaseUrl: string = DEFAULT_BASE_URL;
let baseUrlLoaded = false;
let unauthorizedHandler: ((reason?: string) => void) | null = null;

const responseCache = new Map<string, { timestamp: number; data: any }>();
const inFlightGets = new Map<string, Promise<any>>();

export function clearApiCache(): void {
  responseCache.clear();
  inFlightGets.clear();
}

export function setOnUnauthorized(handler: (reason?: string) => void) {
  unauthorizedHandler = handler;
}

export function generateIdempotencyKey(): string {
  const randomPart = () => Math.random().toString(16).substring(2, 10);
  return `mb-${Date.now().toString(16)}-${randomPart()}-${randomPart()}`;
}

export async function getStoredToken(): Promise<string | null> {
  if (tokenLoaded) return memoryToken;
  try {
    if (Platform.OS === 'web') {
      tokenLoaded = true;
      return null;
    }
    memoryToken = await SecureStore.getItemAsync(TOKEN_KEY);
    tokenLoaded = true;
    return memoryToken;
  } catch {
    tokenLoaded = true;
    return memoryToken;
  }
}

export async function setStoredToken(token: string | null): Promise<void> {
  memoryToken = token;
  tokenLoaded = true;
  clearApiCache();
  try {
    if (Platform.OS === 'web') return;
    if (token) {
      await SecureStore.setItemAsync(TOKEN_KEY, token);
    } else {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
    }
  } catch {
    // Fallback to in-memory token if SecureStore unavailable
  }
}

export async function getApiBaseUrl(): Promise<string> {
  if (baseUrlLoaded) return memoryBaseUrl;
  try {
    if (Platform.OS !== 'web') {
      const saved = await SecureStore.getItemAsync(API_URL_KEY);
      if (saved && saved.trim() !== '') {
        memoryBaseUrl = saved.trim().replace(/\/+$/, '');
      }
    }
    baseUrlLoaded = true;
  } catch {
    baseUrlLoaded = true;
  }
  return memoryBaseUrl;
}

export async function setApiBaseUrl(url: string): Promise<string> {
  const cleaned = url.trim().replace(/\/+$/, '') || DEFAULT_BASE_URL;
  memoryBaseUrl = cleaned;
  baseUrlLoaded = true;
  clearApiCache();
  try {
    if (Platform.OS !== 'web') {
      await SecureStore.setItemAsync(API_URL_KEY, cleaned);
    }
  } catch {
    // Ignore storage error
  }
  return cleaned;
}

export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: unknown;
  params?: Record<string, string | number | boolean | undefined | null>;
  idempotencyKey?: string;
  skipAuthRedirect?: boolean;
  skipCache?: boolean;
}

export async function apiRequest<T = any>(
  endpoint: string,
  options: RequestOptions = {}
): Promise<T> {
  const baseUrl = await getApiBaseUrl();
  const token = await getStoredToken();
  const method = options.method || 'GET';

  let url = `${baseUrl}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;

  if (options.params) {
    const searchParams = new URLSearchParams();
    Object.entries(options.params).forEach(([key, val]) => {
      if (val !== undefined && val !== null && String(val).trim() !== '') {
        searchParams.append(key, String(val));
      }
    });
    const qs = searchParams.toString();
    if (qs) {
      url += `?${qs}`;
    }
  }

  const cacheKey = `${token || 'anon'}:${url}`;

  if (method === 'GET' && !options.skipCache) {
    const cached = responseCache.get(cacheKey);
    if (cached && Date.now() - cached.timestamp < CACHE_TTL_MS) {
      return cached.data as T;
    }
    const existingInFlight = inFlightGets.get(cacheKey);
    if (existingInFlight) {
      return existingInFlight as Promise<T>;
    }
  }

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
    headers['X-Idempotency-Key'] = options.idempotencyKey || generateIdempotencyKey();
  }

  const executeFetch = async (): Promise<T> => {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000);

    try {
      const response = await fetch(url, {
        method,
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);

      const contentType = response.headers.get('content-type') || '';
      const isJson = contentType.includes('application/json');
      const payload = isJson ? await response.json() : await response.text();

      if (!response.ok) {
        const errorMessage =
          (isJson && payload?.message) ||
          `Request failed with status ${response.status}`;

        if (
          !options.skipAuthRedirect &&
          (response.status === 401 ||
            (response.status === 403 &&
              typeof errorMessage === 'string' &&
              errorMessage.toLowerCase().includes('inactive')))
        ) {
          await setStoredToken(null);
          unauthorizedHandler?.(errorMessage);
        }

        if (isJson && payload?.errors) {
          const firstFieldErrors = Object.values(payload.errors)[0] as string[] | undefined;
          const firstMsg = firstFieldErrors?.[0] || errorMessage;
          throw new ApiError(firstMsg, response.status, payload.errors);
        }

        throw new ApiError(errorMessage, response.status);
      }

      if (method === 'GET') {
        responseCache.set(cacheKey, { timestamp: Date.now(), data: payload });
      } else {
        // Invalidate cached GET responses whenever data is mutated
        responseCache.clear();
      }

      return payload as T;
    } catch (err: any) {
      clearTimeout(timeoutId);
      if (err instanceof ApiError) {
        throw err;
      }
      if (err?.name === 'AbortError') {
        throw new ApiError(
          'Request timed out. Check your Wi-Fi/mobile connection and try again.',
          0
        );
      }
      throw new ApiError(
        'Unable to reach the MyBatchoy server. Verify your network connection and Server URL.',
        0
      );
    } finally {
      if (method === 'GET') {
        inFlightGets.delete(cacheKey);
      }
    }
  };

  const promise = executeFetch();
  if (method === 'GET') {
    inFlightGets.set(cacheKey, promise);
  }

  return promise;
}
