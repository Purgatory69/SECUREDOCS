@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">
        {{-- Previous Page Link --}}
        <div class="flex justify-start flex-1">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-[#3C3F58] border border-gray-300 cursor-default rounded-md">
                    <img src="{{ asset('caret-double-left.png') }}" alt="First" class="w-3 h-3 mr-1 opacity-50">
                    First
                </span>
            @else
                <a href="{{ $paginator->url(1) }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-[#3C3F58] border border-gray-300 rounded-md hover:bg-[#55597C] transition-colors">
                    <img src="{{ asset('caret-double-left.png') }}" alt="First" class="w-3 h-3 mr-1">
                    First
                </a>
            @endif

            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-[#3C3F58] border border-gray-300 cursor-default rounded-md">
                    <img src="{{ asset('caret-left.png') }}" alt="Previous" class="w-3 h-3 mr-1 opacity-50">
                    Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-white bg-[#3C3F58] border border-gray-300 rounded-md hover:bg-[#55597C] transition-colors">
                    <img src="{{ asset('caret-left.png') }}" alt="Previous" class="w-3 h-3 mr-1">
                    Prev
                </a>
            @endif
        </div>

        {{-- Pagination Elements --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
            <div>
                <span class="relative z-0 inline-flex rounded-md shadow-sm">
                    {{-- Array Of Links --}}
                    @php
                        // Custom pagination logic - show 5 pages max
                        $current = $paginator->currentPage();
                        $last = $paginator->lastPage();
                        $start = max($current - 2, 1);
                        $end = min($start + 4, $last);
                        
                        // Adjust start if we're near the end
                        if ($end - $start < 4) {
                            $start = max($last - 4, 1);
                        }
                    @endphp

                    @foreach (range($start, $end) as $page)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-black bg-[#f89c00] border border-gray-300 cursor-default">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-white bg-[#3C3F58] border border-gray-300 hover:bg-[#55597C] transition-colors">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                </span>
            </div>
        </div>

        {{-- Next Page Link --}}
        <div class="flex justify-end flex-1">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-[#3C3F58] border border-gray-300 rounded-md hover:bg-[#55597C] transition-colors">
                    Next
                    <img src="{{ asset('caret-right.png') }}" alt="Next" class="w-3 h-3 ml-1">
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-[#3C3F58] border border-gray-300 cursor-default rounded-md">
                    Next
                    <img src="{{ asset('caret-right.png') }}" alt="Next" class="w-3 h-3 ml-1 opacity-50">
                </span>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->url($paginator->lastPage()) }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-white bg-[#3C3F58] border border-gray-300 rounded-md hover:bg-[#55597C] transition-colors">
                    Last
                    <img src="{{ asset('caret-double-right.png') }}" alt="Last" class="w-3 h-3 ml-1">
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-[#3C3F58] border border-gray-300 cursor-default rounded-md">
                    Last
                    <img src="{{ asset('caret-double-right.png') }}" alt="Last" class="w-3 h-3 ml-1 opacity-50">
                </span>
            @endif
        </div>
    </nav>
@endif