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
