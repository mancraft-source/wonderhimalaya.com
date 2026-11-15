<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<title>{{ App\Support\EmailBranding::company()['name'] }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<style>
@media only screen and (max-width: 600px) {
.inner-body {
width: 100% !important;
}

.footer {
width: 100% !important;
}

.content-cell {
padding: 24px !important;
}
}

@media only screen and (max-width: 500px) {
.button {
width: 100% !important;
text-align: center !important;
}
}

@media (prefers-color-scheme: dark) {
body,
.wrapper,
.body {
background-color: #021131 !important;
}

.inner-body {
background-color: #082047 !important;
border-color: #0e2e62 !important;
border-top-color: #ecc77e !important;
}

.inner-body p,
.inner-body li,
.inner-body td,
.footer p {
color: #e9e2d4 !important;
}

.inner-body h1,
.inner-body h2,
.inner-body h3,
.inner-body strong,
.header .wordmark,
.footer .footer-name {
color: #f4efe6 !important;
}

.header .tagline,
.inner-body a,
.footer a {
color: #ecc77e !important;
}

.inner-body a.button-primary,
.inner-body a.button-success {
background-color: #ecc77e !important;
border-color: #ecc77e !important;
color: #021131 !important;
}

.panel-content {
background-color: #021131 !important;
}

.subcopy {
border-top-color: #0e2e62 !important;
}
}
</style>
{!! $head ?? '' !!}
</head>
<body>
@if (filled(trim((string) ($preheader ?? ''))))
<div class="preheader" style="display: none; max-height: 0; max-width: 0; overflow: hidden; mso-hide: all; font-size: 1px; line-height: 1px; color: transparent; opacity: 0;">{{ $preheader }}{!! str_repeat('&#847;&zwnj;&nbsp;', 60) !!}</div>
@endif

<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
{!! $header ?? '' !!}

<!-- Email Body -->
<tr>
<td class="body" width="100%" cellpadding="0" cellspacing="0" style="border: hidden !important;">
<table class="inner-body" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<!-- Body content -->
<tr>
<td class="content-cell">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
