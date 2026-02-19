<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;

class AccountService
{
    private UserRepository $users;

    public function __construct(?UserRepository $users = null)
    {
        $this->users = $users ?? new UserRepository();
    }

    // -------------------------------------------------------------------------
    // Profile update
    // -------------------------------------------------------------------------

    /**
     * Update user profile fields.
     *
     * Validates:
     *   - name: not empty, max 255 chars
     *   - if gift_aid_eligible=true, gift_aid_name is required
     *
     * Updates: name, gift_aid_eligible (0/1), gift_aid_name
     * Returns the updated user array.
     *
     * @throws \InvalidArgumentException on validation failure
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Support both split first/last fields (profile form) and single name (gift-aid form)
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name']  ?? '');
        $name      = $firstName !== '' || $lastName !== ''
            ? trim($firstName . ' ' . $lastName)
            : trim($data['name'] ?? '');
        $phone                   = trim($data['phone'] ?? '');
        $email                   = trim($data['email'] ?? '');
        $companyName             = trim($data['company_name'] ?? '');
        $companyContactFirstName = trim($data['company_contact_first_name'] ?? '');
        $companyContactLastName  = trim($data['company_contact_last_name'] ?? '');
        $companyContactEmail     = trim($data['company_contact_email'] ?? '');
        $website                 = trim($data['website'] ?? '');
        $giftAidEligible         = !empty($data['gift_aid_eligible']) ? 1 : 0;
        $giftAidName     = trim($data['gift_aid_name'] ?? '');
        $giftAidAddress  = trim($data['gift_aid_address'] ?? '');
        $giftAidCity     = trim($data['gift_aid_city'] ?? '');
        $giftAidPostcode = trim($data['gift_aid_postcode'] ?? '');

        // -- Validation -------------------------------------------------------
        if ($name === '') {
            throw new \InvalidArgumentException('Name is required.');
        }

        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Name must be 255 characters or fewer.');
        }

        if ($giftAidEligible === 1 && $giftAidName === '') {
            throw new \InvalidArgumentException('Full name is required for Gift Aid declarations.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }

        if ($email !== '') {
            $existing = $this->users->findByEmail($email);
            if ($existing !== null && (int)$existing['id'] !== $userId) {
                throw new \InvalidArgumentException('That email address is already in use.');
            }
        }

        // -- Persist ----------------------------------------------------------
        // Only include a field group if the form actually sent it, so the
        // profile form doesn't wipe gift_aid and the gift-aid form doesn't
        // wipe phone.
        $profileData = ['name' => $name];

        if (array_key_exists('phone', $data)) {
            $profileData['phone'] = $phone !== '' ? $phone : null;
        }

        // Company fields — only update when the profile form sends them
        if (array_key_exists('company_name', $data)) {
            $profileData['company_name']               = $companyName             !== '' ? $companyName             : null;
            $profileData['company_contact_first_name'] = $companyContactFirstName !== '' ? $companyContactFirstName : null;
            $profileData['company_contact_last_name']  = $companyContactLastName  !== '' ? $companyContactLastName  : null;
            $profileData['company_contact_email']      = $companyContactEmail     !== '' ? $companyContactEmail     : null;
            $profileData['website']                    = $website                 !== '' ? $website                 : null;
        }

        if (array_key_exists('gift_aid_eligible', $data)) {
            $profileData['gift_aid_eligible'] = $giftAidEligible;
            $profileData['gift_aid_name']     = $giftAidEligible === 1 ? $giftAidName     : null;
            $profileData['gift_aid_address']  = $giftAidEligible === 1 ? $giftAidAddress  : null;
            $profileData['gift_aid_city']     = $giftAidEligible === 1 ? $giftAidCity     : null;
            $profileData['gift_aid_postcode'] = $giftAidEligible === 1 ? $giftAidPostcode : null;
        }

        if ($email !== '') {
            $profileData['email'] = $email;
        }

        $this->users->updateProfile($userId, $profileData);

        $updated = $this->users->findById($userId);

        if ($updated === null) {
            throw new \InvalidArgumentException('User not found.');
        }

        return $updated;
    }

    // -------------------------------------------------------------------------
    // Password change
    // -------------------------------------------------------------------------

    /**
     * Change a user's password.
     *
     * Validates:
     *   - current password is correct
     *   - new password meets strength requirements (min 8 chars)
     *   - confirm password matches new password
     *
     * Updates: password_hash
     * Returns the updated user array.
     *
     * @throws \InvalidArgumentException on failure
     */
    public function changePassword(
        int $userId,
        string $currentPassword,
        string $newPassword,
        string $confirmPassword
    ): array {
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw new \InvalidArgumentException('User not found.');
        }

        // -- Verify current password ------------------------------------------
        if (!password_verify($currentPassword, (string)($user['password_hash'] ?? ''))) {
            throw new \InvalidArgumentException('Current password is incorrect.');
        }

        // -- Validate new password strength -----------------------------------
        if (strlen($newPassword) < 8) {
            throw new \InvalidArgumentException('New password must be at least 8 characters.');
        }

        // -- Confirm match ----------------------------------------------------
        if ($newPassword !== $confirmPassword) {
            throw new \InvalidArgumentException('New passwords do not match.');
        }

        // -- Persist ----------------------------------------------------------
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->users->updatePassword($userId, $newHash);

        $updated = $this->users->findById($userId);

        if ($updated === null) {
            throw new \InvalidArgumentException('User not found after update.');
        }

        return $updated;
    }
}
