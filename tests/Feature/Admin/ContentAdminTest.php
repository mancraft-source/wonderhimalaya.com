<?php

use App\Enums\PublishStatus;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Menus\Pages\CreateMenu;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\PostCategories\PostCategoryResource;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Redirects\Pages\CreateRedirect;
use App\Filament\Resources\Redirects\Pages\ListRedirects;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Filament\Resources\Testimonials\TestimonialResource;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\User;
use Database\Factories\MenuFactory;
use Database\Factories\PageFactory;
use Database\Factories\PostCategoryFactory;
use Database\Factories\PostFactory;
use Database\Factories\RedirectFactory;
use Database\Factories\TeamMemberFactory;
use Database\Factories\TestimonialFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SiteSettingsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create()->assignRole('admin');
});

dataset('content resources', [
    'pages' => [PageResource::class, fn () => PageFactory::new()->create()],
    'posts' => [PostResource::class, fn () => PostFactory::new()->inCategory()->byAuthor()->create()],
    'post categories' => [PostCategoryResource::class, fn () => PostCategoryFactory::new()->create()],
    'team members' => [TeamMemberResource::class, fn () => TeamMemberFactory::new()->guide()->create()],
    'testimonials' => [TestimonialResource::class, fn () => TestimonialFactory::new()->create()],
    'menus' => [MenuResource::class, fn () => MenuFactory::new()->create()],
    'redirects' => [RedirectResource::class, fn () => RedirectFactory::new()->create()],
]);

it('loads the index, create and edit pages', function (string $resource, Closure $makeRecord) {
    $record = $makeRecord();

    $this->actingAs($this->admin);

    $this->get($resource::getUrl('index'))->assertOk();
    $this->get($resource::getUrl('create'))->assertOk();
    $this->get($resource::getUrl('edit', ['record' => $record]))->assertOk();
})->with('content resources');

it('lets the content role manage pages', function () {
    $editor = User::factory()->create()->assignRole('content');

    $this->actingAs($editor)->get(PageResource::getUrl('index'))->assertOk();
});

it('creates a page with blocks and builds its path', function () {
    $parent = PageFactory::new()->create(['title' => 'Travel guide', 'slug' => 'travel-guide']);

    $this->actingAs($this->admin);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Nepal Visa',
            'slug' => 'nepal-visa',
            'parent_id' => $parent->id,
            'template' => 'guide',
            'status' => PublishStatus::Published->value,
            'blocks' => [
                ['type' => 'hero', 'data' => ['heading' => 'Getting a Nepal visa', 'eyebrow' => 'Guide']],
                ['type' => 'rich_text', 'data' => ['content' => '<p>Visas on arrival are available.</p>']],
                ['type' => 'faq', 'data' => ['heading' => 'FAQ', 'items' => [['question' => 'Cost?', 'answer' => 'USD 30 for 15 days.']]]],
                ['type' => 'trip_grid', 'data' => ['heading' => 'Popular', 'source' => 'popular', 'limit' => 6]],
            ],
            'seo' => ['title' => 'Nepal visa guide', 'description' => 'How to get a visa', 'noindex' => false],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::where('slug', 'nepal-visa')->firstOrFail();

    expect($page->path)->toBe('travel-guide/nepal-visa')
        ->and($page->publicPath())->toBe('/travel-guide/nepal-visa')
        ->and($page->template)->toBe('guide')
        ->and($page->status)->toBe(PublishStatus::Published)
        ->and($page->seo['title'])->toBe('Nepal visa guide')
        ->and(collect($page->blocks)->pluck('type')->all())->toBe(['hero', 'rich_text', 'faq', 'trip_grid'])
        ->and($page->blocks[0]['data']['heading'])->toBe('Getting a Nepal visa')
        ->and(array_values($page->blocks[2]['data']['items'])[0]['question'])->toBe('Cost?');
});

it('rejects a page whose path is already taken', function () {
    PageFactory::new()->create(['title' => 'About', 'slug' => 'about']);

    $this->actingAs($this->admin);

    Livewire::test(CreatePage::class)
        ->fillForm(['title' => 'About us', 'slug' => 'about', 'template' => 'default', 'status' => 'draft'])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('records a redirect when a page slug changes', function () {
    $page = PageFactory::new()->create(['title' => 'Old', 'slug' => 'old-name']);

    $this->actingAs($this->admin);

    Livewire::test(EditPage::class, ['record' => $page->getRouteKey()])
        ->fillForm(['slug' => 'new-name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($page->fresh()->path)->toBe('new-name')
        ->and(Redirect::where('from_path', '/old-name')->value('to_path'))->toBe('/new-name');
});

it('creates a post with category, author and tags', function () {
    $category = PostCategoryFactory::new()->create();
    $author = TeamMemberFactory::new()->create();

    $this->actingAs($this->admin);

    Livewire::test(CreatePost::class)
        ->fillForm([
            'title' => 'Best time to trek Everest',
            'slug' => 'best-time-to-trek-everest',
            'post_category_id' => $category->id,
            'team_member_id' => $author->id,
            'body' => '<p>'.str_repeat('word ', 500).'</p>',
            'status' => 'published',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $post = Post::where('slug', 'best-time-to-trek-everest')->firstOrFail();

    expect($post->post_category_id)->toBe($category->id)
        ->and($post->team_member_id)->toBe($author->id)
        ->and($post->reading_minutes)->toBeGreaterThan(1);
});

it('creates a menu with nested items', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateMenu::class)
        ->fillForm([
            'name' => 'Main',
            'location' => 'header',
            'items' => [
                ['label' => 'Trekking', 'url' => '/trekking', 'open_new_tab' => false, 'children' => [
                    ['label' => 'Everest', 'url' => '/destinations/everest', 'open_new_tab' => false],
                ]],
                ['label' => 'Blog', 'url' => '/blog', 'open_new_tab' => false, 'children' => []],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $items = Menu::where('location', 'header')->firstOrFail()->items;

    expect(array_is_list($items))->toBeTrue()
        ->and($items[0]['label'])->toBe('Trekking')
        ->and(array_values($items[0]['children'])[0]['url'])->toBe('/destinations/everest');
});

it('normalises redirect paths and enforces uniqueness', function () {
    $this->actingAs($this->admin);

    Livewire::test(CreateRedirect::class)
        ->fillForm(['from_path' => '/Old-Trek/', 'to_path' => '/trips/new-trek/', 'status_code' => 301, 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Redirect::where('from_path', '/old-trek')->value('to_path'))->toBe('/trips/new-trek');

    Livewire::test(CreateRedirect::class)
        ->fillForm(['from_path' => 'old-trek', 'to_path' => '/elsewhere', 'status_code' => 301])
        ->call('create')
        ->assertHasFormErrors(['from_path']);
});

it('imports redirects from csv', function () {
    $file = tempnam(sys_get_temp_dir(), 'redirects');
    file_put_contents($file, "from,to,status\n/a,/b\n/c,/d,302\n,\n/gone,,410\n");

    $result = ListRedirects::importCsv($file);

    expect($result['imported'])->toBe(3)
        ->and(Redirect::where('from_path', '/c')->value('status_code'))->toBe(302)
        ->and(Redirect::where('from_path', '/gone')->value('status_code'))->toBe(410);

    unlink($file);
});

it('loads and saves site settings', function () {
    $this->seed(SiteSettingsSeeder::class);

    $this->actingAs($this->admin)
        ->get(ManageSiteSettings::getUrl())
        ->assertOk()
        ->assertSee('Wonder Himalaya');

    $legalName = Setting::get('company.legal_name');

    Livewire::test(ManageSiteSettings::class)
        ->assertSchemaStateSet(['company.name' => 'Wonder Himalaya'])
        ->fillForm([
            'company.name' => 'Wonder Himalaya Treks',
            'company.phone' => '+977-1-5555555',
            'booking.deposit_percent' => 25,
            'booking.currency' => 'EUR',
            'analytics.gtm_id' => 'GTM-ABC123',
            'stats' => [
                ['value' => '3,000+', 'label' => 'Happy travellers'],
                ['value' => '5.0', 'label' => 'Google rating'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(Setting::get('company.name'))->toBe('Wonder Himalaya Treks')
        ->and(Setting::get('company.legal_name'))->toBe($legalName)
        ->and(Setting::get('booking.deposit_percent'))->toBe(25)
        ->and(Setting::get('booking.currency'))->toBe('EUR')
        ->and(Setting::get('analytics.gtm_id'))->toBe('GTM-ABC123')
        ->and(Setting::get('stats'))->toBe([
            ['value' => '3,000+', 'label' => 'Happy travellers'],
            ['value' => '5.0', 'label' => 'Google rating'],
        ]);
});

it('forbids the settings page without the settings permission', function () {
    $editor = User::factory()->create()->assignRole('content');

    $this->actingAs($editor)->get(ManageSiteSettings::getUrl())->assertForbidden();
});

it('saves registration details, B2B credentials and keeps review keys not on the form', function () {
    $this->seed(SiteSettingsSeeder::class);
    $this->actingAs($this->admin);

    Livewire::test(ManageSiteSettings::class)
        ->fillForm([
            'company.registration' => 'Govt. reg. 123456 · NTB licence 1234',
            'b2b.credentials' => ['Nepal Tourism Board licensed', 'TAAN member'],
            'reviews.google_rating' => '',
            'reviews.google_label' => 'Excellent',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::get('company.registration'))->toBe('Govt. reg. 123456 · NTB licence 1234')
        ->and(Setting::get('b2b.credentials'))->toBe(['Nepal Tourism Board licensed', 'TAAN member'])
        ->and(Setting::get('reviews.google_rating'))->toBeNull()
        ->and(Setting::get('reviews.google_label'))->toBe('Excellent')
        ->and(Setting::get('reviews.tripadvisor_award'))->toBe("Travellers' Choice")
        ->and(Setting::get('reviews.google_count'))->toBe(78);

    Livewire::test(ManageSiteSettings::class)
        ->assertSchemaStateSet(['b2b.credentials' => ['Nepal Tourism Board licensed', 'TAAN member']]);
});
