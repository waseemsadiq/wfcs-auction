import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerAdminPaymentTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'admin_payments',
    'Admin: list payment records. Filter by status (pending, completed, failed, refunded).',
    {
      status:   z.enum(['pending', 'completed', 'failed', 'refunded']).optional().describe('Filter by payment status'),
      page:     z.number().int().positive().optional().describe('Page number'),
      per_page: z.number().int().positive().optional().describe('Results per page'),
    },
    async ({ status, page, per_page }) => {
      const query: Record<string, string> = {};
      if (status)   query['status']   = status;
      if (page)     query['page']     = String(page);
      if (per_page) query['per_page'] = String(per_page);

      const res = await client.get<ApiResponse<unknown[]>>('/api/admin/v1/payments', query);
      const total = res.meta?.total ?? res.data.length;

      return {
        content: [{
          type: 'text',
          text: `${total} payment(s)${status ? ` (${status})` : ''}:\n\n${JSON.stringify(res.data, null, 2)}${
            res.meta ? `\n\nPage ${res.meta.page} of ${res.meta.pages}` : ''
          }`,
        }],
      };
    }
  );

  server.tool(
    'admin_gift_aid',
    'Admin: view Gift Aid overview — statistics and recent claims.',
    {},
    async () => {
      const res = await client.get<ApiResponse<Record<string, unknown>>>('/api/admin/v1/gift-aid');
      return {
        content: [{
          type: 'text',
          text: `Gift Aid overview:\n\n${JSON.stringify(res.data, null, 2)}`,
        }],
      };
    }
  );
}
