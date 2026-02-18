#!/usr/bin/env node
import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';

import { loadConfig } from './config.js';
import { ApiClient } from './api-client.js';

import { registerDiscoveryTools } from './tools/discovery.js';
import { registerProfileTools } from './tools/profile.js';
import { registerBiddingTools } from './tools/bidding.js';
import { registerDonationTools } from './tools/donations.js';
import { registerAdminAuctionTools } from './tools/admin-auctions.js';
import { registerAdminItemTools } from './tools/admin-items.js';
import { registerAdminUserTools } from './tools/admin-users.js';
import { registerAdminPaymentTools } from './tools/admin-payments.js';
import { registerAdminReportTools } from './tools/admin-reports.js';
import { registerAdminSettingsTools } from './tools/admin-settings.js';
import { registerAdminLiveTools } from './tools/admin-live.js';

async function main(): Promise<void> {
  const config = loadConfig();
  const client = new ApiClient(config);

  await client.login();
  const role = client.getRole();
  const user = client.getCurrentUser();

  const server = new McpServer({
    name: 'wfcs-auction',
    version: '1.0.0',
  });

  // ---- Tools available to ALL roles -----------------------------------------
  registerDiscoveryTools(server, client);
  registerProfileTools(server, client);

  // ---- Bidder + Admin -------------------------------------------------------
  if (role === 'bidder' || role === 'admin') {
    registerBiddingTools(server, client);
  }

  // ---- Donor + Admin --------------------------------------------------------
  if (role === 'donor' || role === 'admin') {
    registerDonationTools(server, client);
  }

  // ---- Admin only -----------------------------------------------------------
  if (role === 'admin') {
    registerAdminAuctionTools(server, client);
    registerAdminItemTools(server, client);
    registerAdminUserTools(server, client);
    registerAdminPaymentTools(server, client);
    registerAdminReportTools(server, client);
    registerAdminSettingsTools(server, client);
    registerAdminLiveTools(server, client);
  }

  // ---- Resources ------------------------------------------------------------
  server.resource(
    'wfcs://status',
    'wfcs://status',
    { mimeType: 'application/json' },
    async () => ({
      contents: [{
        uri: 'wfcs://status',
        mimeType: 'application/json',
        text: JSON.stringify({
          connected: true,
          role,
          user: { id: user.id, name: user.name, email: user.email, role: user.role },
          api_url: config.apiUrl,
        }, null, 2),
      }],
    })
  );

  server.resource(
    'wfcs://role-permissions',
    'wfcs://role-permissions',
    { mimeType: 'application/json' },
    async () => {
      const permissions: Record<string, string[]> = {
        bidder: ['verify_connection', 'browse_events', 'browse_items', 'my_profile', 'my_bids', 'place_bid'],
        donor:  ['verify_connection', 'browse_events', 'browse_items', 'my_profile', 'my_donations'],
        admin:  [
          'verify_connection', 'browse_events', 'browse_items',
          'my_profile', 'my_bids', 'place_bid', 'my_donations',
          'manage_auctions', 'manage_items', 'manage_users',
          'admin_payments', 'admin_gift_aid', 'admin_reports',
          'admin_settings', 'manage_live',
        ],
      };
      return {
        contents: [{
          uri: 'wfcs://role-permissions',
          mimeType: 'application/json',
          text: JSON.stringify({
            current_role: role,
            available_tools: permissions[role] ?? [],
            all_roles: permissions,
          }, null, 2),
        }],
      };
    }
  );

  // ---- Admin prompts --------------------------------------------------------
  if (role === 'admin') {
    server.prompt(
      'daily-briefing',
      'Generate a daily briefing of auction activity: bids today, revenue, and live status.',
      {},
      async () => ({
        messages: [{
          role: 'user',
          content: {
            type: 'text',
            text: [
              'Please give me a daily briefing for the WFCS Auction:',
              '1. Use admin_reports to get today\'s bid count and total revenue',
              '2. Use manage_live to check if there\'s a live auction running',
              '3. Use admin_payments with status=pending to count outstanding payments',
              '4. Summarise in a concise briefing suitable for a charity admin',
            ].join('\n'),
          },
        }],
      })
    );

    server.prompt(
      'auction-report',
      'Generate a full report for a specific auction event.',
      {
        slug: z.string().describe('The event slug to report on'),
      },
      async ({ slug }) => ({
        messages: [{
          role: 'user',
          content: {
            type: 'text',
            text: [
              `Please generate a full report for the auction event "${slug}":`,
              '1. Use manage_auctions action=get to get event details',
              '2. Use manage_items with event_slug to list all items',
              '3. Use admin_payments to get payment status',
              '4. Summarise: total items, bids received, revenue raised, outstanding payments',
            ].join('\n'),
          },
        }],
      })
    );

    server.prompt(
      'revenue-summary',
      'Generate a revenue summary across all auctions.',
      {},
      async () => ({
        messages: [{
          role: 'user',
          content: {
            type: 'text',
            text: [
              'Please generate a revenue summary for the WFCS Auction:',
              '1. Use admin_reports to get total revenue and payment breakdown',
              '2. Use admin_gift_aid to get Gift Aid statistics',
              '3. Summarise total raised, Gift Aid value, and outstanding payments',
              '4. Format the output as a clean summary suitable for charity trustees',
            ].join('\n'),
          },
        }],
      })
    );
  }

  // ---- Start server ---------------------------------------------------------
  const transport = new StdioServerTransport();
  await server.connect(transport);
}

main().catch((err) => {
  process.stderr.write(`Fatal error: ${err instanceof Error ? err.message : String(err)}\n`);
  process.exit(1);
});
