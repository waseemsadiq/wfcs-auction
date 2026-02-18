import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { ApiClient } from '../api-client.js';
import { ApiResponse, Event } from '../types.js';

export function registerAdminAuctionTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'manage_auctions',
    'Admin: manage auction events. Actions: list, get, create, update, publish, open, end.',
    {
      action:      z.enum(['list', 'get', 'create', 'update', 'publish', 'open', 'end']).describe('Action to perform'),
      slug:        z.string().optional().describe('Event slug (required for get, update, publish, open, end)'),
      title:       z.string().optional().describe('Event title (required for create)'),
      description: z.string().optional().describe('Event description'),
      venue:       z.string().optional().describe('Venue name/address'),
      starts_at:   z.string().optional().describe('Start date/time (ISO 8601, e.g. 2026-03-15T18:00:00)'),
      ends_at:     z.string().optional().describe('End date/time (ISO 8601)'),
      page:        z.number().int().positive().optional().describe('Page number (for list)'),
      per_page:    z.number().int().positive().optional().describe('Results per page (for list)'),
      confirmed:   z.boolean().optional().describe('Set to true to confirm destructive actions (end)'),
    },
    async ({ action, slug, title, description, venue, starts_at, ends_at, page, per_page, confirmed }) => {
      let text: string;

      switch (action) {
        case 'list': {
          const query: Record<string, string> = {};
          if (page)     query['page']     = String(page);
          if (per_page) query['per_page'] = String(per_page);
          const res = await client.get<ApiResponse<Event[]>>('/api/admin/v1/auctions', query);
          text = `${res.meta?.total ?? res.data.length} auction(s):\n\n${JSON.stringify(res.data, null, 2)}`;
          if (res.meta) text += `\n\nPage ${res.meta.page} of ${res.meta.pages}`;
          break;
        }

        case 'get': {
          if (!slug) throw new Error('slug is required');
          const res = await client.get<ApiResponse<Event>>(`/api/admin/v1/auctions/${slug}`);
          text = JSON.stringify(res.data, null, 2);
          break;
        }

        case 'create': {
          if (!title) throw new Error('title is required');
          const body: Record<string, unknown> = { title };
          if (description) body['description'] = description;
          if (venue)       body['venue']       = venue;
          if (starts_at)   body['starts_at']   = starts_at;
          if (ends_at)     body['ends_at']     = ends_at;
          const res = await client.post<ApiResponse<Event>>('/api/admin/v1/auctions', body);
          text = `Auction created:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }

        case 'update': {
          if (!slug) throw new Error('slug is required');
          const body: Record<string, unknown> = {};
          if (title)       body['title']       = title;
          if (description) body['description'] = description;
          if (venue)       body['venue']       = venue;
          if (starts_at)   body['starts_at']   = starts_at;
          if (ends_at)     body['ends_at']     = ends_at;
          const res = await client.put<ApiResponse<Event>>(`/api/admin/v1/auctions/${slug}`, body);
          text = `Auction updated:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }

        case 'publish': {
          if (!slug) throw new Error('slug is required');
          const res = await client.post<ApiResponse<Event>>(`/api/admin/v1/auctions/${slug}/publish`);
          text = `Auction published:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }

        case 'open': {
          if (!slug) throw new Error('slug is required');
          const res = await client.post<ApiResponse<Event>>(`/api/admin/v1/auctions/${slug}/open`);
          text = `Auction opened — bidding is now live:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }

        case 'end': {
          if (!slug) throw new Error('slug is required');
          if (!confirmed) {
            text = 'This will end the auction and determine winners. Set confirmed=true to proceed.';
            break;
          }
          const res = await client.post<ApiResponse<Event>>(`/api/admin/v1/auctions/${slug}/end`, { confirmed: true });
          text = `Auction ended — winners determined:\n\n${JSON.stringify(res.data, null, 2)}`;
          break;
        }
      }

      return { content: [{ type: 'text', text: text! }] };
    }
  );
}
