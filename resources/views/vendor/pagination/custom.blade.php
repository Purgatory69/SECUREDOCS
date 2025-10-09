@if ($paginator->hasPages())
    <div class="flex items-center justify-center space-x-2">
        {{-- First Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-gray-400 cursor-not-allowed">
                <img src="{{ asset('caret-double-left.png') }}" alt="First" class="w-3 h-3 opacity-50">
            </span>
        @else
            <a href="{{ $paginator->url(1) }}" class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-white hover:bg-[#55597C] transition-colors">
                <img src="{{ asset('caret-double-left.png') }}" alt="First" class="w-3 h-3">
            </a>
        @endif

        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-gray-400 cursor-not-allowed">
                <img src="{{ asset('caret-left.png') }}" alt="Previous" class="w-3 h-3 opacity-50">
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-white hover:bg-[#55597C] transition-colors">
                <img src="{{ asset('caret-left.png') }}" alt="Previous" class="w-3 h-3">
            </a>
        @endif

        {{-- Pagination Numbers --}}
        @php
            $current = $paginator->currentPage();
            $last = $paginator->lastPage();
            
            // Show 5 pages maximum
            $start = max($current - 2, 1);
            $end = min($start + 4, $last);
            
            // Adjust if we're near the end
            if ($end - $start < 4) {
                $start = max($last - 4, 1);
            }
        @endphp

        @foreach (range($start, $end) as $page)
            @if ($page == $current)
                <span class="flex items-center justify-center w-8 h-8 rounded bg-[#f89c00] text-black font-bold">
                    {{ $page }}
                </span>
            @else
                <a href="{{ $paginator->url($page) }}" class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-white hover:bg-[#55597C] transition-colors">
                    {{ $page }}
                </a>
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-white hover:bg-[#55597C] transition-colors">
                <img src="{{ asset('caret-right.png') }}" alt="Next" class="w-3 h-3">
            </a>
        @else
            <span class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-gray-400 cursor-not-allowed">
                <img src="{{ asset('caret-right.png') }}" alt="Next" class="w-3 h-3 opacity-50">
            </span>
        @endif

        {{-- Last Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->url($paginator->lastPage()) }}" class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-white hover:bg-[#55597C] transition-colors">
                <img src="{{ asset('caret-double-right.png') }}" alt="Last" class="w-3 h-3">
            </a>
        @else
            <span class="flex items-center justify-center w-8 h-8 rounded bg-[#3C3F58] text-gray-400 cursor-not-allowed">
                <img src="{{ asset('caret-double-right.png') }}" alt="Last" class="w-3 h-3 opacity-50">
            </span>
        @endif
    </div>
@endif