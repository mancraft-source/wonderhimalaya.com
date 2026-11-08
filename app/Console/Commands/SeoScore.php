<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\TeamMember;
use App\Models\Trip;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

#[Signature('seo:score')]
#[Description('Recalculate the stored SEO score of every trip, destination, activity, page, post and team member')]
class SeoScore extends Command
{
    /** @var array<int, class-string<Model>> */
    public const MODELS = [Trip::class, Destination::class, Activity::class, Page::class, Post::class, TeamMember::class];

    public function handle(): int
    {
        $this->info('Updated '.self::recalculate().' SEO scores.');

        return self::SUCCESS;
    }

    /** Stores fresh scores without firing model events or touching updated_at. */
    public static function recalculate(): int
    {
        $count = 0;

        foreach (self::MODELS as $class) {
            $class::query()->each(function (Model $model) use (&$count): void {
                $seo = (array) ($model->seo ?? []);
                $seo['score'] = $model->seoAnalysis()['score'];
                $model->newQueryWithoutScopes()->whereKey($model->getKey())->update(['seo' => json_encode($seo)]);
                $count++;
            });
        }

        return $count;
    }
}
