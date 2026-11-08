<?php

use App\Enums\PublishStatus;
use App\Models\Page;

it('shows the booking page as a step tracker anchored in the text', function () {
    Page::create(['title' => 'How to book a trek', 'slug' => 'how-to-book-a-trip-with-wonder-himalaya', 'template' => 'legal', 'status' => PublishStatus::Published,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<h2>How to book?</h2><p>As you are here at the booking page, check the itinerary.</p>'
            .'<p>Regarding Deposite: deposit 30%.</p><p>Swift/ Wired Transfer</p><ul><li>Pay To: Wonder Himalaya</li><li>Branch: Thamel</li><li>Swift Code: HIMANPKA</li></ul>']]]]);

    $this->get('/how-to-book-a-trip-with-wonder-himalaya')->assertOk()
        ->assertSee('data-stepper', false)
        ->assertSeeInOrder(['href="#step-1"', 'href="#step-2"', 'href="#step-3"', 'href="#step-4"'], false)
        ->assertSeeInOrder(['id="step-1"', 'As you are here', 'id="step-2"', 'Regarding Deposite', 'id="step-3"', 'Swift/ Wired Transfer', 'id="step-4"'], false)
        ->assertSee('<dt>Swift Code</dt>', false);
});

it('builds the tracker from numbered step headings on any guide page', function () {
    $guide = Page::create(['title' => 'Travel Guide', 'slug' => 'travel-guide', 'status' => PublishStatus::Published]);
    Page::create(['title' => 'Getting a permit', 'slug' => 'permits', 'parent_id' => $guide->id, 'template' => 'guide', 'status' => PublishStatus::Published,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<h2>Step 1: Apply online</h2><p>A.</p><h2>Step 2: Pay the fee</h2><p>B.</p><h2>Step 3: Collect it</h2><p>C.</p>']]]]);

    $this->get('/travel-guide/permits')->assertOk()
        ->assertSee('data-stepper', false)
        ->assertSee('href="#step-1-apply-online"', false)
        ->assertSee('<span class="process-step__of">Step 2 of 3</span>Pay the fee', false);
});

it('keeps the section navigation on guide pages that are not a process', function () {
    $guide = Page::create(['title' => 'Travel Guide', 'slug' => 'travel-guide', 'status' => PublishStatus::Published]);
    Page::create(['title' => 'Nepal Visa', 'slug' => 'nepal-visa', 'parent_id' => $guide->id, 'template' => 'guide', 'status' => PublishStatus::Published,
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => '<h2>Visa on arrival</h2><p>A.</p><h2>Fees</h2><p>B.</p>']]]]);

    $this->get('/travel-guide/nepal-visa')->assertOk()
        ->assertDontSee('data-stepper', false)
        ->assertSee('data-toc', false);
});
