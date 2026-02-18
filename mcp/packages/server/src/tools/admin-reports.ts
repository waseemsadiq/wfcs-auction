import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { ApiClient } from '../api-client.js';
import { ApiResponse } from '../types.js';

export function registerAdminReportTools(server: McpServer, client: ApiClient): void {

  server.tool(
    'admin_reports',
    'Admin: view revenue and activity reports. Includes total revenue, payment breakdown by status, and bids placed today.',
    {},
    async () => {
      const res = await client.get<ApiResponse<Record<string, unknown>>>('/api/admin/v1/reports/revenue');
      const data = res.data;

      const lines = [
        `Revenue Report`,
        `==============`,
        `Total Revenue:  £${(data['total_revenue'] as number)?.toFixed(2) ?? '0.00'}`,
        `Bids Today:     ${data['bids_today'] ?? 0}`,
        ``,
        `Payments by Status:`,
        JSON.stringify(data['by_status'], null, 2),
      ];

      return {
        content: [{ type: 'text', text: lines.join('\n') }],
      };
    }
  );
}
