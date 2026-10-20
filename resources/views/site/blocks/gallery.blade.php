@php $images = collect($data['images'] ?? [])->filter()->map(fn ($p) => str_starts_with($p, 'http') ? $p : \Illuminate\Support\Facades\Storage::disk('public')->url($p)); @endphp
@if ($images->isNotEmpty())
    <section @class(['py-10' => $narrow, 'container-page py-16' => ! $narrow])>
        <div class="columns-2 gap-3 md:columns-3 [&>img]:mb-3">
            @foreach ($images as $src)<img src="{{ $src }}" alt="" loading="lazy" class="w-full rounded-2xl">@endforeach
        </div>
    </section>
@endif
