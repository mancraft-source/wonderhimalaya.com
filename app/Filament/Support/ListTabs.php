<?php

namespace App\Filament\Support;

use App\Enums\PublishStatus;
use Closure;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Saved quick filters for List pages: one scope drives both the tab's
 * query and its badge count, so the number always matches the rows shown.
 */
final class ListTabs
{
    /**
     * @param  Builder<Model>  $baseQuery  The resource's visible records (e.g. Resource::getEloquentQuery()).
     * @param  Closure(Builder): Builder  $scope
     */
    public static function counted(string $label, Builder $baseQuery, Closure $scope, string $badgeColor = 'gray'): Tab
    {
        return Tab::make($label)
            ->badge(fn (): ?int => $scope(clone $baseQuery)->count() ?: null)
            ->badgeColor($badgeColor)
            ->modifyQueryUsing(fn (Builder $query): Builder => $scope($query));
    }

    /**
     * Published / Scheduled / Drafts for models using HasPublishStatus. "Scheduled" is
     * published with a future publish date, so it is not live on the site yet.
     *
     * @param  Builder<Model>  $baseQuery
     * @return array<string, Tab>
     */
    public static function publishing(Builder $baseQuery): array
    {
        return [
            'published' => self::counted('Published', $baseQuery, fn (Builder $query): Builder => $query->published(), 'success'),
            'scheduled' => self::counted('Scheduled', $baseQuery, fn (Builder $query): Builder => $query
                ->where('status', PublishStatus::Published)
                ->where('published_at', '>', now()), 'info'),
            'drafts' => self::counted('Drafts', $baseQuery, fn (Builder $query): Builder => $query->where('status', PublishStatus::Draft)),
        ];
    }
}
