# WFCS Auction — Claude Agent Onboarding

A role-aware onboarding site for The Well Foundation's Claude agent for the Auction app. Users log in with their existing WFCS Auction account, get redirected to their role-specific page, and see curated prompts they can copy directly into Claude.

---

## Roles

- **Bidder** — browse and bid on auction items
- **Donor** — submit items for auction
- **Admin** — manage auctions, items, users, and settings

## Local development

Requires Galvani running from the git root:

```bash
# From /Users/waseem/Sites/www/
./galvani

# Site available at:
http://localhost:8080/auction/mcp/auction-mcp-docs/index.html?nocache=1

# Note: always use ?nocache=1 when testing after JS changes
```

## Deployment

This folder is a subtree that syncs to the public repo `waseemsadiq/auction-mcp` on every push to master. GitHub Pages serves it from the `main` branch.

Never edit the public repo directly — all changes go through this private repo.
