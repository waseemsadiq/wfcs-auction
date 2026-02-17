<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\GiftAidRepository;

class GiftAidService
{
    private ?GiftAidRepository $repo;

    /**
     * @param GiftAidRepository|null $repo  Supply a mock for testing.
     *                                       When null, a real repository is created
     *                                       lazily on first DB access.
     */
    public function __construct(?GiftAidRepository $repo = null)
    {
        $this->repo = $repo;
    }

    /**
     * Return the repository, creating it lazily on first use.
     */
    private function repo(): GiftAidRepository
    {
        if ($this->repo === null) {
            $this->repo = new GiftAidRepository();
        }
        return $this->repo;
    }

    /**
     * Calculate Gift Aid amount for a bid.
     *
     * Formula: max(0, (bid_amount - market_value) * 0.25)
     *
     * Returns 0.00 if bid_amount does not exceed market_value.
     * This method calculates the potential Gift Aid regardless of user declaration
     * status — use saveDeclaration() to validate and record the declaration.
     *
     * @param float $bidAmount   The winning bid amount
     * @param float $marketValue The item's market value (0 means no market value set)
     * @return float             Gift Aid amount (rounded to 2 decimal places)
     */
    public function calculate(float $bidAmount, float $marketValue): float
    {
        if ($marketValue <= 0) {
            return 0.00;
        }

        $surplus = $bidAmount - $marketValue;

        if ($surplus <= 0) {
            return 0.00;
        }

        return round($surplus * 0.25, 2);
    }

    /**
     * Save and validate a Gift Aid declaration for a payment.
     *
     * Declaration array must contain:
     *   full_name          (non-empty string)
     *   address            (string)
     *   postcode           (string)
     *   confirmed_taxpayer (1 = confirmed, 0 = not confirmed)
     *
     * Throws \RuntimeException if validation fails.
     * On success, marks the payment as gift-aid claimed in the database.
     *
     * @param int   $userId         The user making the declaration
     * @param int   $paymentId      The payment being claimed
     * @param array $declaration    Declaration fields (see above)
     * @param float $giftAidAmount  Pre-calculated gift aid amount
     * @return void
     * @throws \RuntimeException
     */
    public function saveDeclaration(int $userId, int $paymentId, array $declaration, float $giftAidAmount): void
    {
        $fullName          = trim((string)($declaration['full_name'] ?? ''));
        $confirmedTaxpayer = (int)($declaration['confirmed_taxpayer'] ?? 0);

        if ($confirmedTaxpayer !== 1) {
            throw new \RuntimeException(
                'You must confirm you are a UK taxpayer to make a Gift Aid declaration.'
            );
        }

        if ($fullName === '') {
            throw new \RuntimeException(
                'Your full name is required to make a Gift Aid declaration.'
            );
        }

        $this->repo()->markClaimed($paymentId, $giftAidAmount);
    }

    /**
     * Get Gift Aid report data for admin (paginated).
     *
     * @param int $page    Page number (1-based)
     * @param int $perPage Records per page
     * @return array{claims: array, total: int, totalAmount: float, page: int, perPage: int, pages: int}
     */
    public function report(int $page = 1, int $perPage = 50): array
    {
        $page   = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $claims = $this->repo()->claimed($perPage, $offset);
        $total  = $this->repo()->countClaimed();

        $totalAmount = array_reduce($claims, function (float $carry, array $row): float {
            return $carry + (float)($row['gift_aid_amount'] ?? 0.0);
        }, 0.0);

        return [
            'claims'      => $claims,
            'total'       => $total,
            'totalAmount' => $totalAmount,
            'page'        => $page,
            'perPage'     => $perPage,
            'pages'       => max(1, (int)ceil($total / $perPage)),
        ];
    }

    /**
     * Get stats for admin dashboard.
     *
     * @return array{total_claimed: float, total_payments_eligible: int, total_amount: float}
     */
    public function stats(): array
    {
        return $this->repo()->stats();
    }
}
