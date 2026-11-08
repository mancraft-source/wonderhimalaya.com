{{-- A process (booking, permits…) as a step tracker that follows the reader: numbered nodes on a rail that fills as you
     scroll, the step being read highlighted and earlier ones checked (initStepper in app.js). Every link jumps to its step.
     variant="rail": the sticky vertical tracker for the left column. variant="chips": a sticky, swipeable strip for small screens.
     <x-site.process-stepper :steps="[['id' => 'step-1', 'number' => 1, 'title' => 'Choose your trip', 'summary' => '…'], …]" /> --}}
@props(['steps', 'eyebrow' => 'The process', 'heading' => null, 'variant' => 'rail'])
@php
    $total = count($steps);
@endphp
@if ($variant === 'chips')
    <nav {{ $attributes->class('stepper-chips') }} aria-label="{{ $eyebrow }}" data-stepper>
        <ol class="flex snap-x gap-2 overflow-x-auto px-4 py-3 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" data-stepper-track>
            @foreach ($steps as $step)
                <li class="shrink-0 snap-start" data-step-item>
                    <a href="#{{ $step['id'] }}" data-step-link class="stepper-chip">
                        <span class="stepper-chip__node" aria-hidden="true"><span class="stepper-num">{{ $step['number'] }}</span><x-heroicon-m-check class="stepper-check size-3.5" /></span>
                        <span class="whitespace-nowrap">{{ $step['title'] }}</span>
                        <span class="sr-only">(step {{ $step['number'] }} of {{ $total }})</span>
                    </a>
                </li>
            @endforeach
        </ol>
        <span class="stepper-chips__bar" aria-hidden="true"></span>
    </nav>
@else
    <nav {{ $attributes }} aria-label="{{ $eyebrow }}" data-stepper>
        <p class="eyebrow">{{ $eyebrow }}</p>
        @if ($heading)
            <p class="mt-3 font-display text-[1.75rem] leading-tight text-juniper-800 [@media(max-height:52rem)]:hidden">{{ $heading }}</p>
        @endif
        <div class="relative mt-7">
            <span class="stepper-rail" aria-hidden="true"><span class="stepper-rail__fill" data-stepper-fill></span></span>
            <ol data-stagger>
                @foreach ($steps as $step)
                    <li class="stepper-item" data-step-item>
                        <a href="#{{ $step['id'] }}" data-step-link class="stepper-link group">
                            <span class="stepper-node" aria-hidden="true"><span class="stepper-num">{{ $step['number'] }}</span><x-heroicon-m-check class="stepper-check size-4" /></span>
                            <span class="block min-w-0 pb-6">
                                <span class="data block text-[0.7rem] tracking-[0.14em] text-stone-500 uppercase">Step {{ str_pad($step['number'], 2, '0', STR_PAD_LEFT) }}<span class="sr-only"> of {{ $total }}</span></span>
                                <span class="stepper-title">{{ $step['title'] }}</span>
                                @if (! empty($step['summary']))
                                    <span class="mt-1.5 block text-sm leading-relaxed text-ink-soft">{{ $step['summary'] }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </div>
    </nav>
@endif
