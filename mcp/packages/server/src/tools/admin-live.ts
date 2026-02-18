import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerAdminLiveTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'manage_live',
    'Admin: manage the live gala auction screen. Actions: status (current live item), start (set live item), stop (clear live screen).',
    {
      action:    z.enum(['status', 'start', 'stop']).describe('Action to perform'),
      item_slug: z.string().optional().describe('Item slug to go live (required for start)'),
    },
    async ({ action, item_slug }) => {
      let text: string;

      switch (action) {
        case 'status': {
          const res = await client.get<ApiResponse<Record<string, unknown>>>('/api/admin/v1/live');
          const data = res.data;
          if (!data['live_item']) {
            text = 'No item is currently live.';
          } else {
            text = `Live item:\n\n${JSON.stringify(data['live_item'], null, 2)}\n\nBidding paused: ${data['bidding_paused']}`;
          }
          break;
        }

        case 'start': {
          if (!item_slug) throw new Error('item_slug is required to start a live auction');
          const res = await client.post<ApiResponse<Record<string, unknown>>>('/api/admin/v1/live/start', { item_slug });
          text = `Live auction started:\n\n${JSON.stringify(res.data['live_item'], null, 2)}`;
          break;
        }

        case 'stop': {
          await client.post<ApiResponse<unknown>>('/api/admin/v1/live/stop');
          text = 'Live auction stopped. The projector screen has been cleared.';
          break;
        }
      }

      return { content: [{ type: 'text', text: text! }] };
    }
  );
}
