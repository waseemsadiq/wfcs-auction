import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerProfileTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'my_profile',
    'View your profile and account details.',
    {
      action: z.enum(['view']).describe('Action to perform').default('view'),
    },
    async () => {
      const res = await client.get<ApiResponse<Record<string, unknown>>>('/api/v1/users/me');
      return {
        content: [{
          type: 'text',
          text: `Your profile:\n\n${JSON.stringify(res.data, null, 2)}`,
        }],
      };
    }
  );

  server.tool(
    'my_bids',
    'View your bid history including current status (winning/outbid) and items you\'ve won.',
    {
      page:     z.number().int().positive().optional().describe('Page number'),
      per_page: z.number().int().positive().optional().describe('Results per page (max 100)'),
    },
    async ({ page, per_page }) => {
      const query: Record<string, string> = {};
      if (page)     query['page']     = String(page);
      if (per_page) query['per_page'] = String(per_page);

      const res = await client.get<ApiResponse<unknown[]>>('/api/v1/users/me/bids', query);
      const total = res.meta?.total ?? res.data.length;

      return {
        content: [{
          type: 'text',
          text: `Your bids (${total} total):\n\n${JSON.stringify(res.data, null, 2)}${
            res.meta ? `\n\nPage ${res.meta.page} of ${res.meta.pages}` : ''
          }`,
        }],
      };
    }
  );
}
