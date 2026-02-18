import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerBiddingTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'place_bid',
    'Place a bid on an auction item. Use buy_now=true to buy immediately at the buy-now price.',
    {
      item_slug: z.string().describe('The slug of the item to bid on'),
      amount:    z.number().positive().describe('Bid amount in GBP (e.g. 50 for £50)'),
      buy_now:   z.boolean().optional().describe('Set to true to buy now at the buy-now price'),
    },
    async ({ item_slug, amount, buy_now }) => {
      const res = await client.post<ApiResponse<Record<string, unknown>>>('/api/v1/bids', {
        item_slug,
        amount,
        buy_now: buy_now ? 1 : 0,
      });

      return {
        content: [{
          type: 'text',
          text: `Bid placed successfully!\n\n${JSON.stringify(res.data, null, 2)}`,
        }],
      };
    }
  );
}
