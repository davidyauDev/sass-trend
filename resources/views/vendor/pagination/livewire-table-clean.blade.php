@blaze(fold: true)

<?php
extract(Flux::forwardedAttributes($attributes, ['scrollTo']));
?>

@props([
    'paginator' => null,
    'scrollTo' => $scrollTo ?? null,
])

@php
$simple = ! $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;

$scrollToSelector = $scrollTo === true ? 'body' : $scrollTo;

$scrollIntoViewJsSnippet = ($scrollTo !== null && $scrollTo !== false)
    ? "(\$el.closest('{$scrollToSelector}') || \$el.closest('body').querySelector('{$scrollToSelector}')).scrollIntoView()"
    : '';
@endphp

@if ($simple)
    <div {{ $attributes->class('flex items-center justify-end') }} data-flux-pagination>
        @if ($paginator->hasPages())
            <div class="flex items-center rounded-xl border border-zinc-200 bg-white p-[1px] dark:border-white/10 dark:bg-white/[0.03]">
                @if ($paginator->onFirstPage())
                    <div class="flex size-9 items-center justify-center rounded-[10px] text-zinc-300 dark:text-zinc-500">
                        <flux:icon.chevron-left variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-right variant="micro" class="hidden rtl:inline" />
                    </div>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="flex size-9 items-center justify-center rounded-[10px] text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-white/[0.08] dark:hover:text-white">
                        <flux:icon.chevron-left variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-right variant="micro" class="hidden rtl:inline" />
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="flex size-9 items-center justify-center rounded-[10px] text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-white/[0.08] dark:hover:text-white">
                        <flux:icon.chevron-right variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-left variant="micro" class="hidden rtl:inline" />
                    </button>
                @else
                    <div class="flex size-9 items-center justify-center rounded-[10px] text-zinc-300 dark:text-zinc-500">
                        <flux:icon.chevron-right variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-left variant="micro" class="hidden rtl:inline" />
                    </div>
                @endif
            </div>
        @endif
    </div>
@else
    <div {{ $attributes->class('flex items-center justify-end') }} data-flux-pagination>
        @if ($paginator->hasPages())
            <div class="flex items-center rounded-xl border border-zinc-200 bg-white p-[1px] dark:border-white/10 dark:bg-white/[0.03]">
                @if ($paginator->onFirstPage())
                    <div class="flex size-9 items-center justify-center rounded-[10px] text-zinc-300 dark:text-zinc-500">
                        <flux:icon.chevron-left variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-right variant="micro" class="hidden rtl:inline" />
                    </div>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" aria-label="{{ __('pagination.previous') }}" class="flex size-9 items-center justify-center rounded-[10px] text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-white/[0.08] dark:hover:text-white">
                        <flux:icon.chevron-left variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-right variant="micro" class="hidden rtl:inline" />
                    </button>
                @endif

                @foreach (\Livewire\invade($paginator)->elements() as $element)
                    @if (is_string($element))
                        <div class="flex h-9 min-w-9 items-center justify-center px-2 text-xs font-medium text-zinc-400 dark:text-zinc-500">{{ $element }}</div>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <div wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}" aria-current="page" class="flex h-9 min-w-9 items-center justify-center rounded-[10px] bg-emerald-600 px-3 text-sm font-semibold text-white">
                                    {{ $page }}
                                </div>
                            @else
                                <button
                                    wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    type="button"
                                    class="flex h-9 min-w-9 items-center justify-center rounded-[10px] px-3 text-sm font-medium text-slate-700 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-white/[0.08] dark:hover:text-white"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" aria-label="{{ __('pagination.next') }}" class="flex size-9 items-center justify-center rounded-[10px] text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:text-zinc-300 dark:hover:bg-white/[0.08] dark:hover:text-white">
                        <flux:icon.chevron-right variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-left variant="micro" class="hidden rtl:inline" />
                    </button>
                @else
                    <div class="flex size-9 items-center justify-center rounded-[10px] text-zinc-300 dark:text-zinc-500">
                        <flux:icon.chevron-right variant="micro" class="rtl:hidden" />
                        <flux:icon.chevron-left variant="micro" class="hidden rtl:inline" />
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
