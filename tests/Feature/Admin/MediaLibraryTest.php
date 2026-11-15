<?php

use App\Filament\Pages\MediaLibrary;
use App\Filament\Resources\Trips\TripResource;
use App\Models\Post;
use App\Models\Trip;
use App\Models\User;
use Database\Factories\PostFactory;
use Database\Factories\TripFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->editor = User::factory()->role('content')->create();
    Storage::fake('public');
});

/** @param  array<string, mixed>  $attributes */
function addImage(Model $owner, string $collection = 'hero', array $attributes = []): Media
{
    return $owner->media()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'collection_name' => $collection,
        'name' => 'everest',
        'file_name' => 'everest.jpg',
        'mime_type' => 'image/jpeg',
        'disk' => 'public',
        'conversions_disk' => 'public',
        'size' => 250_000,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => [],
        'responsive_images' => [],
    ], $attributes));
}

/** An image whose owner record has been removed from the database. */
function addOrphanImage(): Media
{
    $trip = TripFactory::new()->create();
    $media = addImage($trip, attributes: ['file_name' => 'orphan.jpg']);
    Trip::query()->whereKey($trip->id)->toBase()->delete();

    return $media->fresh();
}

it('is available to content editors only', function () {
    $this->actingAs($this->editor)->get(MediaLibrary::getUrl())->assertOk()->assertSee('Media library');
    $this->actingAs(User::factory()->sales()->create())->get(MediaLibrary::getUrl())->assertForbidden();
});

it('shows where each image is used, its size and alt text', function () {
    $trip = TripFactory::new()->create(['title' => 'Everest Base Camp Trek']);
    $used = addImage($trip, attributes: ['file_name' => 'ebc-hero.jpg', 'size' => 3_500_000, 'custom_properties' => ['alt' => 'Prayer flags at Gorak Shep', 'width' => 1920, 'height' => 1280]]);
    $orphan = addOrphanImage();

    Livewire::actingAs($this->editor)
        ->test(MediaLibrary::class)
        ->assertCanSeeTableRecords([$used, $orphan])
        ->assertTableColumnStateSet('used_by', 'Everest Base Camp Trek', $used)
        ->assertTableColumnStateSet('used_by', 'Not used', $orphan)
        ->assertTableColumnStateSet('alt', 'Prayer flags at Gorak Shep', $used)
        ->assertSee([TripResource::getUrl('edit', ['record' => $trip]), '1920 × 1280', 'large']);
});

it('filters large files, missing alt text and unused images', function () {
    $trip = TripFactory::new()->create();
    $large = addImage($trip, 'gallery', ['size' => 2_000_000, 'custom_properties' => ['alt' => 'Ama Dablam']]);
    $noAlt = addImage($trip, 'gallery', ['custom_properties' => ['alt' => '']]);
    $orphan = addOrphanImage();

    Livewire::actingAs($this->editor)
        ->test(MediaLibrary::class)
        ->filterTable('large')
        ->assertCanSeeTableRecords([$large])
        ->assertCanNotSeeTableRecords([$noAlt, $orphan])
        ->resetTableFilters()
        ->filterTable('missing_alt')
        ->assertCanSeeTableRecords([$noAlt, $orphan])
        ->assertCanNotSeeTableRecords([$large])
        ->resetTableFilters()
        ->filterTable('unused')
        ->assertCanSeeTableRecords([$orphan])
        ->assertCanNotSeeTableRecords([$large, $noAlt])
        ->resetTableFilters()
        ->filterTable('model_type', 'trip')
        ->assertCanSeeTableRecords([$large, $noAlt]);
});

it('counts images of trashed records as used', function () {
    $post = PostFactory::new()->create();
    $media = addImage($post);
    $post->delete();

    Livewire::actingAs($this->editor)
        ->test(MediaLibrary::class)
        ->filterTable('unused')
        ->assertCanNotSeeTableRecords([$media]);

    expect(Post::withTrashed()->find($post->id)->getFirstMedia('hero'))->not->toBeNull();
});

it('sets alt text in bulk where the website reads it', function () {
    $trip = TripFactory::new()->create();
    $first = addImage($trip, 'gallery');
    $second = addImage($trip, 'gallery');

    Livewire::actingAs($this->editor)
        ->test(MediaLibrary::class)
        ->selectTableRecords([$first, $second])
        ->callAction(TestAction::make('editAltText')->table()->bulk(), data: ['alt' => 'Sunrise over Kanchenjunga'])
        ->assertNotified('Alt text saved on 2 images');

    expect($first->fresh()->getCustomProperty('alt'))->toBe('Sunrise over Kanchenjunga')
        ->and($second->fresh()->getCustomProperty('alt'))->toBe('Sunrise over Kanchenjunga');

    expect(view('components.site.picture', ['model' => $trip, 'collection' => 'gallery'])->render())->toContain('alt="Sunrise over Kanchenjunga"');
});

it('deletes only unused images', function () {
    $used = addImage(TripFactory::new()->create());
    $orphan = addOrphanImage();

    Livewire::actingAs($this->editor)
        ->test(MediaLibrary::class)
        ->assertActionHidden(TestAction::make('delete')->table($used))
        ->assertActionVisible(TestAction::make('delete')->table($orphan))
        ->selectTableRecords([$used, $orphan])
        ->callAction(TestAction::make('deleteUnused')->table()->bulk())
        ->assertNotified('Deleted 1 unused image');

    expect(Media::find($used->id))->not->toBeNull()
        ->and(Media::find($orphan->id))->toBeNull();
});
