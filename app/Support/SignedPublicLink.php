<?php

namespace App\Support;

/**
 * HMAC-signed public link helper for QR-code / scan-to-verify style links that must be
 * checkable without a database lookup and without requiring the scanner to be logged
 * in. Signs {subjectId}:{instituteId}:{type} using the application key, truncated to
 * 32 hex characters — the same convention already used for public fee receipt links
 * (see PublicReceiptController and StatementController::receiptUrl()), reused here so
 * the signing/verification logic exists in exactly one place rather than being
 * re-implemented per feature.
 *
 * The "type" string scopes a signature to a single purpose — a valid signature minted
 * for one type can never verify against another, so a leaked receipt link can't be
 * replayed as a transport pass link or vice versa.
 */
class SignedPublicLink
{
    public static function sign(int $subjectId, int $instituteId, string $type): string
    {
        return substr(hash_hmac('sha256', "{$subjectId}:{$instituteId}:{$type}", config('app.key')), 0, 32);
    }

    public static function verify(int $subjectId, int $instituteId, string $type, string $signature): bool
    {
        return hash_equals(self::sign($subjectId, $instituteId, $type), $signature);
    }

    public static function url(string $path, int $subjectId, int $instituteId, string $type): string
    {
        $sig = self::sign($subjectId, $instituteId, $type);

        return url("{$path}?sid={$subjectId}&iid={$instituteId}&sig={$sig}");
    }
}
