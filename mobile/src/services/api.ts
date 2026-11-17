import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';
import { Platform } from 'react-native';

const TOKEN_KEY = 'mybatchoy_auth_token_v1';
const API_URL_KEY = 'mybatchoy_api_base_url_v2';
const CACHE_TTL_MS = 8000;

export const IS_DEV_MODE: boolean =
  typeof __DEV__ !== 'undefined' ? Boolean(__DEV__) : process.env.NODE_ENV !== 'production';

const RAW_ENV_API_URL = (process.env.EXPO_PUBLIC_API_URL || '').trim();

// Tracks endpoints whose un-updated Vercel /api/index.php strips one "/api" prefix.
const vercelStrippedBases = new Set<string>();

export function isValidProductionApiUrl(url: string): boolean {
  const trimmed = (url || '').trim();
  if (!/^https:\/\/[^\s/$.?#].[^\s]*$/i.test(trimmed)) {
    return false;
  }

  try {
    const parsed = new URL(trimmed);
    const host = parsed.hostname.toLowerCase();
    if (
      host === 'localhost' ||
      host === '127.0.0.1' ||
      host === '10.0.2.2' ||
      host.startsWith('192.168.') ||
      host.startsWith('10.') ||
      host.endsWith('.example') ||
      host.includes('your-project') ||
      host.includes('your-production-domain') ||
      host.includes('your-preview-domain')
    ) {
      return false;
    }

    return parsed.protocol === 'https:';
  } catch {
    return false;
  }
}

export function getDetectedLanHost(): string | null {
  if (!IS_DEV_MODE) {
    return null;
  }

  if (Platform.OS === 'web') {
    if (typeof window !== 'undefined' && window.location?.hostname) {
      return window.location.hostname;
    }
    return null;
  }

  const extra = Constants as any;
  const hostUri: string | undefined =
    Constants.expoConfig?.hostUri ||
    extra?.manifest2?.extra?.expoGo?.debuggerHost ||
    extra?.manifest?.debuggerHost;

  if (!hostUri || typeof hostUri !== 'string') {
    return null;
  }

  const host = hostUri.split(':')[0]?.trim();
  return host || null;
}

export function normalizeApiBaseUrl(rawUrl: string): string {
  let cleaned = (rawUrl || '').trim();
  if (!cleaned) {
    return detectDefaultBaseUrl();
  }

  if (!/^https?:\/\//i.test(cleaned)) {
    cleaned = `${IS_DEV_MODE ? 'http' : 'https'}://${cleaned}`;
  }

  cleaned = cleaned.replace(/\/+$/, '');

  if (/\/api\/v1$/i.test(cleaned)) {
    return cleaned.replace(/\/api\/v1$/i, '/api/v1');
  }

  if (/\/api$/i.test(cleaned)) {
    return `${cleaned}/v1`;
  }

  if (/\/mybatchoy$/i.test(cleaned)) {
    return `${cleaned}/public/api/v1`;
  }

  return `${cleaned}/api/v1`;
}

export interface ApiPreset {
  label: string;
  url: string;
}

export function getApiPresets(): ApiPreset[] {
  if (!IS_DEV_MODE) {
    return [];
  }

  const lanHost = getDetectedLanHost();
  const wifiHost =
    lanHost && lanHost !== 'localhost' && lanHost !== '127.0.0.1' ? lanHost : 'localhost';

  const presets: ApiPreset[] = [
    {
      label: 'Phone Wi-Fi (XAMPP)',
      url: `http://${wifiHost}/Mybatchoy/public/api/v1`,
    },
    {
      label: 'PC Browser (XAMPP)',
      url: 'http://localhost/Mybatchoy/public/api/v1',
    },
    {
      label: 'Android Emulator',
      url: 'http://10.0.2.2/Mybatchoy/public/api/v1',
    },
  ];

  if (RAW_ENV_API_URL) {
    presets.push({
      label: 'Configured Env API',
      url: normalizeApiBaseUrl(RAW_ENV_API_URL),
    });
  }

  return presets;
}

function detectDefaultBaseUrl(): string {
  if (RAW_ENV_API_URL) {
    return normalizeApiBaseUrl(RAW_ENV_API_URL);
  }

  if (IS_DEV_MODE && Platform.OS === 'web' && typeof window !== 'undefined') {
    const host = (window.location?.hostname || '').toLowerCase();
    if (
      host === 'localhost' ||
      host === '127.0.0.1' ||
      host.startsWith('192.168.') ||
      host.startsWith('10.')
    ) {
      return `http://${host}/Mybatchoy/public/api/v1`;
    }
  }

  return IS_DEV_MODE ? 'http://localhost/Mybatchoy/public/api/v1' : '';
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
  if (!IS_DEV_MODE) {
    const envUrl = RAW_ENV_API_URL ? normalizeApiBaseUrl(RAW_ENV_API_URL) : '';
    if (!isValidProductionApiUrl(envUrl)) {
      throw new ApiError(
        'Production build is missing a valid HTTPS EXPO_PUBLIC_API_URL endpoint.',
        0
      );
    }
    return envUrl;
  }

  if (baseUrlLoaded) return memoryBaseUrl;
  try {
    if (Platform.OS === 'web') {
      if (typeof window !== 'undefined' && window.localStorage) {
        const saved = window.localStorage.getItem(API_URL_KEY);
        if (saved && saved.trim() !== '') {
          memoryBaseUrl = normalizeApiBaseUrl(saved);
        }
      }
    } else {
      const saved = await SecureStore.getItemAsync(API_URL_KEY);
      if (saved && saved.trim() !== '') {
        memoryBaseUrl = normalizeApiBaseUrl(saved);
      }
    }
    baseUrlLoaded = true;
  } catch {
    baseUrlLoaded = true;
  }
  return memoryBaseUrl;
}

export async function setApiBaseUrl(url: string): Promise<string> {
  if (!IS_DEV_MODE) {
    throw new Error('Custom API URL overrides are disabled in production builds.');
  }

  const cleaned = normalizeApiBaseUrl(url);
  memoryBaseUrl = cleaned;
  baseUrlLoaded = true;
  clearApiCache();
  try {
    if (Platform.OS === 'web') {
      if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.setItem(API_URL_KEY, cleaned);
      }
    } else {
      await SecureStore.setItemAsync(API_URL_KEY, cleaned);
    }
  } catch {
    // Ignore storage error
  }
  return cleaned;
}

function resolveEffectiveBase(baseUrl: string): string {
  if (vercelStrippedBases.has(baseUrl) && /\/api\/v1$/i.test(baseUrl) && !/\/api\/api\/v1$/i.test(baseUrl)) {
    return baseUrl.replace(/\/api\/v1$/i, '/api/api/v1');
  }
  return baseUrl;
}

export async function testApiConnection(
  rawUrl: string
): Promise<{ ok: boolean; url: string; message: string }> {
  const normalized = normalizeApiBaseUrl(rawUrl);
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 8000);

  const probe = async (base: string) => {
    const healthRes = await fetch(`${base}/health`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });
    if (healthRes.ok) {
      return { status: healthRes.status, ok: true, payloadText: '' };
    }
    const text = await healthRes.text();
    if (healthRes.status === 404) {
      const loginRes = await fetch(`${base}/auth/login`, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({}),
        signal: controller.signal,
      });
      const loginText = await loginRes.text();
      if (loginRes.status === 422 || loginRes.status === 401) {
        return { status: loginRes.status, ok: true, payloadText: loginText };
      }
      return { status: loginRes.status, ok: false, payloadText: loginText || text };
    }
    return { status: healthRes.status, ok: false, payloadText: text };
  };

  try {
    let result = await probe(resolveEffectiveBase(normalized));
    if (!result.ok && result.status === 404 && result.payloadText.includes('The route v1/')) {
      const doubled = normalized.replace(/\/api\/v1$/i, '/api/api/v1');
      result = await probe(doubled);
      if (result.ok) {
        vercelStrippedBases.add(normalized);
      }
    }
    clearTimeout(timeoutId);

    if (result.ok) {
      return { ok: true, url: normalized, message: `Connected cleanly to ${normalized}` };
    }

    if (result.status === 404) {
      return {
        ok: false,
        url: normalized,
        message: `HTTP 404 at ${normalized}. Check that Apache/server points to .../public/api/v1.`,
      };
    }

    return {
      ok: false,
      url: normalized,
      message: `Server replied with status ${result.status} at ${normalized}.`,
    };
  } catch {
    clearTimeout(timeoutId);
    return {
      ok: false,
      url: normalized,
      message: `Could not reach ${normalized}. Check Wi-Fi, IP address, or XAMPP Apache.`,
    };
  }
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
  const rawBaseUrl = await getApiBaseUrl();
  const baseUrl = resolveEffectiveBase(rawBaseUrl);
  const token = await getStoredToken();
  const method = options.method || 'GET';

  const endpointPath = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
  let queryString = '';

  if (options.params) {
    const searchParams = new URLSearchParams();
    Object.entries(options.params).forEach(([key, val]) => {
      if (val !== undefined && val !== null && String(val).trim() !== '') {
        searchParams.append(key, String(val));
      }
    });
    const qs = searchParams.toString();
    if (qs) {
      queryString = `?${qs}`;
    }
  }

  let url = `${baseUrl}${endpointPath}${queryString}`;

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
      let response = await fetch(url, {
        method,
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        signal: controller.signal,
      });

      let contentType = response.headers.get('content-type') || '';
      let isJson = contentType.includes('application/json');
      let payload = isJson ? await response.json() : await response.text();

      // Recover seamlessly if an un-updated Vercel /api/index.php stripped one "/api" prefix
      if (
        response.status === 404 &&
        isJson &&
        typeof payload?.message === 'string' &&
        payload.message.includes('The route v1/') &&
        /\/api\/v1$/i.test(rawBaseUrl) &&
        !vercelStrippedBases.has(rawBaseUrl)
      ) {
        vercelStrippedBases.add(rawBaseUrl);
        url = `${resolveEffectiveBase(rawBaseUrl)}${endpointPath}${queryString}`;
        response = await fetch(url, {
          method,
          headers,
          body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
          signal: controller.signal,
        });
        contentType = response.headers.get('content-type') || '';
        isJson = contentType.includes('application/json');
        payload = isJson ? await response.json() : await response.text();
      }

      clearTimeout(timeoutId);

      if (!response.ok) {
        const errorMessage =
          (isJson && payload?.message) ||
          (response.status === 404
            ? IS_DEV_MODE
              ? `Endpoint not found (404) at ${url}. Tap "API URL" and make sure it ends with /api/v1.`
              : `API endpoint not found (404). Please contact the administrator.`
            : `Request failed with status ${response.status}`);

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
        IS_DEV_MODE
          ? 'Unable to reach the MyBatchoy server. Verify your network connection and Server URL.'
          : 'Unable to reach the MyBatchoy server. Check your internet connection and try again.',
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
