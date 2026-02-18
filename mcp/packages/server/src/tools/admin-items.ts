import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse, Item } from '../types.js';

export function registerAdminItemTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'manage_items',
    'Admin: manage auction items. Actions: list, get, update.',
    {
      action:       z.enum(['list', 'get', 'update']).describe('Action to perform'),
      slug:         z.string().optional().describe('Item slug (required for get, update)'),
      status:       z.string().optional().describe('Filter by status: draft, active, ended, sold (for list)'),
      event_slug:   z.string().optional().describe('Filter by event slug (for list)'),
      page:         z.number().int().positive().optional().describe('Page number'),
      per_page:     z.number().int().positive().optional().describe('Results per page'),
      title:        z.string().optional().describe('New title (for update)'),
      description:  z.string().optional().describe('New description (for update)'),
      lot_number:   z.string().optional().describe('Lot number (for update)'),
      starting_bid: z.number().positive().optional().describe('Starting bid in GBP (for update)'),
      min_increment: z.number().positive().optional().describe('Minimum bid increment in GBP (for update)'),
      buy_now_price: z.number().positive().optional().describe('Buy-now price in GBP (for update)'),
      market_value:  z.number().positive().optional().describe('Market value in GBP (for update)'),
      new_status:   z.string().optional().describe('New status (draft, active, ended, sold) (for update)'),
    },
    async ({ action, slug, status, event_slug, page, per_page, title, description, lot_number,
             starting_bid, min_increment, buy_now_price, market_value, new_status }) => {
      let text: string;

      switch (action) {
        case 'list': {
          const query: Record<string, string> = {};
          if (status)     query['status']     = status;
          if (event_slug) query['event_slug'] = event_slug;
          if (page)       query['page']       = String(page);
          if (per_page)   query['per_page']   = String(per_page);
          const res = await client.get<ApiResponse<Item[]>>('/api/admin/v1/items', query);
          text = `${res.meta?.total ?? res.data.length} item(s):\n\n${JSON.stringify(res.data, null, 2)}`;
          if (res.meta) text += `\n\nPage ${res.meta.page} of ${res.meta.pages}`;
          break;
        }

        case 'get': {
          if (!slug) throw new Error('slug is required');
          const res = await client.get<ApiResponse<Item>>(`/api/admin/v1/items/${slug}`);
          text = JSON.stringify(res.data, null, 2);
          break;
        }

        case 'update': {
          if (!slug) throw new Error('slug is required');
          const body: Record<string, unknown> = {};
          if (title)         body['title']         = title;
          if (description)   body['description']   = description;
          if (lot_number)    body['lot_number']    = lot_number;
          if (starting_bid)  body['starting_bid']  = starting_bid;
          if (min_increment) body['min_increment'] = min_increment;
          if (buy_now_price) body['buy_now_price'] = buy_now_price;
          if (market_value)  body['market_value']  = market_value;
          if (new_status)    body['status']        = new_status;
          const res = await client.put<ApiResponse<Item>>(`/api/admin/v1/items/${slug}`, body);
          text = `Item updated:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }
      }

      return { content: [{ type: 'text', text: text! }] };
    }
  );
}
