import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse, Event, Item } from '../types.js';

export function registerDiscoveryTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'verify_connection',
    'Test the connection to the WFCS Auction API and return current user info.',
    {},
    async () => {
      const result = await client.verifyConnection();
      return {
        content: [{
          type: 'text',
          text: result.connected
            ? `Connected successfully.\n\nUser:\n${JSON.stringify(result.user, null, 2)}`
            : `Connection failed: ${result.error}`,
        }],
      };
    }
  );

  server.tool(
    'browse_events',
    'Browse auction events. Actions: list (all events), get (single event by slug), get_items (items in an event).',
    {
      action:   z.enum(['list', 'get', 'get_items']).describe('What to do'),
      slug:     z.string().optional().describe('Event slug (required for get, get_items)'),
      status:   z.string().optional().describe('Filter by status: active, published (for list)'),
      page:     z.number().int().positive().optional().describe('Page number'),
      per_page: z.number().int().positive().optional().describe('Results per page (max 100)'),
    },
    async ({ action, slug, status, page, per_page }) => {
      let text: string;

      if (action === 'list') {
        const query: Record<string, string> = {};
        if (status)   query['status']   = status;
        if (page)     query['page']     = String(page);
        if (per_page) query['per_page'] = String(per_page);

        const res = await client.get<ApiResponse<Event[]>>('/api/v1/events', query);
        text = `Found ${res.meta?.total ?? res.data.length} event(s):\n\n${JSON.stringify(res.data, null, 2)}`;
        if (res.meta) text += `\n\nPage ${res.meta.page} of ${res.meta.pages}`;

      } else if (action === 'get') {
        if (!slug) throw new Error('slug is required for action=get');
        const res = await client.get<ApiResponse<Event>>(`/api/v1/events/${slug}`);
        text = JSON.stringify(res.data, null, 2);

      } else {
        if (!slug) throw new Error('slug is required for action=get_items');
        const query: Record<string, string> = {};
        if (page)     query['page']     = String(page);
        if (per_page) query['per_page'] = String(per_page);
        const res = await client.get<ApiResponse<Item[]>>(`/api/v1/events/${slug}/items`, query);
        text = `Found ${res.data.length} item(s) in event "${slug}":\n\n${JSON.stringify(res.data, null, 2)}`;
      }

      return { content: [{ type: 'text', text }] };
    }
  );

  server.tool(
    'browse_items',
    'Browse auction items. Actions: list (all items with filters), get (single item by slug), search (search by keyword).',
    {
      action:   z.enum(['list', 'get', 'search']).describe('What to do'),
      slug:     z.string().optional().describe('Item slug (required for get)'),
      q:        z.string().optional().describe('Search query (for list/search)'),
      category: z.string().optional().describe('Category slug filter'),
      event:    z.string().optional().describe('Event slug filter'),
      page:     z.number().int().positive().optional().describe('Page number'),
      per_page: z.number().int().positive().optional().describe('Results per page (max 100)'),
    },
    async ({ action, slug, q, category, event, page, per_page }) => {
      let text: string;

      if (action === 'get') {
        if (!slug) throw new Error('slug is required for action=get');
        const res = await client.get<ApiResponse<Item>>(`/api/v1/items/${slug}`);
        text = JSON.stringify(res.data, null, 2);

      } else {
        const query: Record<string, string> = {};
        if (q)        query['q']        = q;
        if (category) query['category'] = category;
        if (event)    query['event']    = event;
        if (page)     query['page']     = String(page);
        if (per_page) query['per_page'] = String(per_page);

        const res = await client.get<ApiResponse<Item[]>>('/api/v1/items', query);
        text = `Found ${res.meta?.total ?? res.data.length} item(s):\n\n${JSON.stringify(res.data, null, 2)}`;
        if (res.meta) text += `\n\nPage ${res.meta.page} of ${res.meta.pages}`;
      }

      return { content: [{ type: 'text', text }] };
    }
  );
}
