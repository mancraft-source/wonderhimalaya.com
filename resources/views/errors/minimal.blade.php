{{-- Standalone error shell: no database, no composers — safe when the app itself is failing. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>@yield('title') | Wonder Himalaya</title>
    <link rel="icon" href="/favicon.png">
    <style>
        :root { color-scheme: light; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4efe6 url('/images/topo.svg'); font-family: ui-sans-serif, system-ui, sans-serif; color: #1b1b1a; }
        main { max-width: 36rem; padding: 3rem 1.5rem; text-align: center; }
        img { height: 6rem; width: auto; }
        p.code { font: 500 .8rem ui-monospace, monospace; color: #b5532e; letter-spacing: .1em; margin-top: 2rem; }
        h1 { font: 400 clamp(2rem, 1.4rem + 2.4vw, 3.2rem)/1.1 Georgia, serif; color: #1f3a33; margin: 1rem 0; }
        p { line-height: 1.6; color: #4a4843; }
        a.btn { display: inline-block; margin-top: 1.5rem; padding: .9rem 1.6rem; border-radius: 999px; background: #b5532e; color: #fff; text-decoration: none; font-weight: 600; font-size: .9rem; }
    </style>
</head>
<body>
    <main>
        <img src="/images/brand/logo-badge-160.webp" alt="Wonder Himalaya">
        <p class="code">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <a class="btn" href="/">Back to home</a>
        <p style="margin-top:2rem;font-size:.85rem">Need help now? <a href="https://wa.me/9779860880827" style="color:#1f3a33">WhatsApp us</a> or call +977-01-5928066.</p>
    </main>
</body>
</html>
