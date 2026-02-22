<?php
declare(strict_types=1);

namespace App\Repositories;

use Core\Database;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM users WHERE id = ?',
            [$id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM users WHERE email = ?',
            [$email]
        );
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM users WHERE slug = ?',
            [$slug]
        );
    }

    public function findByVerificationToken(string $token): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM users WHERE email_verification_token = ?',
            [$token]
        );
    }

    /**
     * Create a new user.
     * Expected keys: slug, name, email, password_hash, role,
     *                email_verification_token, email_verification_expires_at
     * Returns the new user's auto-increment ID.
     */
    public function create(array $data): int
    {
        $this->db->execute(
            'INSERT INTO users
                (slug, name, email, password_hash, role, phone,
                 company_name, company_contact_first_name, company_contact_last_name,
                 company_contact_email, website,
                 email_verified_at,
                 email_verification_token, email_verification_expires_at,
                 created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $data['slug'],
                $data['name'],
                $data['email'],
                $data['password_hash'],
                $data['role'] ?? 'bidder',
                $data['phone'] ?? null,
                $data['company_name'] ?? null,
                $data['company_contact_first_name'] ?? null,
                $data['company_contact_last_name'] ?? null,
                $data['company_contact_email'] ?? null,
                $data['website'] ?? null,
                $data['email_verified_at'] ?? null,
                $data['email_verification_token'] ?? null,
                $data['email_verification_expires_at'] ?? null,
            ]
        );
        return (int)$this->db->lastInsertId();
    }

    public function updatePhone(int $id, string $phone): void
    {
        $this->db->execute(
            'UPDATE users SET phone = ?, updated_at = NOW() WHERE id = ?',
            [$phone, $id]
        );
    }

    /**
     * Mark email as verified (verified=true) or regenerate token (verified=false).
     */
    public function updateVerification(int $id, bool $verified): void
    {
        if ($verified) {
            $this->db->execute(
                'UPDATE users
                 SET email_verified_at = NOW(),
                     email_verification_token = NULL,
                     email_verification_expires_at = NULL,
                     updated_at = NOW()
                 WHERE id = ?',
                [$id]
            );
        } else {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 86400);
            $this->db->execute(
                'UPDATE users
                 SET email_verification_token = ?,
                     email_verification_expires_at = ?,
                     updated_at = NOW()
                 WHERE id = ?',
                [$token, $expires, $id]
            );
        }
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->execute(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [$passwordHash, $id]
        );
    }

    /**
     * Update profile fields.
     * Allowed keys: name, gift_aid_eligible (0/1), gift_aid_name, gift_aid_address,
     *   gift_aid_city, gift_aid_postcode, notify_outbid, notify_ending_soon,
     *   notify_win, notify_payment
     */
    public function updateProfile(int $id, array $data): void
    {
        $allowed = [
            'name', 'email', 'phone',
            'company_name', 'company_contact_first_name', 'company_contact_last_name',
            'company_contact_email', 'website',
            'gift_aid_eligible', 'gift_aid_name',
            'gift_aid_address', 'gift_aid_city', 'gift_aid_postcode',
            'notify_outbid', 'notify_ending_soon', 'notify_win', 'notify_payment',
        ];
        $fields  = [];
        $values  = [];

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed, true)) {
                continue;
            }
            $fields[] = "$field = ?";
            $values[] = $value;
        }

        if (empty($fields)) {
            return;
        }

        $values[] = $id;
        $this->db->execute(
            'UPDATE users SET ' . implode(', ', $fields) . ', updated_at = NOW() WHERE id = ?',
            $values
        );
    }

    public function slugExists(string $slug): bool
    {
        return $this->findBySlug($slug) !== null;
    }

    /**
     * All users for admin with optional role filter, paginated.
     */
    public function all(int $limit = 50, int $offset = 0): array
    {
        return $this->db->query(
            'SELECT * FROM users
             ORDER BY created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );
    }

    /**
     * Search users by name/email, optional role filter, paginated.
     */
    public function search(string $query, string $role = '', int $limit = 50, int $offset = 0): array
    {
        $conditions = [];
        $params     = [];

        if ($query !== '') {
            $conditions[] = '(name LIKE ? OR email LIKE ?)';
            $params[]     = '%' . $query . '%';
            $params[]     = '%' . $query . '%';
        }

        if ($role !== '') {
            $conditions[] = 'role = ?';
            $params[]     = $role;
        }

        $where = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        return $this->db->query(
            'SELECT * FROM users' . $where .
            ' ORDER BY created_at DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset,
            $params
        );
    }

    /**
     * Count users with optional role filter.
     */
    public function count(string $role = ''): int
    {
        if ($role !== '') {
            $row = $this->db->queryOne(
                'SELECT COUNT(*) AS cnt FROM users WHERE role = ?',
                [$role]
            );
        } else {
            $row = $this->db->queryOne('SELECT COUNT(*) AS cnt FROM users');
        }
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count users matching a search query with optional role filter.
     */
    public function countSearch(string $query, string $role = ''): int
    {
        $conditions = [];
        $params     = [];

        if ($query !== '') {
            $conditions[] = '(name LIKE ? OR email LIKE ?)';
            $params[]     = '%' . $query . '%';
            $params[]     = '%' . $query . '%';
        }

        if ($role !== '') {
            $conditions[] = 'role = ?';
            $params[]     = $role;
        }

        $where = empty($conditions) ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $row = $this->db->queryOne('SELECT COUNT(*) AS cnt FROM users' . $where, $params);
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Update user role.
     */
    public function updateRole(int $id, string $role): void
    {
        $this->db->execute(
            'UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?',
            [$role, $id]
        );
    }

    /**
     * Count bids per user. Returns array of [user_id => count].
     * Used to append bid_count to user rows.
     */
    public function bidCountForUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $rows = $this->db->query(
            'SELECT user_id, COUNT(*) AS cnt FROM bids WHERE user_id IN (' . $placeholders . ') GROUP BY user_id',
            $userIds
        );
        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['user_id']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * Summary stats for admin users page.
     * Returns [total, bidders, donors, admins, unverified].
     */
    public function adminStats(): array
    {
        $total   = (int)(($this->db->queryOne('SELECT COUNT(*) AS cnt FROM users')['cnt']) ?? 0);
        $bidders = (int)(($this->db->queryOne("SELECT COUNT(*) AS cnt FROM users WHERE role = 'bidder'")['cnt']) ?? 0);
        $donors  = (int)(($this->db->queryOne("SELECT COUNT(*) AS cnt FROM users WHERE role = 'donor'")['cnt']) ?? 0);
        $admins  = (int)(($this->db->queryOne("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'")['cnt']) ?? 0);
        $unverified = (int)(($this->db->queryOne("SELECT COUNT(*) AS cnt FROM users WHERE email_verified_at IS NULL")['cnt']) ?? 0);
        return compact('total', 'bidders', 'donors', 'admins', 'unverified');
    }

    /**
     * Count users who have opted into Gift Aid.
     */
    public function countGiftAidEligible(): int
    {
        $row = $this->db->queryOne('SELECT COUNT(*) AS cnt FROM users WHERE gift_aid_eligible = 1');
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Set a specific verification token and expiry on a user.
     * Used by AuthService::resendVerification().
     */
    public function setVerificationToken(int $id, string $token, string $expires): void
    {
        $this->db->execute(
            'UPDATE users
             SET email_verification_token = ?,
                 email_verification_expires_at = ?,
                 updated_at = NOW()
             WHERE id = ?',
            [$token, $expires, $id]
        );
    }

    /**
     * Change a user's email address.
     * Clears email_verified_at and generates a new verification token in one query.
     * Returns the raw token string so the caller can send the verification email.
     */
    public function updateEmail(int $id, string $email): string
    {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 86400);

        $this->db->execute(
            'UPDATE users
             SET email = ?,
                 email_verified_at = NULL,
                 email_verification_token = ?,
                 email_verification_expires_at = ?,
                 updated_at = NOW()
             WHERE id = ?',
            [$email, $token, $expires, $id]
        );

        return $token;
    }

    public function uniqueSlug(string $text): string
    {
        return uniqueSlug('users', $text, $this->db);
    }

    /**
     * Delete password reset tokens for a user.
     */
    public function deletePasswordResets(int $userId): void
    {
        $this->db->execute(
            'DELETE FROM password_reset_tokens WHERE user_id = ?',
            [$userId]
        );
    }

    /**
     * Delete rate limit records keyed by email.
     * rate_limits has no FK — uses identifier (email) + action strings.
     */
    public function deleteRateLimits(string $email): void
    {
        $this->db->execute(
            'DELETE FROM rate_limits WHERE identifier = ?',
            [$email]
        );
    }

    /**
     * Transfer events created_by to another user (handles demoted admins edge case).
     */
    public function transferEvents(int $fromUserId, int $toUserId): void
    {
        $this->db->execute(
            'UPDATE events SET created_by = ? WHERE created_by = ?',
            [$toUserId, $fromUserId]
        );
    }

    /**
     * Permanently delete a user row. All FK-referenced rows must be cleaned up first.
     */
    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

}
