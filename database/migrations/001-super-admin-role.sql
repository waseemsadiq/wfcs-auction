-- Migration: add super_admin role (run once on production)
-- Safe to run multiple times — IF NOT EXISTS guards the INSERT.

ALTER TABLE users
  MODIFY COLUMN role ENUM('bidder','donor','admin','super_admin') NOT NULL DEFAULT 'bidder';

-- Insert super_admin users (if they don't already exist)
INSERT INTO users (slug, name, email, password_hash, role, email_verified_at)
SELECT 'waseem-sadiq', 'Waseem Sadiq', 'admin@wfcs.co.uk',
       '$2y$12$ue9Dsx1cea8oAseGKuEelO3J1ahBMLhcVRKAow/sNXjR16FNB7GvG',
       'super_admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@wfcs.co.uk');

INSERT INTO users (slug, name, email, password_hash, role, email_verified_at)
SELECT 'fahim-baqir', 'Fahim Baqir', 'fahimbaqir@gmail.com',
       '$2y$12$zGJ0lmQryVjtD8wS.WajPeojA67ipZ4/4NnBQ13Y6BBYtyMCEdlYi',
       'super_admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'fahimbaqir@gmail.com');
