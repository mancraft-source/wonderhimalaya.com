<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Scheme;

/**
 * Content-Security-Policy for the public site. Scripts use per-request nonces
 * (`@cspNonce`, and Vite tags get it automatically via ContentSecurityPolicy middleware).
 */
class SitePolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FRAME_ANCESTORS, Keyword::SELF)
            ->add(Directive::FORM_ACTION, [
                Keyword::SELF,
                'https://www.paypal.com', 'https://www.sandbox.paypal.com',
                // Cybersource Secure Acceptance (bank card gateway)
                'https://secureacceptance.cybersource.com', 'https://testsecureacceptance.cybersource.com',
            ])
            ->add(Directive::SCRIPT, [
                Keyword::SELF,
                'https://cdn.jsdelivr.net', 'https://unpkg.com',
                'https://www.googletagmanager.com', 'https://www.google-analytics.com', 'https://*.google-analytics.com',
                'https://challenges.cloudflare.com',
                'https://www.paypal.com', 'https://*.paypal.com', 'https://*.paypalobjects.com',
                'https://maps.googleapis.com',
            ])
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, [
                Keyword::SELF, Keyword::UNSAFE_INLINE, // Livewire/Alpine inject inline styles
                'https://fonts.googleapis.com', 'https://cdn.jsdelivr.net', 'https://unpkg.com',
            ])
            ->add(Directive::FONT, [Keyword::SELF, Scheme::DATA, 'https://fonts.gstatic.com', 'https://cdn.jsdelivr.net'])
            ->add(Directive::IMG, [Keyword::SELF, Scheme::DATA, Scheme::BLOB, Scheme::HTTPS])
            ->add(Directive::MEDIA, [Keyword::SELF, Scheme::BLOB, Scheme::HTTPS])
            ->add(Directive::CONNECT, [
                Keyword::SELF,
                'https://www.google-analytics.com', 'https://*.google-analytics.com', 'https://*.analytics.google.com',
                'https://www.googletagmanager.com', 'https://challenges.cloudflare.com',
                'https://*.paypal.com',
            ])
            ->add(Directive::FRAME, [
                Keyword::SELF,
                'https://challenges.cloudflare.com',
                'https://www.paypal.com', 'https://*.paypal.com',
                'https://www.youtube.com', 'https://www.youtube-nocookie.com', 'https://player.vimeo.com',
                'https://www.google.com', 'https://maps.google.com', 'https://www.googletagmanager.com',
            ])
            ->add(Directive::WORKER, [Keyword::SELF, Scheme::BLOB]);
    }
}
