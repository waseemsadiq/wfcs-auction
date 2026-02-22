-- WFCS Auction — Seed Data
-- Comprehensive test data covering every feature and status

-- ─── Categories ────────────────────────────────────────────────────────────
INSERT INTO categories (slug, name) VALUES
  ('art',              'Art'),
  ('experiences',      'Experiences'),
  ('fashion',          'Fashion'),
  ('food-drink',       'Food & Drink'),
  ('health-beauty',    'Health & Beauty'),
  ('holidays-travel',  'Holidays & Travel'),
  ('sports',           'Sports'),
  ('technology',       'Technology');

-- ─── Users ─────────────────────────────────────────────────────────────────
-- All passwords: Admin1234!
-- Hash generated inside Galvani: $2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K

-- id=1 Admin
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('admin-user', 'Admin User', 'admin@wellfoundation.org.uk',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'admin', NOW(), 0, NULL);


INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('waseem-sadiq', 'Waseem Sadiq', 'admin@wfcs.co.uk',
   '$2y$12$ue9Dsx1cea8oAseGKuEelO3J1ahBMLhcVRKAow/sNXjR16FNB7GvG',
   'super_admin', NOW(), 0, NULL);

INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('fahim-baqir', 'Fahim Baqir', 'fahimbaqir@gmail.com',
   '$2y$12$zGJ0lmQryVjtD8wS.WajPeojA67ipZ4/4NnBQ13Y6BBYtyMCEdlYi',
   'super_admin', NOW(), 0, NULL);

-- id=2 Donor 1
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('sarah-jones', 'Sarah Jones', 'donor@example.com',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'donor', NOW(), 0, NULL);

-- id=3 Bidder 1 (with Gift Aid)
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('james-macleod', 'James MacLeod', 'bidder@example.com',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'bidder', NOW(), 1, 'James MacLeod');

-- id=4 Donor 2
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('margaret-campbell', 'Margaret Campbell', 'donor2@example.com',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'donor', NOW(), 0, NULL);

-- id=5 Bidder 2 (with Gift Aid)
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('emma-wilson', 'Emma Wilson', 'bidder2@example.com',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'bidder', NOW(), 1, 'Emma Wilson');

-- id=6 Bidder 3 (no Gift Aid)
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('robert-thomson', 'Robert Thomson', 'bidder3@example.com',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'bidder', NOW(), 0, NULL);

-- id=7 Unverified bidder (email not confirmed)
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at, gift_aid_eligible, gift_aid_name) VALUES
  ('test-unverified', 'Test Unverified', 'unverified@example.com',
   '$2y$12$a/34kLZPaOkDPoLfNW5vh.yLUwPtgUkblHLgxVYyEi1Y.g0clKa8K',
   'bidder', NULL, 0, NULL);

-- ─── Settings ──────────────────────────────────────────────────────────────
INSERT INTO settings (key_name, value) VALUES
  ('charity_name',            'Well Foundation for Community Support (WFCS)'),
  ('charity_number',          'SC040105'),
  ('charity_email',           'info@wellfoundation.org.uk'),
  ('stripe_publishable_key',  ''),
  ('stripe_secret_key',       ''),
  ('stripe_webhook_secret',   ''),
  ('email_from',              'noreply@wellfoundation.org.uk'),
  ('email_from_name',         'WFCS Auction'),
  ('smtp_host',               ''),
  ('smtp_port',               '587'),
  ('smtp_username',           ''),
  ('smtp_password',           ''),
  ('live_event_id',           NULL),
  ('gift_aid_rate',           '0.25'),
  ('stripe_webhook_url_token','');

-- ─── Events ────────────────────────────────────────────────────────────────

-- id=1 ACTIVE — bidding open now
INSERT INTO events (slug, title, description, status, starts_at, ends_at, venue, created_by) VALUES
  ('wfcs-annual-gala-2026',
   'WFCS Annual Gala 2026',
   'Our flagship annual charity auction raising funds for community support programmes across Lanarkshire. Join us for an evening of incredible lots donated by generous supporters.',
   'active',
   DATE_SUB(NOW(), INTERVAL 1 HOUR),
   DATE_ADD(NOW(), INTERVAL 7 DAY),
   'Hilton Glasgow, 1 William Street, Glasgow, G3 8HT',
   1);

-- id=2 ENDED — auction over, awaiting payment/settlement
INSERT INTO events (slug, title, description, status, starts_at, ends_at, venue, created_by) VALUES
  ('winter-wonderland-2025',
   'Winter Wonderland Auction 2025',
   'Our festive Christmas charity auction featuring luxury gifts, experiences, and festive hampers. Bid for a great cause.',
   'ended',
   DATE_SUB(NOW(), INTERVAL 45 DAY),
   DATE_SUB(NOW(), INTERVAL 30 DAY),
   'The Grand Central Hotel, 99 Gordon Street, Glasgow, G1 3SF',
   1);

-- id=3 PUBLISHED — upcoming, not yet open for bidding
INSERT INTO events (slug, title, description, status, starts_at, ends_at, venue, created_by) VALUES
  ('spring-gala-2026',
   'Spring Gala Auction 2026',
   'A wonderful springtime celebration with unique lots from across Scotland and beyond. Early registration open now — secure your place for an unforgettable evening.',
   'published',
   DATE_ADD(NOW(), INTERVAL 30 DAY),
   DATE_ADD(NOW(), INTERVAL 37 DAY),
   'Radisson Blu Hotel, 301 Argyle Street, Glasgow, G2 8DL',
   1);

-- id=4 CLOSED — fully settled
INSERT INTO events (slug, title, description, status, starts_at, ends_at, venue, created_by) VALUES
  ('christmas-gala-2025',
   'Christmas Gala 2025',
   'A magical evening of charity bidding with over 20 lots, live entertainment, and seasonal cheer. All proceeds support WFCS community programmes.',
   'closed',
   DATE_SUB(NOW(), INTERVAL 80 DAY),
   DATE_SUB(NOW(), INTERVAL 65 DAY),
   'Crowne Plaza Glasgow, Congress Road, Glasgow, G3 8QT',
   1);

-- id=5 DRAFT — not yet published
INSERT INTO events (slug, title, description, status, starts_at, ends_at, venue, created_by) VALUES
  ('summer-fete-2026',
   'Summer Fête Auction 2026',
   'Draft event — lots and details being finalised. Outdoor summer event with family-friendly lots and garden experiences.',
   'draft',
   DATE_ADD(NOW(), INTERVAL 120 DAY),
   DATE_ADD(NOW(), INTERVAL 127 DAY),
   'Strathclyde Country Park, Motherwell, ML1 3ED',
   1);

-- ─── Items — Event 1 (ACTIVE) ──────────────────────────────────────────────

-- Lot 1: no bids yet
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('rolex-submariner', 1,
   (SELECT id FROM categories WHERE slug='fashion'), 2,
   'Rolex Submariner Date',
   'A stunning pre-owned Rolex Submariner Date in stainless steel with black dial and ceramic bezel. Includes original box and papers. This iconic diver''s watch is one of the most recognisable luxury timepieces in the world.',
   1, 3500.00, 100.00, NULL, 3500.00, 0, 'active', 4200.00);

-- Lot 2: no bids yet
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('chefs-table-experience', 1,
   (SELECT id FROM categories WHERE slug='experiences'), 2,
   'Chef''s Table Experience for Two',
   'An exclusive chef''s table dining experience for two at a Michelin-starred Glasgow restaurant. Includes a 7-course tasting menu, a kitchen tour, and a personalised recipe card signed by the head chef.',
   2, 800.00, 50.00, NULL, 800.00, 0, 'active', 1850.00);

-- Lot 3: no bids yet
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('loch-lomond-oil-painting', 1,
   (SELECT id FROM categories WHERE slug='art'), 2,
   'Loch Lomond Oil Painting',
   'Original large-format oil painting of Loch Lomond at sunrise by acclaimed Scottish landscape artist. Framed in solid oak. 90cm × 60cm. Certificate of authenticity included.',
   3, 400.00, 25.00, NULL, 400.00, 0, 'active', 750.00);

-- Lot 4: no bids yet
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('gleneagles-golf-package', 1,
   (SELECT id FROM categories WHERE slug='sports'), 2,
   'Gleneagles Golf Package for Two',
   'A two-night stay at the world-renowned Gleneagles Hotel with a full round of golf on the King''s Course for two. Includes breakfast and dinner on both evenings. Valid until December 2026.',
   4, 1500.00, 100.00, NULL, 1500.00, 0, 'active', 2600.00);

-- Lot 5: no bids yet
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('balmoral-spa-day', 1,
   (SELECT id FROM categories WHERE slug='health-beauty'), 2,
   'Balmoral Spa Day for Two',
   'A full-day luxury spa experience at Balmoral Hotel Edinburgh for two people. Includes use of all thermal facilities, a 60-minute treatment each, and afternoon tea in the Palm Court restaurant.',
   5, 700.00, 50.00, NULL, 700.00, 0, 'active', 1400.00);

-- Lot 6: no bids yet
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('amalfi-villa-week', 1,
   (SELECT id FROM categories WHERE slug='holidays-travel'), 2,
   'Amalfi Coast Villa Week',
   'Seven nights in a stunning private villa on the Amalfi Coast sleeping up to 6 guests. Private pool, panoramic sea views, daily breakfast hamper. Available May–October 2026. Flights not included.',
   6, 4000.00, 250.00, NULL, 4000.00, 0, 'active', 8500.00);

-- Lot 7: ACTIVE BID BATTLE — 3 bids, current £925
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('scottish-artisan-hamper', 1,
   (SELECT id FROM categories WHERE slug='food-drink'), 4,
   'Scottish Artisan Food Hamper',
   'A beautifully curated hamper of Scottish artisan produce: smoked salmon from the Outer Hebrides, Highland oatcakes, heather honey, artisan preserves, handcrafted shortbread, and aged Scottish cheddar. Presented in a traditional wicker hamper with ribbon. A truly exceptional gift.',
   7, 600.00, 50.00, NULL, 925.00, 3, 'active', 1200.00);

-- Lot 8: ACTIVE WITH BUY-NOW — 2 bids, current £1800, buy now £2500
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('macbook-pro-m4', 1,
   (SELECT id FROM categories WHERE slug='technology'), 4,
   'Apple MacBook Pro M4 (14-inch)',
   'Brand new sealed Apple MacBook Pro 14-inch with M4 chip, 16GB RAM, 512GB SSD in Space Black. Includes UK power adapter. One of the most powerful laptops Apple has ever made — ideal for creative professionals.',
   8, 1500.00, 100.00, 2500.00, 1800.00, 2, 'active', 2399.00);

-- Lot 9: ART — original watercolour
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('highland-watercolour', 1,
   (SELECT id FROM categories WHERE slug='art'), 4,
   'Highland Glen Watercolour',
   'Original watercolour depicting a misty Highland glen at dawn by Glasgow-based artist. Painted en plein air in Glencoe. Framed in natural ash. 60cm × 45cm. Signed and dated.',
   9, 280.00, 20.00, NULL, 280.00, 0, 'active', 520.00);

-- Lot 10: ART — bronze sculpture
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('bronze-stag-sculpture', 1,
   (SELECT id FROM categories WHERE slug='art'), 2,
   'Bronze Stag Sculpture',
   'Limited-edition cast bronze sculpture of a Highland red deer stag by Edinburgh sculptor. One of only 12 produced. Mounted on a polished slate plinth. Height 34cm. Certificate of authenticity included.',
   10, 950.00, 50.00, NULL, 950.00, 0, 'active', 1800.00);

-- Lot 11: PENDING — awaiting admin approval
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('celtic-fc-signed-jersey', 1,
   (SELECT id FROM categories WHERE slug='sports'), 4,
   'Celtic FC Signed Home Jersey 2025/26',
   'An official Celtic FC home jersey for the 2025/26 season, hand-signed by five first-team players. Presented in a display frame with certificate of authenticity. A must-have for any Hoops fan.',
   9, 250.00, 25.00, NULL, 250.00, 0, 'active', 500.00);

-- Lot 12: ACTIVE
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('handmade-jewellery-set', 1,
   (SELECT id FROM categories WHERE slug='fashion'), 2,
   'Handmade Silver Jewellery Set',
   'A beautifully crafted sterling silver jewellery set by Scottish designer, comprising necklace, bracelet, and matching earrings. Inspired by traditional Celtic knotwork. Presented in a velvet gift box.',
   10, 150.00, 15.00, NULL, 150.00, 0, 'active', 280.00);

-- ─── Items — Event 2 (ENDED — Winter Wonderland 2025) ─────────────────────

-- Lot 1: SOLD to James MacLeod (user 3)
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value, winner_id) VALUES
  ('glasgow-city-hotel-stay', 2,
   (SELECT id FROM categories WHERE slug='holidays-travel'), 2,
   'Glasgow City Centre Hotel Stay (2 Nights)',
   'Two nights bed and breakfast for two at a 4-star Glasgow city centre hotel. Includes welcome refreshments on arrival and late checkout. Valid until June 2026.',
   1, 200.00, 25.00, NULL, 380.00, 4, 'sold', 520.00, 3);

-- Lot 2: ENDED — no bids met reserve, unsold
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('personal-training-package', 2,
   (SELECT id FROM categories WHERE slug='health-beauty'), 4,
   '10-Session Personal Training Package',
   'Ten one-hour personal training sessions with a REPS-certified personal trainer based in Glasgow. Includes initial fitness assessment and personalised programme. Valid for 6 months.',
   2, 300.00, 25.00, NULL, 300.00, 0, 'ended', 600.00);

-- Lot 3: SOLD to Emma Wilson (user 5)
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value, winner_id) VALUES
  ('signed-book-collection', 2,
   (SELECT id FROM categories WHERE slug='art'), 2,
   'Scottish Authors Signed Book Collection',
   'A collection of eight signed first-edition novels by acclaimed Scottish authors including Irvine Welsh, Val McDermid, and Ian Rankin. All books signed at author events and in excellent condition.',
   3, 120.00, 10.00, NULL, 195.00, 5, 'sold', 350.00, 5);

-- ─── Items — Event 3 (PUBLISHED — Spring Gala 2026) ───────────────────────

-- Lot 1: listed but event not yet active
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('edinburgh-castle-private-tour', 3,
   (SELECT id FROM categories WHERE slug='experiences'), 2,
   'Edinburgh Castle Private Evening Tour',
   'An exclusive private evening tour of Edinburgh Castle for up to 8 guests, after the castle closes to the public. Includes a guided walk of the Crown Jewels exhibition and Great Hall, followed by a welcome reception.',
   1, 1200.00, 100.00, NULL, 1200.00, 0, 'active', 2400.00);

-- Lot 2: listed
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value) VALUES
  ('garden-makeover-day', 3,
   (SELECT id FROM categories WHERE slug='experiences'), 4,
   'Professional Garden Makeover Day',
   'A full day visit from a professional garden designer and team to transform your outdoor space. Includes planting plan, up to £500 of plants and materials, and a full tidy-up. Central Scotland only.',
   2, 500.00, 50.00, NULL, 500.00, 0, 'active', 1800.00);

-- ─── Items — Event 4 (CLOSED — Christmas Gala 2025) ───────────────────────

-- Lot 1: SOLD to James MacLeod (user 3)
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value, winner_id) VALUES
  ('vintage-omega-watch', 4,
   (SELECT id FROM categories WHERE slug='fashion'), 2,
   'Vintage Omega Seamaster (1968)',
   'An authentic 1968 Omega Seamaster in excellent condition for its age. Serviced 2024, keeps excellent time. Original dial and hands. A beautiful piece of horological history.',
   1, 800.00, 50.00, NULL, 1150.00, 6, 'sold', 1600.00, 3);

-- Lot 2: SOLD to Emma Wilson (user 5)
INSERT INTO items (slug, event_id, category_id, donor_id, title, description, lot_number,
  starting_bid, min_increment, buy_now_price, current_bid, bid_count, status, market_value, winner_id) VALUES
  ('afternoon-tea-experience', 4,
   (SELECT id FROM categories WHERE slug='experiences'), 4,
   'Luxury Afternoon Tea Experience for Six',
   'A private luxury afternoon tea for six guests hosted by an award-winning Glasgow tearoom. Includes finger sandwiches, freshly baked scones with clotted cream and jam, a selection of cakes and pastries, and a choice of premium teas and coffees. A wonderful occasion in elegant surroundings.',
   2, 120.00, 15.00, NULL, 210.00, 4, 'sold', 400.00, 5);

-- ─── Item images ────────────────────────────────────────────────────────────
-- Royalty-free images (Picsum Photos — CC0). Files live in uploads/.
-- If uploads/ is empty on a fresh environment, items fall back to the placeholder.

UPDATE items SET image = 'rolex-submariner.jpg'              WHERE slug = 'rolex-submariner';
UPDATE items SET image = 'chefs-table-experience.jpg'        WHERE slug = 'chefs-table-experience';
UPDATE items SET image = 'loch-lomond-oil-painting.jpg'      WHERE slug = 'loch-lomond-oil-painting';
UPDATE items SET image = 'gleneagles-golf-package.jpg'       WHERE slug = 'gleneagles-golf-package';
UPDATE items SET image = 'balmoral-spa-day.jpg'              WHERE slug = 'balmoral-spa-day';
UPDATE items SET image = 'amalfi-villa-week.jpg'             WHERE slug = 'amalfi-villa-week';
UPDATE items SET image = 'scottish-artisan-hamper.jpg'       WHERE slug = 'scottish-artisan-hamper';
UPDATE items SET image = 'macbook-pro-m4.jpg'                WHERE slug = 'macbook-pro-m4';
UPDATE items SET image = 'highland-watercolour.jpg'          WHERE slug = 'highland-watercolour';
UPDATE items SET image = 'bronze-stag-sculpture.jpg'         WHERE slug = 'bronze-stag-sculpture';
UPDATE items SET image = 'celtic-fc-signed-jersey.jpg'       WHERE slug = 'celtic-fc-signed-jersey';
UPDATE items SET image = 'handmade-jewellery-set.jpg'        WHERE slug = 'handmade-jewellery-set';
UPDATE items SET image = 'glasgow-city-hotel-stay.jpg'       WHERE slug = 'glasgow-city-hotel-stay';
UPDATE items SET image = 'personal-training-package.jpg'     WHERE slug = 'personal-training-package';
UPDATE items SET image = 'signed-book-collection.jpg'        WHERE slug = 'signed-book-collection';
UPDATE items SET image = 'edinburgh-castle-private-tour.jpg' WHERE slug = 'edinburgh-castle-private-tour';
UPDATE items SET image = 'garden-makeover-day.jpg'           WHERE slug = 'garden-makeover-day';
UPDATE items SET image = 'vintage-omega-watch.jpg'           WHERE slug = 'vintage-omega-watch';
UPDATE items SET image = 'afternoon-tea-experience.jpg'      WHERE slug = 'afternoon-tea-experience';

-- ─── Bids ──────────────────────────────────────────────────────────────────

-- Lot 7 (Scottish Artisan Hamper) — 3 bids
INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at) VALUES
  ((SELECT id FROM items WHERE slug='scottish-artisan-hamper'), 3, 650.00, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
  ((SELECT id FROM items WHERE slug='scottish-artisan-hamper'), 5, 800.00, 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
  ((SELECT id FROM items WHERE slug='scottish-artisan-hamper'), 3, 925.00, 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Lot 8 (MacBook Pro) — 2 bids
INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at) VALUES
  ((SELECT id FROM items WHERE slug='macbook-pro-m4'), 5, 1600.00, 0, DATE_SUB(NOW(), INTERVAL 4 DAY)),
  ((SELECT id FROM items WHERE slug='macbook-pro-m4'), 3, 1800.00, 0, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Glasgow Hotel (ended event, sold to user 3) — 4 bids
INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at) VALUES
  ((SELECT id FROM items WHERE slug='glasgow-city-hotel-stay'), 5, 230.00, 0, DATE_SUB(NOW(), INTERVAL 40 DAY)),
  ((SELECT id FROM items WHERE slug='glasgow-city-hotel-stay'), 3, 280.00, 0, DATE_SUB(NOW(), INTERVAL 38 DAY)),
  ((SELECT id FROM items WHERE slug='glasgow-city-hotel-stay'), 5, 330.00, 0, DATE_SUB(NOW(), INTERVAL 36 DAY)),
  ((SELECT id FROM items WHERE slug='glasgow-city-hotel-stay'), 3, 380.00, 0, DATE_SUB(NOW(), INTERVAL 32 DAY));

-- Signed Book Collection (ended event, sold to user 5) — 5 bids
INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at) VALUES
  ((SELECT id FROM items WHERE slug='signed-book-collection'), 3, 130.00, 0, DATE_SUB(NOW(), INTERVAL 44 DAY)),
  ((SELECT id FROM items WHERE slug='signed-book-collection'), 6, 145.00, 0, DATE_SUB(NOW(), INTERVAL 43 DAY)),
  ((SELECT id FROM items WHERE slug='signed-book-collection'), 5, 160.00, 0, DATE_SUB(NOW(), INTERVAL 42 DAY)),
  ((SELECT id FROM items WHERE slug='signed-book-collection'), 3, 175.00, 0, DATE_SUB(NOW(), INTERVAL 41 DAY)),
  ((SELECT id FROM items WHERE slug='signed-book-collection'), 5, 195.00, 0, DATE_SUB(NOW(), INTERVAL 38 DAY));

-- Vintage Omega (closed event, sold to user 3) — 6 bids
INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at) VALUES
  ((SELECT id FROM items WHERE slug='vintage-omega-watch'), 5,  900.00, 0, DATE_SUB(NOW(), INTERVAL 78 DAY)),
  ((SELECT id FROM items WHERE slug='vintage-omega-watch'), 3,  950.00, 0, DATE_SUB(NOW(), INTERVAL 77 DAY)),
  ((SELECT id FROM items WHERE slug='vintage-omega-watch'), 6, 1000.00, 0, DATE_SUB(NOW(), INTERVAL 76 DAY)),
  ((SELECT id FROM items WHERE slug='vintage-omega-watch'), 5, 1050.00, 0, DATE_SUB(NOW(), INTERVAL 75 DAY)),
  ((SELECT id FROM items WHERE slug='vintage-omega-watch'), 3, 1100.00, 0, DATE_SUB(NOW(), INTERVAL 74 DAY)),
  ((SELECT id FROM items WHERE slug='vintage-omega-watch'), 3, 1150.00, 0, DATE_SUB(NOW(), INTERVAL 70 DAY));

-- Afternoon Tea (closed event, sold to user 5) — 4 bids
INSERT INTO bids (item_id, user_id, amount, is_buy_now, created_at) VALUES
  ((SELECT id FROM items WHERE slug='afternoon-tea-experience'), 3, 135.00, 0, DATE_SUB(NOW(), INTERVAL 78 DAY)),
  ((SELECT id FROM items WHERE slug='afternoon-tea-experience'), 5, 160.00, 0, DATE_SUB(NOW(), INTERVAL 77 DAY)),
  ((SELECT id FROM items WHERE slug='afternoon-tea-experience'), 3, 185.00, 0, DATE_SUB(NOW(), INTERVAL 76 DAY)),
  ((SELECT id FROM items WHERE slug='afternoon-tea-experience'), 5, 210.00, 0, DATE_SUB(NOW(), INTERVAL 73 DAY));

-- ─── Payments ──────────────────────────────────────────────────────────────

-- Glasgow Hotel: COMPLETED, no Gift Aid (user 3, James MacLeod)
INSERT INTO payments (user_id, item_id, amount, stripe_session_id, stripe_payment_intent_id,
  status, gift_aid_claimed, gift_aid_amount, created_at) VALUES
  (3, (SELECT id FROM items WHERE slug='glasgow-city-hotel-stay'),
   380.00, 'cs_test_hotel_001', 'pi_test_hotel_001',
   'completed', 0, NULL, DATE_SUB(NOW(), INTERVAL 29 DAY));

-- Signed Books: COMPLETED with Gift Aid (user 5, Emma Wilson — gift aid on amount above market value)
INSERT INTO payments (user_id, item_id, amount, stripe_session_id, stripe_payment_intent_id,
  status, gift_aid_claimed, gift_aid_amount, created_at) VALUES
  (5, (SELECT id FROM items WHERE slug='signed-book-collection'),
   195.00, 'cs_test_books_001', 'pi_test_books_001',
   'completed', 1, 11.25, DATE_SUB(NOW(), INTERVAL 28 DAY));

-- Vintage Omega: COMPLETED with Gift Aid (user 3, James MacLeod — gift aid on excess above market)
INSERT INTO payments (user_id, item_id, amount, stripe_session_id, stripe_payment_intent_id,
  status, gift_aid_claimed, gift_aid_amount, created_at) VALUES
  (3, (SELECT id FROM items WHERE slug='vintage-omega-watch'),
   1150.00, 'cs_test_omega_001', 'pi_test_omega_001',
   'completed', 1, 0.00, DATE_SUB(NOW(), INTERVAL 63 DAY));

-- Afternoon Tea: COMPLETED with Gift Aid (user 5, Emma Wilson)
INSERT INTO payments (user_id, item_id, amount, stripe_session_id, stripe_payment_intent_id,
  status, gift_aid_claimed, gift_aid_amount, created_at) VALUES
  (5, (SELECT id FROM items WHERE slug='afternoon-tea-experience'),
   210.00, 'cs_test_tea_001', 'pi_test_tea_001',
   'completed', 1, 27.50, DATE_SUB(NOW(), INTERVAL 62 DAY));

-- MacBook Pro (James won, PENDING payment — not yet paid)
INSERT INTO payments (user_id, item_id, amount, stripe_session_id, stripe_payment_intent_id,
  status, gift_aid_claimed, gift_aid_amount, created_at) VALUES
  (3, (SELECT id FROM items WHERE slug='macbook-pro-m4'),
   1800.00, NULL, NULL,
   'pending', 0, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Glasgow Hotel: a FAILED payment attempt (before the successful one above — user 3 first attempt failed)
INSERT INTO payments (user_id, item_id, amount, stripe_session_id, stripe_payment_intent_id,
  status, gift_aid_claimed, gift_aid_amount, created_at) VALUES
  (3, (SELECT id FROM items WHERE slug='glasgow-city-hotel-stay'),
   380.00, 'cs_test_hotel_failed', 'pi_test_hotel_failed',
   'failed', 0, NULL, DATE_SUB(NOW(), INTERVAL 30 DAY));
