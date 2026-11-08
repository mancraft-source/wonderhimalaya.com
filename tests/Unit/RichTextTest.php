<?php

use App\Support\RichText;

it('turns newline-separated reasons into a real list', function () {
    $html = RichText::normalize("<p>Some of the reasons to trek are: Clear weather and views.\nMild temperature\nStable trails</p>");

    expect($html)->toBe('<p>Some of the reasons to trek are:</p><ul><li>Clear weather and views</li><li>Mild temperature</li><li>Stable trails</li></ul>');
});

it('keeps a lead sentence that ends with a colon as its own paragraph', function () {
    $html = RichText::normalize("<p>The reasons for choosing the winter season are as follows:\nFewer crowds\nClear sky</p>");

    expect($html)->toBe('<p>The reasons for choosing the winter season are as follows:</p><ul><li>Fewer crowds</li><li>Clear sky</li></ul>');
});

it('uses line breaks rather than a list when the lines are prose', function () {
    $long = str_repeat('word ', 30);
    $html = RichText::normalize("<p>Intro\n{$long}</p>");

    expect($html)->toContain('<br>')->not->toContain('<ul>');
});

it('groups consecutive image paragraphs into a gallery and leaves single images alone', function () {
    $pair = RichText::normalize('<p><img src="/a.jpg"></p><p><img src="/b.jpg"></p><p>Text</p><p><img src="/c.jpg"></p>');

    expect($pair)->toContain('<div class="prose-gallery not-prose"><img src="/a.jpg"><img src="/b.jpg"></div>')
        ->toContain('<p><img src="/c.jpg"></p>');
});

it('turns statement-length headings into callouts and anchors real headings', function () {
    $statement = str_repeat('Wonder Himalaya will always be by your side. ', 5);
    $html = RichText::normalize("<h2>Which season?</h2><h2>Which season?</h2><h2>{$statement}</h2>");

    expect($html)->toContain('<h2 id="which-season">')
        ->toContain('<h2 id="which-season-2">')
        ->toContain('<blockquote class="callout">')
        ->and(RichText::outline($html))->toHaveCount(2)
        ->and(RichText::outline($html)[0])->toBe(['id' => 'which-season', 'text' => 'Which season?', 'level' => 2]);
});

it('preserves UTF-8 text', function () {
    expect(RichText::normalize('<p>It doesn’t matter — Lush greenery &amp; nature</p>'))
        ->toBe('<p>It doesn’t matter — Lush greenery &amp; nature</p>');
});

it('promotes title lines fused into prose paragraphs to headings', function () {
    $sentence = 'You walk along the Kali Gandaki Gorge, the deepest gorge in the world, past Thakali villages.';
    $html = RichText::normalize("<p>The region is popular with trekkers of every level.\nExperience the Culture and Landscape</p><p>{$sentence}\nDiscover Unique Features\n{$sentence}</p>");

    expect($html)->toBe('<p>The region is popular with trekkers of every level.</p><h2 id="experience-the-culture-and-landscape">Experience the Culture and Landscape</h2>'
        ."<p>{$sentence}</p><h2 id=\"discover-unique-features\">Discover Unique Features</h2><p>{$sentence}</p>");
});

it('does not promote key-value lines or short items without a following sentence', function () {
    $sentence = str_repeat('A long line of descriptive prose about the trip. ', 3);
    $html = RichText::normalize("<p>{$sentence}\nBest Seasons: Spring and Autumn\n__________\nShivpuri National Park\nChitwan National Park</p>");

    expect($html)->not->toContain('<h2')->not->toContain('____');
});

it('splits back-to-back sentences into paragraphs but keeps line breaks after short labels', function () {
    $long = 'You are with a genuine trekking company with professional and experienced guides and porters.';
    $html = RichText::normalize("<p>Please note\n{$long}\nWe take care of your health and safety.</p>");

    expect($html)->toBe("<p>Please note<br>{$long}</p><p>We take care of your health and safety.</p>");
});

it('repairs double-encoded ampersands and drops stray &nbsp; spacers between blocks', function () {
    expect(RichText::normalize("<p>Call +977-1 &amp;amp; +977-2</p>\n&nbsp;<p>Next</p>"))
        ->toBe('<p>Call +977-1 &amp; +977-2</p><p>Next</p>');
});

it('reads numbered headings as process steps only when they run 1, 2, 3 in order', function () {
    $outline = fn (array $texts) => array_map(fn ($text, $i) => ['id' => 'h'.$i, 'text' => $text, 'level' => 2], $texts, array_keys($texts));

    expect(RichText::stepsFromOutline($outline(['Intro', 'Step 1: Choose a trip', 'Step 2 – Pay the deposit', '3. Fly to Kathmandu'])))
        ->toBe([
            ['id' => 'h1', 'number' => 1, 'title' => 'Choose a trip'],
            ['id' => 'h2', 'number' => 2, 'title' => 'Pay the deposit'],
            ['id' => 'h3', 'number' => 3, 'title' => 'Fly to Kathmandu'],
        ])
        ->and(RichText::stepsFromOutline($outline(['Step 1: A', 'Step 2: B'])))->toBe([])
        ->and(RichText::stepsFromOutline($outline(['Step 1: A', 'Step 3: B', 'Step 4: C'])))->toBe([])
        ->and(RichText::stepsFromOutline($outline(['8,849 m above sea level', 'Autumn', 'Spring'])))->toBe([]);
});

it('inserts a step heading before the paragraph a step starts at and skips steps it cannot place', function () {
    $html = RichText::markSteps('<h2 id="intro">Intro</h2><p>Regarding deposit: pay 30%.</p>', [
        ['id' => 'step-1', 'number' => 1, 'title' => 'Pay the 30% deposit', 'before' => 'Regarding deposit'],
        ['id' => 'step-2', 'number' => 2, 'title' => 'Missing', 'before' => 'Nowhere in the text'],
    ], 2);

    expect($html)->toBe('<h2 id="intro">Intro</h2><h2 id="step-1" class="process-step"><span class="process-step__num" aria-hidden="true">1</span>'
        .'<span class="process-step__text"><span class="process-step__of">Step 1 of 2</span>Pay the 30% deposit</span></h2><p>Regarding deposit: pay 30%.</p>');
});

it('turns label: value lists into a detail card with the note as its footnote', function () {
    $html = RichText::detailCards('<ul><li>Pay To: Wonder Himalaya</li><li>Branch: Thamel</li><li>Swift Code: HIMANPKA</li><li>Note: Bank charges are up to the client.</li></ul><ul><li>Clear weather</li><li>Mild: yes</li><li>Stable trails</li></ul>');

    expect($html)->toContain('<div class="detail-card not-prose"><dl><div><dt>Pay To</dt><dd>Wonder Himalaya</dd></div>')
        ->toContain('<p class="detail-card__note">Bank charges are up to the client.</p></div>')
        ->toContain('<li>Clear weather</li>');
});

it('turns dead accordion toggle links into question headings', function () {
    $html = RichText::normalize('<p><a href="#collapse-abc123">'."\n".'Is the Company Legally Registered?'."\n".'</a></p><p>Answer.</p>');

    expect($html)->toStartWith('<h3 id="is-the-company-legally-registered">Is the Company Legally Registered?</h3>');
});

it('turns styling headings into an eyebrow and a lead, and marks notes and policy lists', function () {
    $point = str_repeat('A policy point that is a full sentence long enough. ', 2);
    $html = RichText::normalize('<h4>Book with Confidence</h4><h2>Peace of mind</h2><h4>Secure your plans today without the worry. Enjoy hassle-free bookings.</h4>'
        ."<ul><li>{$point}</li><li>{$point}</li><li>Regarding Trip Cancellation In case you cancel, the deposit is kept as a fee for our costs.</li></ul><p>Note: we are registered.</p>");

    expect($html)->toContain('<p class="kicker">Book with Confidence</p>')
        ->toContain('<p class="lead">Secure your plans today')
        ->toContain('<ul class="points">')
        ->toContain('<li><strong>Regarding Trip Cancellation.</strong> In case you cancel')
        ->toContain('<p class="note">Note: we are registered.</p>');
});

it('drops a first heading that only repeats the page title', function () {
    expect(RichText::withoutTitleHeading('<h2>Alert</h2><p>Text</p>', 'Alerts'))->toBe('<p>Text</p>')
        ->and(RichText::withoutTitleHeading('<h2>Safety first</h2><p>Text</p>', 'Alerts'))->toBe('<h2>Safety first</h2><p>Text</p>');
});
