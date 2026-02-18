export interface Config {
  apiUrl: string;
  email: string;
  password: string;
}

export function loadConfig(): Config {
  const apiUrl   = process.env.WFCS_API_URL   ?? '';
  const email    = process.env.WFCS_EMAIL      ?? '';
  const password = process.env.WFCS_PASSWORD   ?? '';

  if (!apiUrl)   throw new Error('WFCS_API_URL environment variable is required');
  if (!email)    throw new Error('WFCS_EMAIL environment variable is required');
  if (!password) throw new Error('WFCS_PASSWORD environment variable is required');

  return { apiUrl: apiUrl.replace(/\/$/, ''), email, password };
}
