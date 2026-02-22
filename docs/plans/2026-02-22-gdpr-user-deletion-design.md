# GDPR User Deletion Design

**Goal:** Allow admins to permanently delete a user and all their associated data from the admin users list, in compliance with GDPR right to erasure.

**Architecture:** New `UserService::deleteUser()` orchestrates the cascade. All SQL stays in repositories. A Popover API confirmation panel replaces any browser dialog.

---

## Deletion rules

| Scenario | Behaviour |
|---|---|
| Target is an admin | Block — redirect with error flash |
| Target created auction events | Transfer `events.created_by` to acting admin |
| User has items in an **active** auction | Anonymise — `donor_id = NULL` on those items, then proceed |
| All other donated items (draft/ended/closed) | Delete items + all bids on them |

Deletion always succeeds for non-admin users.

---

## Cascade delete order

1. `password_reset_tokens` — DELETE WHERE user_id
2. `api_tokens` — DELETE WHERE user_id
3. `rate_limits` — DELETE WHERE identifier = user's email
4. `gift_aid_claims` — DELETE WHERE user_id
5. `bids` — DELETE WHERE user_id (bids placed by this user)
6. Donated items in active auctions — UPDATE SET donor_id = NULL (anonymise)
7. Remaining donated items — DELETE bids on those items from others, then DELETE items
8. `payments` — DELETE WHERE user_id
9. `items` — UPDATE SET winner_id = NULL WHERE winner_id = userId
10. `events` — UPDATE SET created_by = actingAdminId WHERE created_by = userId
11. DELETE the user row

---

## Confirmation UX

- Delete button on each row in `/admin/users` (next to "View"), hidden for admin-role users
- Clicking opens a **Popover API** panel (no `confirm()` dialog) showing:
  - User name and email
  - Warning: "This cannot be undone. All bids, donated items and personal data will be permanently removed."
  - Red "Yes, delete permanently" button (submits hidden `<form method="POST">`)
  - Cancel button (closes popover)

---

## Files changed

| File | Change |
|---|---|
| `app/Views/admin/users.php` | Delete button + Popover per row |
| `app/Controllers/AdminController.php` | `deleteUser(string $slug)` method |
| `app/Services/UserService.php` | New — `deleteUser(int $userId, int $actingAdminId): void` |
| `app/Repositories/UserRepository.php` | `delete(int $id)` + `hasItemsInActiveAuctions(int $id): bool` |
| `app/Repositories/ItemRepository.php` | `anonymiseDonor(int $userId): void`, `deleteByDonor(array $itemIds): void`, `clearWinner(int $userId): void`, `idsByDonorNotActive(int $userId): array` |
| `app/Repositories/BidRepository.php` | `deleteByUser(int $userId): void`, `deleteByItems(array $itemIds): void` |
| `core/Router.php` | Register `POST /admin/users/{slug}/delete` |

---

## Tests

- Block deletion of admin-role user
- Happy path: all rows deleted in correct order, user row gone
- Active auction items anonymised (`donor_id = NULL`), not deleted
- `events.created_by` transferred to acting admin
