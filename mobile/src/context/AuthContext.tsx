import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import {
  apiRequest,
  getStoredToken,
  setOnUnauthorized,
  setStoredToken,
} from '../services/api';
import { BusinessSummary, UserProfile } from '../types/models';

interface AuthContextValue {
  user: UserProfile | null;
  business: BusinessSummary | null;
  initializing: boolean;
  sessionNotice: string | null;
  clearSessionNotice: () => void;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshProfile: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<UserProfile | null>(null);
  const [business, setBusiness] = useState<BusinessSummary | null>(null);
  const [initializing, setInitializing] = useState<boolean>(true);
  const [sessionNotice, setSessionNotice] = useState<string | null>(null);

  const clearSessionNotice = useCallback(() => setSessionNotice(null), []);

  const refreshProfile = useCallback(async () => {
    const data = await apiRequest<{ user: UserProfile; business: BusinessSummary }>('/auth/me');
    setUser(data.user);
    setBusiness(data.business);
  }, []);

  useEffect(() => {
    setOnUnauthorized((reason) => {
      setUser(null);
      setBusiness(null);
      if (reason) {
        setSessionNotice(reason);
      }
    });
  }, []);

  useEffect(() => {
    let mounted = true;
    (async () => {
      try {
        const token = await getStoredToken();
        if (token) {
          const data = await apiRequest<{ user: UserProfile; business: BusinessSummary }>(
            '/auth/me',
            { skipAuthRedirect: true }
          );
          if (mounted) {
            setUser(data.user);
            setBusiness(data.business);
          }
        }
      } catch {
        await setStoredToken(null);
        if (mounted) {
          setUser(null);
        }
      } finally {
        if (mounted) {
          setInitializing(false);
        }
      }
    })();
    return () => {
      mounted = false;
    };
  }, []);

  const login = useCallback(async (username: string, password: string) => {
    setSessionNotice(null);
    const data = await apiRequest<{
      token: string;
      user: UserProfile;
      session_timeout_minutes: number;
    }>('/auth/login', {
      method: 'POST',
      body: {
        username: username.trim(),
        password,
        device_name: 'MyBatchoy Expo Go Mobile',
      },
      skipAuthRedirect: true,
    });

    await setStoredToken(data.token);
    setUser(data.user);

    try {
      const meData = await apiRequest<{ user: UserProfile; business: BusinessSummary }>('/auth/me');
      setUser(meData.user);
      setBusiness(meData.business);
    } catch {
      // Keep login user state if /auth/me fails transiently
    }
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiRequest('/auth/logout', { method: 'POST', skipAuthRedirect: true });
    } catch {
      // Always clear local token even if network is unreachable
    } finally {
      await setStoredToken(null);
      setUser(null);
      setBusiness(null);
    }
  }, []);

  return (
    <AuthContext.Provider
      value={{
        user,
        business,
        initializing,
        sessionNotice,
        clearSessionNotice,
        login,
        logout,
        refreshProfile,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return ctx;
}

