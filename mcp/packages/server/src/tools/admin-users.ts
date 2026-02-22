import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerAdminUserTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'manage_users',
    'Admin: manage users. Actions: list (with optional search/role filter), get (single user), update (role/profile/email), delete (permanently erase user and all their data — super admins can also delete admin accounts).',
    {
      action:    z.enum(['list', 'get', 'update', 'delete']).describe('Action to perform'),
      slug:      z.string().optional().describe('User slug (required for get, update)'),
      q:         z.string().optional().describe('Search by name or email (for list)'),
      role:      z.string().optional().describe('Filter by role: bidder, donor, admin (for list)'),
      page:      z.number().int().positive().optional().describe('Page number'),
      per_page:  z.number().int().positive().optional().describe('Results per page'),
      new_role:  z.enum(['bidder', 'donor', 'admin']).optional().describe('New role (for update)'),
      name:      z.string().optional().describe('New display name (for update)'),
      phone:     z.string().optional().describe('New phone number (for update)'),
      new_email: z.string().email().optional().describe('New email address (for update — sends verification email to new address; admin accounts excluded)'),
    },
    async ({ action, slug, q, role, page, per_page, new_role, name, phone, new_email }) => {
      let text: string;

      switch (action) {
        case 'list': {
          const query: Record<string, string> = {};
          if (q)        query['q']        = q;
          if (role)     query['role']     = role;
          if (page)     query['page']     = String(page);
          if (per_page) query['per_page'] = String(per_page);
          const res = await client.get<ApiResponse<unknown[]>>('/api/admin/v1/users', query);
          text = `${res.meta?.total ?? res.data.length} user(s):\n\n${JSON.stringify(res.data, null, 2)}`;
          if (res.meta) text += `\n\nPage ${res.meta.page} of ${res.meta.pages}`;
          break;
        }

        case 'get': {
          if (!slug) throw new Error('slug is required');
          const res = await client.get<ApiResponse<unknown>>(`/api/admin/v1/users/${slug}`);
          text = JSON.stringify(res.data, null, 2);
          break;
        }

        case 'update': {
          if (!slug) throw new Error('slug is required');
          const body: Record<string, unknown> = {};
          if (new_role)  body['role']  = new_role;
          if (name)      body['name']  = name;
          if (phone)     body['phone'] = phone;
          if (new_email) body['email'] = new_email;
          const res = await client.put<ApiResponse<unknown>>(`/api/admin/v1/users/${slug}`, body);
          text = `User updated:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }

        case 'delete': {
          if (!slug) throw new Error('slug is required');
          const res = await client.delete<ApiResponse<{ message: string }>>(`/api/admin/v1/users/${slug}`);
          text = res.data?.message ?? 'User deleted.';
          break;
        }
      }

      return { content: [{ type: 'text', text: text! }] };
    }
  );
}
