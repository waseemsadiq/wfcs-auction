import { Config } from './config.js';
import { LoginResponse, ApiResponse, User, Role } from './types.js';

export class ApiClient {
  private jwt: string | null = null;
  private jwtExpiresAt: number = 0;
  private loginPromise: Promise<void> | null = null;
  private currentUser: User | null = null;

  constructor(private config: Config) {}

  async login(): Promise<void> {
    if (this.loginPromise) return this.loginPromise;

    this.loginPromise = (async () => {
      const url = `${this.config.apiUrl}/api/auth/login`;
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: this.config.email,
          password: this.config.password,
        }),
      });

      if (!response.ok) {
        const body = await response.text();
        throw new Error(`Login failed (${response.status}): ${body}`);
      }

      const data = (await response.json()) as LoginResponse;
      this.jwt          = data.data.token;
      this.jwtExpiresAt = Date.now() + (data.data.expires_in - 60) * 1000;
      this.currentUser  = data.data.user;
      this.loginPromise = null;
    })();

    return this.loginPromise;
  }

  private async ensureAuth(): Promise<void> {
    if (!this.jwt || Date.now() >= this.jwtExpiresAt) {
      await this.login();
    }
  }

  async request<T>(
    method: string,
    path: string,
    options: { query?: Record<string, string>; body?: unknown } = {}
  ): Promise<T> {
    await this.ensureAuth();

    const url = new URL(`${this.config.apiUrl}${path}`);

    // Token via query param — Galvani drops Authorization headers
    if (this.jwt) url.searchParams.set('token', this.jwt);

    if (options.query) {
      for (const [k, v] of Object.entries(options.query)) {
        if (v !== undefined && v !== '') url.searchParams.set(k, v);
      }
    }

    const init: RequestInit = { method };

    if (options.body !== undefined) {
      init.headers = { 'Content-Type': 'application/json' };
      init.body    = JSON.stringify(options.body);
    }

    let response = await fetch(url.toString(), init);

    // Re-login once on 401 then retry
    if (response.status === 401) {
      this.jwt = null;
      await this.login();
      url.searchParams.set('token', this.jwt!);
      response = await fetch(url.toString(), init);
    }

    if (!response.ok) {
      const errBody = await response.json().catch(() => ({ error: response.statusText })) as { error?: string };
      throw new Error(errBody.error ?? `HTTP ${response.status}`);
    }

    return (await response.json()) as T;
  }

  async get<T>(path: string, query?: Record<string, string>): Promise<T> {
    return this.request<T>('GET', path, { query });
  }

  async post<T>(path: string, body?: unknown): Promise<T> {
    return this.request<T>('POST', path, { body });
  }

  async put<T>(path: string, body?: unknown): Promise<T> {
    return this.request<T>('PUT', path, { body });
  }

  getRole(): Role {
    if (!this.currentUser) throw new Error('Not logged in');
    return this.currentUser.role;
  }

  getCurrentUser(): User {
    if (!this.currentUser) throw new Error('Not logged in');
    return this.currentUser;
  }

  async verifyConnection(): Promise<{ connected: boolean; user?: Record<string, unknown>; error?: string }> {
    try {
      const res = await this.get<ApiResponse<Record<string, unknown>>>('/api/v1/users/me');
      return { connected: true, user: res.data };
    } catch (err) {
      return { connected: false, error: err instanceof Error ? err.message : String(err) };
    }
  }
}
