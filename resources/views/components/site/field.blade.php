{{-- <x-site.field label="Email" name="email" type="email" wire:model="email" required /> --}}
@props(['label', 'name', 'type' => 'text', 'hint' => null, 'optional' => false, 'prefix' => 'f'])
@php $id = $prefix.'-'.str_replace('.', '-', $name); @endphp
<div {{ $attributes->only('class')->class('min-w-0') }}>
    <label for="{{ $id }}" class="field-label">{{ $label }} @if ($optional)<span class="font-normal text-stone-400">(optional)</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" rows="4" {{ $attributes->except('class')->class('field') }} @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror></textarea>
    @elseif ($type === 'select')
        <select id="{{ $id }}" {{ $attributes->except('class')->class('field') }} @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>{{ $slot }}</select>
    @else
        <input id="{{ $id }}" type="{{ $type }}" {{ $attributes->except('class')->class('field') }} @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
    @endif
    @if ($hint)<p class="mt-1.5 text-xs text-stone-500">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@enderror
</div>
