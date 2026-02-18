import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerDonationTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'my_donations',
    'View all items you have donated to the auction, including their current status and bid activity.',
    {},
    async () => {
      const res = await client.get<ApiResponse<unknown[]>>('/api/v1/users/me/donations');
      const items = res.data;

      if (items.length === 0) {
        return { content: [{ type: 'text', text: 'You have not donated any items yet.' }] };
      }

      return {
        content: [{
          type: 'text',
          text: `Your donated items (${items.length}):\n\n${JSON.stringify(items, null, 2)}`,
        }],
      };
    }
  );
}
