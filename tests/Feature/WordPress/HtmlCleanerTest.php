<?php

use App\Services\WordPress\HtmlCleaner;
use App\Services\WordPress\UrlMap;

it('strips Elementor and Gutenberg markup down to semantic HTML', function () {
    $html = <<<'HTML'
<div data-elementor-type="wp-page" class="elementor elementor-4851">
  <section class="elementor-section" data-id="bceafd8" style="color:red">
    <div class="elementor-widget-container"><h1 class="elementor-heading-title">Nepal Visa</h1></div>
    <!-- wp:paragraph --><p class="wp-block-paragraph" style="font-size:2px">Hello <span style="x">world</span> &amp; <b>friends</b></p><!-- /wp:paragraph -->
    <p>&nbsp;</p><p></p>
    <div class="elementor-text-editor">Loose text <a href="#">anchor</a></div>
    <script>alert(1)</script><style>.x{}</style>
    <iframe src="https://evil.example.com/x"></iframe>
    <iframe src="https://www.youtube.com/embed/abc" width="560" height="315" frameborder="0"></iframe>
    <ul class="list"><li data-x="1"><i aria-hidden="true" class="fas fa-check"></i> Item</li></ul>
    <form><input name="q"></form>
    <div class="ti-widget"><div class="ti-review-item">Review</div></div>
  </section>
</div>
HTML;

    $clean = (new HtmlCleaner)->clean($html, 'Nepal Visa');

    expect($clean)
        ->not->toContain('elementor')
        ->not->toContain('style=')
        ->not->toContain('data-')
        ->not->toContain('<script')
        ->not->toContain('<div')
        ->not->toContain('<span')
        ->not->toContain('evil.example.com')
        ->not->toContain('Review')
        ->not->toContain('<h2>Nepal Visa</h2>')
        ->not->toContain('<p></p>')
        ->toContain('<p>Hello world &amp; <strong>friends</strong></p>')
        ->toContain('<p>Loose text anchor</p>')
        ->toContain('src="https://www.youtube.com/embed/abc"')
        ->toContain('<li>Item</li>');
});

it('rewrites internal links through the url map and keeps external ones', function () {
    $map = (new UrlMap)
        ->add('/trip/11n-12d-standardeverest-base-camp', '/trip/everest-base-camp-trek-fixed-departure')
        ->add('/nepal-visa', '/travel-guide/nepal-visa');

    $clean = (new HtmlCleaner($map))->clean(
        '<p><a href="https://wonderhimalaya.com/trip/11n-12d-standardeverest-base-camp/#itinerary">EBC</a>'
        .' <a href="https://www.wonderhimalaya.com/nepal-visa/">Visa</a>'
        .' <a href="https://wonderhimalaya.com/trip-types/premium/">Premium</a>'
        .' <a href="https://wonderhimalaya.com/some-unknown-page/">Unknown</a>'
        .' <a href="https://example.org/x">Ext</a></p>'
    );

    expect($clean)
        ->toContain('href="/trip/everest-base-camp-trek-fixed-departure#itinerary"')
        ->toContain('href="/travel-guide/nepal-visa"')
        ->toContain('href="/trips?tier=premium"')
        ->toContain('href="/some-unknown-page"')
        ->toContain('href="https://example.org/x" target="_blank" rel="noopener"');
});

it('rewrites images through the resolver and drops srcset', function () {
    $clean = (new HtmlCleaner)
        ->withImageResolver(fn (string $url, ?int $id) => $id === 7337 ? '/storage/wp-content/uploads/2026/04/a.jpg' : null)
        ->clean('<figure class="wp-block-image"><img class="wp-image-7337" src="https://wonderhimalaya.com/wp-content/uploads/2026/04/a-300x164.jpg" srcset="x 300w" alt=" Dordi "><figcaption class="c">Cap</figcaption></figure>');

    expect($clean)
        ->toContain('src="/storage/wp-content/uploads/2026/04/a.jpg"')
        ->toContain('alt="Dordi"')
        ->toContain('<figcaption>Cap</figcaption>')
        ->not->toContain('srcset');
});

it('handles UTF-8 text without mangling', function () {
    expect((new HtmlCleaner)->clean('<p>Nepal’s “roof” – Sagarmatha 8,848 m</p>'))
        ->toBe('<p>Nepal’s “roof” – Sagarmatha 8,848 m</p>');
});
