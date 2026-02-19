<?php

declare(strict_types=1);

namespace Vibeable\Backend\Controller;

use Vibeable\Backend\Service\AuthService;
use Vibeable\Backend\Database\DB;

final class ReferralController
{
    /** @param array<string, string> $params */
    public static function index(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $rows = DB::query(
            'SELECT r.id, r.referred_id, r.commission_credits, r.status, r.created_at, u.email as referred_email 
             FROM referrals r JOIN users u ON u.id = r.referred_id WHERE r.referrer_id = ? ORDER BY r.id DESC',
            [$user['id']]
        );
        $code = 'VB-' . strtoupper(substr(md5((string) $user['id']), 0, 8));
        return ['referrals' => $rows, 'referral_code' => $code];
    }

    /** @param array<string, string> $params */
    public static function create(array $params, array $payload): array
    {
        $user = AuthService::getCurrentUser();
        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            throw new \Exception('Referral code required', 400);
        }
        // In full implementation: resolve code to referrer_id, create referral, award credits
        return ['message' => 'Referral recorded.', 'referral_code' => $code];
    }
}
