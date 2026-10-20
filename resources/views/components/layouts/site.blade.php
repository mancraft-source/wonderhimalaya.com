{{-- Public site layout (full design system lands here). Props: title, description --}}
@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | Wonder Himalaya' : 'Wonder Himalaya' }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main>{{ $slot }}</main>
</body>
</html>
