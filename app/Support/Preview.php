<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

/**
 * Public-site visibility of content records and shareable draft previews.
 *
 * Logged-in editors can already open drafts at their normal URL (see FindsPublished); a preview link is a
 * temporary signed URL that grants one record to anyone holding it, so a draft can be shared for review.
 */
final class Preview
{
    /** Request attribute holding the record granted by a signed preview link. */
    private const ATTRIBUTE = 'preview.record';

    public const VALID_FOR_HOURS = 24;

    /** @var array<string, class-string<Model>> route segment => previewable model */
    public const TYPES = [
        'trip' => Trip::class,
        'page' => Page::class,
        'post' => Post::class,
        'team' => TeamMember::class,
    ];

    /** Whether the record is live on the public site right now (published, not scheduled, not trashed). */
    public static function isPublic(Model $record): bool
    {
        if (method_exists($record, 'trashed') && $record->trashed()) {
            return false;
        }

        if (method_exists($record, 'isPublished')) {
            return $record->isPublished();
        }

        if ($record instanceof TeamMember) {
            return $record->is_published;
        }

        return true;
    }

    public static function publicUrl(Model $record): string
    {
        return url($record->publicPath());
    }

    public static function isPreviewable(Model $record): bool
    {
        return self::typeFor($record) !== null
            && ! self::isPublic($record)
            && ! (method_exists($record, 'trashed') && $record->trashed());
    }

    public static function signedUrl(Model $record): string
    {
        return URL::temporarySignedRoute('preview', now()->addHours(self::VALID_FOR_HOURS), [
            'type' => self::typeFor($record),
            'id' => $record->getKey(),
        ]);
    }

    /** Called by the preview route once the signature has been verified. */
    public static function grant(Model $record): void
    {
        request()->attributes->set(self::ATTRIBUTE, $record);
    }

    /** The primary key of the granted record when it is of the queried model's class. */
    public static function grantedKeyFor(Model $model): int|string|null
    {
        $granted = request()->attributes->get(self::ATTRIBUTE);

        return $granted instanceof Model && $granted::class === $model::class ? $granted->getKey() : null;
    }

    public static function active(): bool
    {
        return request()->attributes->get(self::ATTRIBUTE) instanceof Model;
    }

    public static function expiresAt(): ?Carbon
    {
        $expires = request()->query('expires');

        return is_numeric($expires) ? Carbon::createFromTimestamp((int) $expires, config('app.timezone')) : null;
    }

    private static function typeFor(Model $record): ?string
    {
        return array_search($record::class, self::TYPES, true) ?: null;
    }
}
