<?php

/**
 * Environment Configuration - SHARED HOSTING VERSION
 *
 * This file replaces .env on shared hosting where dotfiles
 * are not reliable. It populates $_ENV so all env() / getenv() calls work.
 *
 * The build script renames this to config/env.php in the dist/ folder.
 * Require it at the very top of index.php (already done in the shared build).
 *
 * Edit the values below for your server.
 */

// Stripe — configure in Admin > Settings > Payments, or set here as fallback
$_ENV['STRIPE_PUBLISHABLE_KEY'] = '';
$_ENV['STRIPE_SECRET_KEY']      = '';
$_ENV['STRIPE_WEBHOOK_SECRET']  = '';

// Email — configure in Admin > Settings > Email, or set here as fallback
$_ENV['MAIL_FROM_ADDRESS'] = 'noreply@wellfoundation.org.uk';
$_ENV['MAIL_FROM_NAME']    = 'WFCS Auction';

// SMTP (PHPMailer — update to match your server's mail settings)
$_ENV['MAIL_HOST']       = 'mail.YOUR_DOMAIN_HERE';
$_ENV['MAIL_PORT']       = '587';
$_ENV['MAIL_USERNAME']   = 'noreply@YOUR_DOMAIN_HERE';
$_ENV['MAIL_PASSWORD']   = 'YOUR_MAIL_PASSWORD_HERE';
$_ENV['MAIL_ENCRYPTION'] = 'tls';
