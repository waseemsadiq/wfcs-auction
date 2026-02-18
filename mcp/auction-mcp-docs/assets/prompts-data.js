// WFCS Auction MCP — Prompt library data
window.PROMPTS = {
  bidder: [
    // Events
    { id: 'b1', category: 'events', difficulty: 'easy', text: 'List all active auctions.' },
    { id: 'b2', category: 'events', difficulty: 'easy', text: 'Show me the upcoming auctions.' },
    { id: 'b3', category: 'events', difficulty: 'easy', text: 'What auctions are currently open for bidding?' },

    // Items
    { id: 'b4', category: 'items', difficulty: 'easy', text: 'Show me all the items in the Spring Gala auction.' },
    { id: 'b5', category: 'items', difficulty: 'easy', text: 'Browse items in the Art category.' },
    { id: 'b6', category: 'items', difficulty: 'intermediate', text: 'Find items in the Art category under £100.' },
    { id: 'b7', category: 'items', difficulty: 'intermediate', text: 'Search for "golf" in the auction items.' },
    { id: 'b8', category: 'items', difficulty: 'easy', text: 'Show me the details for item [item-slug].' },
    { id: 'b9', category: 'items', difficulty: 'intermediate', text: 'Which items have a buy-now option?' },
    { id: 'b10', category: 'items', difficulty: 'intermediate', text: 'Find items where I\'m currently the highest bidder.' },

    // Bidding
    { id: 'b11', category: 'bidding', difficulty: 'easy', text: 'Place a £50 bid on [item-slug].' },
    { id: 'b12', category: 'bidding', difficulty: 'easy', text: 'Buy now on [item-slug].' },
    { id: 'b13', category: 'bidding', difficulty: 'intermediate', text: 'What\'s the current highest bid on [item-slug]?' },
    { id: 'b14', category: 'bidding', difficulty: 'intermediate', text: 'What\'s the minimum bid I need to place on [item-slug]?' },

    // My Bids
    { id: 'b15', category: 'my bids', difficulty: 'easy', text: 'Show my current bids.' },
    { id: 'b16', category: 'my bids', difficulty: 'easy', text: 'Which bids am I currently winning?' },
    { id: 'b17', category: 'my bids', difficulty: 'intermediate', text: 'Show my current bids and which ones I\'m winning.' },
    { id: 'b18', category: 'my bids', difficulty: 'intermediate', text: 'Have I won any items?' },
    { id: 'b19', category: 'my bids', difficulty: 'intermediate', text: 'Show me all items I\'ve won that need payment.' },

    // Profile
    { id: 'b20', category: 'profile', difficulty: 'easy', text: 'Show my profile.' },
    { id: 'b21', category: 'profile', difficulty: 'easy', text: 'Am I eligible for Gift Aid?' },
  ],

  donor: [
    // Donations
    { id: 'd1', category: 'donations', difficulty: 'easy', text: 'Show all my donated items.' },
    { id: 'd2', category: 'donations', difficulty: 'easy', text: 'Which of my donations have sold?' },
    { id: 'd3', category: 'donations', difficulty: 'intermediate', text: 'How much have my donations raised in total?' },
    { id: 'd4', category: 'donations', difficulty: 'easy', text: 'Show the current bid on each of my donated items.' },
    { id: 'd5', category: 'donations', difficulty: 'intermediate', text: 'Which of my donations still have no bids?' },
    { id: 'd6', category: 'donations', difficulty: 'intermediate', text: 'Show me a summary of all my donated items and their status.' },

    // Items
    { id: 'd7', category: 'items', difficulty: 'easy', text: 'Browse the current auction items.' },
    { id: 'd8', category: 'items', difficulty: 'easy', text: 'Show me items in [event-name].' },

    // Profile
    { id: 'd9', category: 'profile', difficulty: 'easy', text: 'Show my donor profile.' },
    { id: 'd10', category: 'profile', difficulty: 'easy', text: 'Check my Gift Aid status.' },
  ],

  admin: [
    // Auctions
    { id: 'a1', category: 'auctions', difficulty: 'easy', text: 'List all auctions including drafts.' },
    { id: 'a2', category: 'auctions', difficulty: 'easy', text: 'Show the details of the Spring Gala auction.' },
    { id: 'a3', category: 'auctions', difficulty: 'intermediate', text: 'Create a new auction: "Spring Gala 2026", starting 15 March 2026 at 18:00, ending 22:00, venue "Edinburgh Marriott".' },
    { id: 'a4', category: 'auctions', difficulty: 'easy', text: 'Publish the Spring Gala auction.' },
    { id: 'a5', category: 'auctions', difficulty: 'easy', text: 'Open bidding on the Spring Gala.' },
    { id: 'a6', category: 'auctions', difficulty: 'advanced', text: 'End the Spring Gala auction and determine winners.' },
    { id: 'a7', category: 'auctions', difficulty: 'intermediate', text: 'Update the venue for the Spring Gala to "Glasgow Hilton".' },
    { id: 'a8', category: 'auctions', difficulty: 'easy', text: 'How many items are in each auction?' },

    // Items
    { id: 'a9', category: 'items', difficulty: 'easy', text: 'List all items in the Spring Gala auction.' },
    { id: 'a10', category: 'items', difficulty: 'easy', text: 'Show all draft items.' },
    { id: 'a11', category: 'items', difficulty: 'easy', text: 'Show the details of item [slug].' },
    { id: 'a12', category: 'items', difficulty: 'intermediate', text: 'Set the starting bid for [item-slug] to £75.' },
    { id: 'a13', category: 'items', difficulty: 'intermediate', text: 'Update the lot number for [item-slug] to Lot 12.' },
    { id: 'a14', category: 'items', difficulty: 'intermediate', text: 'Set a buy-now price of £200 on [item-slug].' },

    // Users
    { id: 'a15', category: 'users', difficulty: 'easy', text: 'List all users.' },
    { id: 'a16', category: 'users', difficulty: 'easy', text: 'Find users with the email "example@email.com".' },
    { id: 'a17', category: 'users', difficulty: 'easy', text: 'Show me all admin users.' },
    { id: 'a18', category: 'users', difficulty: 'intermediate', text: 'Change [user-slug]\'s role to donor.' },
    { id: 'a19', category: 'users', difficulty: 'easy', text: 'How many bidders do we have registered?' },

    // Payments
    { id: 'a20', category: 'payments', difficulty: 'easy', text: 'Show all pending payments.' },
    { id: 'a21', category: 'payments', difficulty: 'easy', text: 'List all completed payments.' },
    { id: 'a22', category: 'payments', difficulty: 'intermediate', text: 'How many payments are outstanding?' },

    // Gift Aid
    { id: 'a23', category: 'gift-aid', difficulty: 'easy', text: 'Show the Gift Aid overview.' },
    { id: 'a24', category: 'gift-aid', difficulty: 'intermediate', text: 'How much Gift Aid have we claimed this year?' },

    // Reports
    { id: 'a25', category: 'reports', difficulty: 'intermediate', text: 'Show today\'s bidding activity and revenue.' },
    { id: 'a26', category: 'reports', difficulty: 'advanced', text: 'Give me a revenue summary including Gift Aid for the trustees.' },
    { id: 'a27', category: 'reports', difficulty: 'intermediate', text: 'How many bids have been placed today?' },
    { id: 'a28', category: 'reports', difficulty: 'advanced', text: 'Generate a full report for the Spring Gala auction.' },

    // Settings
    { id: 'a29', category: 'settings', difficulty: 'easy', text: 'Show the current application settings.' },
    { id: 'a30', category: 'settings', difficulty: 'intermediate', text: 'Update the charity name setting.' },

    // Live
    { id: 'a31', category: 'live', difficulty: 'easy', text: 'What item is currently live on the projector?' },
    { id: 'a32', category: 'live', difficulty: 'easy', text: 'Set [item-slug] as the live auction item.' },
    { id: 'a33', category: 'live', difficulty: 'easy', text: 'Stop the live auction screen.' },
    { id: 'a34', category: 'live', difficulty: 'intermediate', text: 'Is bidding currently paused?' },
  ],
};

window.CATEGORIES = {
  bidder: ['all', 'events', 'items', 'bidding', 'my bids', 'profile'],
  donor:  ['all', 'donations', 'items', 'profile'],
  admin:  ['all', 'auctions', 'items', 'users', 'payments', 'gift-aid', 'reports', 'settings', 'live'],
};
