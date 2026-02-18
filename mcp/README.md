# WFCS Auction MCP Server

A role-aware [Model Context Protocol](https://modelcontextprotocol.io/) server for the WFCS Auction app. Connects Claude to your auction API with tool access determined by the authenticated user's role.

## Roles and Tools

| Tool | Bidder | Donor | Admin |
|------|--------|-------|-------|
| `verify_connection` | ✓ | ✓ | ✓ |
| `browse_events` | ✓ | ✓ | ✓ |
| `browse_items` | ✓ | ✓ | ✓ |
| `my_profile` | ✓ | ✓ | ✓ |
| `my_bids` | ✓ | — | ✓ |
| `place_bid` | ✓ | — | ✓ |
| `my_donations` | — | ✓ | ✓ |
| `manage_auctions` | — | — | ✓ |
| `manage_items` | — | — | ✓ |
| `manage_users` | — | — | ✓ |
| `admin_payments` | — | — | ✓ |
| `admin_gift_aid` | — | — | ✓ |
| `admin_reports` | — | — | ✓ |
| `admin_settings` | — | — | ✓ |
| `manage_live` | — | — | ✓ |

## Quick Start

### Prerequisites
- Node.js 18+
- WFCS Auction app running (Galvani dev or LAMP production)

### Setup

```bash
cd auction/mcp
npm install
npm run build
```

### Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `WFCS_API_URL` | Base URL of the auction app | `http://localhost:8080/auction` |
| `WFCS_EMAIL` | Your auction account email | `admin@wellfoundation.org.uk` |
| `WFCS_PASSWORD` | Your auction account password | `Admin1234!` |

## Claude Desktop Configuration

Add to `~/Library/Application Support/Claude/claude_desktop_config.json` (macOS):

```json
{
  "mcpServers": {
    "wfcs-auction": {
      "command": "node",
      "args": ["/path/to/auction/mcp/packages/server/dist/index.js"],
      "env": {
        "WFCS_API_URL": "http://localhost:8080/auction",
        "WFCS_EMAIL": "your@email.com",
        "WFCS_PASSWORD": "yourpassword"
      }
    }
  }
}
```

## Claude Code Configuration

Add to your project's `.mcp.json`:

```json
{
  "mcpServers": {
    "wfcs-auction": {
      "command": "node",
      "args": ["mcp/packages/server/dist/index.js"],
      "env": {
        "WFCS_API_URL": "http://localhost:8080/auction",
        "WFCS_EMAIL": "your@email.com",
        "WFCS_PASSWORD": "yourpassword"
      }
    }
  }
}
```

## Auth Notes

- Token is passed as `?token=JWT` query param on all requests (Galvani drops Authorization headers)
- Token is valid for 1 year; the client auto re-authenticates on 401
- No CSRF required for `/api/` routes

## Development

```bash
# Watch mode (recompiles on change)
cd mcp/packages/server
npm run dev

# Run server directly
WFCS_API_URL=http://localhost:8080/auction \
WFCS_EMAIL=admin@wellfoundation.org.uk \
WFCS_PASSWORD=Admin1234! \
node dist/index.js
```

## Admin Prompts

Admins get three built-in prompts registered in the MCP server. Access via `/prompts` in Claude:

- **daily-briefing** — Bids today, revenue, live status, outstanding payments
- **auction-report** — Full report for a specific event (pass `slug` parameter)
- **revenue-summary** — Revenue + Gift Aid summary for trustees

## Docs Site

The `auction-mcp-docs/` directory contains a static HTML documentation site with role-specific prompt libraries.

To serve locally:

```bash
# Python (macOS built-in)
cd mcp/auction-mcp-docs
python3 -m http.server 3000
# Open http://localhost:3000
```

Sign in with your auction credentials — you'll be redirected to prompts matching your role.

### Updating Prompts

Edit `auction-mcp-docs/assets/prompts-data.js` — the `window.PROMPTS` object. Each prompt needs:
- `id` — unique string
- `category` — must match a value in `window.CATEGORIES`
- `difficulty` — `easy`, `intermediate`, or `advanced`
- `text` — the prompt text shown and copied

## Project Structure

```
mcp/
├── packages/server/
│   ├── src/
│   │   ├── index.ts          # Entry point — role detection + tool registration
│   │   ├── api-client.ts     # HTTP client (token via query param)
│   │   ├── config.ts         # Env var loader
│   │   ├── types.ts          # TypeScript interfaces
│   │   └── tools/
│   │       ├── discovery.ts       # verify_connection, browse_events, browse_items
│   │       ├── profile.ts         # my_profile, my_bids
│   │       ├── bidding.ts         # place_bid
│   │       ├── donations.ts       # my_donations
│   │       ├── admin-auctions.ts  # manage_auctions
│   │       ├── admin-items.ts     # manage_items
│   │       ├── admin-users.ts     # manage_users
│   │       ├── admin-payments.ts  # admin_payments, admin_gift_aid
│   │       ├── admin-reports.ts   # admin_reports
│   │       ├── admin-settings.ts  # admin_settings
│   │       └── admin-live.ts      # manage_live
│   └── dist/                 # Compiled output (git-ignored)
└── auction-mcp-docs/
    ├── index.html            # Login page
    ├── bidder/               # Bidder guide + prompts
    ├── donor/                # Donor guide + prompts
    ├── admin/                # Admin guide + prompts
    └── assets/               # CSS, JS, prompt data
```
