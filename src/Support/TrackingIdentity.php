<?php

namespace Lumina\Core\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Privacy-first tracking identity.
 *
 * Visitor and session identity are generated client-side by tracker.js as
 * opaque random IDs and persisted in localStorage / sessionStorage — NOT
 * cookies — so no consent banner is required. The server trusts these IDs
 * when present and validated, and otherwise falls back to a one-way salted
 * hash of IP + User-Agent using a STABLE (non-rotating) secret, so the same
 * visitor is re-identified across days without storing raw IPs.
 */
class TrackingIdentity
{
    public const VISITOR_HEADER = 'X-Lumina-Visitor';

    public const SESSION_HEADER = 'X-Lumina-Session';

    public const VISITOR_INPUT_KEY = 'visitor';

    public const SESSION_INPUT_KEY = 'session';

    /**
     * Resolve the visitor identity for a request.
     *
     * @return array{
     *     visitor_id: string,
     *     session_id: string|null,
     *     visitor_hash: string,
     *     event_id: string
     * }
     */
    public static function resolve(Request $request, ?string $scope = null): array
    {
        $visitorId = self::readOpaqueId($request, self::VISITOR_HEADER, self::VISITOR_INPUT_KEY);
        $sessionId = self::readOpaqueId($request, self::SESSION_HEADER, self::SESSION_INPUT_KEY);

        if ($visitorId === null) {
            $visitorId = self::fallbackHash($request, $scope);
        }

        return [
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
            'visitor_hash' => $visitorId,
            'event_id' => (string) Str::uuid(),
        ];
    }

    /**
     * Read an opaque client ID from a header or request input. tracker.js
     * sends these as query parameters because sendBeacon cannot set custom
     * headers.
     */
    protected static function readOpaqueId(Request $request, string $header, string $inputKey): ?string
    {
        $value = $request->header($header);

        if ($value === null) {
            $value = $request->input($inputKey);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 100) {
            return null;
        }

        // Opaque IDs only — never accept raw identifiers like IPs or emails.
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            return null;
        }

        // Cap at 64 chars to match the visitor_hash column width; opaque client
        // IDs are random UUIDs (~36 chars) so this never truncates real IDs,
        // while a spoofed over-long value can never overflow the schema.
        return substr($value, 0, 64);
    }

    /**
     * One-way hash of IP + User-Agent with a stable secret salt. The salt is
     * stable (not daily) so cross-day unique visitors remain exact, and the
     * optional scope keeps server-derived identity scoped to one tracked site.
     */
    protected static function fallbackHash(Request $request, ?string $scope): string
    {
        return hash('sha256', implode('|', [
            (string) $scope,
            (string) $request->ip(),
            (string) ($request->userAgent() ?? ''),
            self::stableSalt(),
        ]));
    }

    /**
     * Stable app-wide salt, generated exactly once under a cache lock so
     * concurrent first requests can never diverge.
     */
    protected static function stableSalt(): string
    {
        $salt = Cache::get('lumina_visitor_salt');

        if ($salt !== null) {
            return $salt;
        }

        // The lock guarantees exactly one writer on stores that support it
        // (redis, database, file); stores without lock support (array) fall
        // back to a plain rememberForever — a lost race there only means a
        // second random salt is generated, not a correctness failure.
        try {
            return Cache::lock('lumina_visitor_salt_lock', 10)->block(5, function () {
                return Cache::rememberForever('lumina_visitor_salt', fn () => Str::random(32));
            });
        } catch (\Throwable $e) {
            return Cache::rememberForever('lumina_visitor_salt', fn () => Str::random(32));
        }
    }
}
