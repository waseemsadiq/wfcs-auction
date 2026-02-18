import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerAdminSettingsTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'admin_settings',
    'Admin: view or update application settings. Sensitive keys (Stripe secrets) are masked and cannot be updated via this tool.',
    {
      action:  z.enum(['get', 'update']).describe('Action to perform'),
      updates: z.record(z.string()).optional().describe('Key-value pairs to update (for update action)'),
    },
    async ({ action, updates }) => {
      if (action === 'get') {
        const res = await client.get<ApiResponse<Record<string, string>>>('/api/admin/v1/settings');
        return {
          content: [{
            type: 'text',
            text: `Application Settings:\n\n${JSON.stringify(res.data, null, 2)}`,
          }],
        };
      }

      if (!updates || Object.keys(updates).length === 0) {
        throw new Error('updates is required for action=update');
      }

      const res = await client.put<ApiResponse<Record<string, unknown>>>('/api/admin/v1/settings', updates);
      return {
        content: [{
          type: 'text',
          text: `Settings updated:\n\n${JSON.stringify(res.data, null, 2)}`,
        }],
      };
    }
  );
}
