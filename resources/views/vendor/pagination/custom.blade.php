@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="custom-pagination" style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:12px;padding:12px 18px;background:#f8fafc;border-top:1px solid #e2e8f0;border-radius:0 0 12px 12px">
        <div style="font-size:12.5px;color:#64748b;font-weight:500">
            Showing <b style="color:#0f172a">{{ $paginator->firstItem() }}</b> to <b style="color:#0f172a">{{ $paginator->lastItem() }}</b> of <b style="color:#0f172a">{{ $paginator->total() }}</b> records
        </div>

        <div style="display:inline-flex;align-items:center;gap:6px">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:12.5px;font-weight:600;color:#94a3b8;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:not-allowed;user-select:none">
                    <i class="fa-solid fa-chevron-left" style="font-size:10px"></i> Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:12.5px;font-weight:600;color:#1e293b;background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;text-decoration:none;transition:all 0.15s ease" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff';this.style.borderColor='#cbd5e1'">
                    <i class="fa-solid fa-chevron-left" style="font-size:10px"></i> Prev
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span style="padding:6px 8px;font-size:12.5px;color:#94a3b8;user-select:none">{{ $element }}</span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;font-size:12.5px;font-weight:700;color:#ffffff;background:#0f172a;border:1px solid #0f172a;border-radius:8px;user-select:none;box-shadow:0 1px 2px rgba(0,0,0,0.1)">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" style="display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;font-size:12.5px;font-weight:600;color:#334155;background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;text-decoration:none;transition:all 0.15s ease" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff';this.style.borderColor='#cbd5e1'">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:12.5px;font-weight:600;color:#1e293b;background:#ffffff;border:1px solid #cbd5e1;border-radius:8px;text-decoration:none;transition:all 0.15s ease" onmouseover="this.style.background='#f1f5f9';this.style.borderColor='#94a3b8'" onmouseout="this.style.background='#ffffff';this.style.borderColor='#cbd5e1'">
                    Next <i class="fa-solid fa-chevron-right" style="font-size:10px"></i>
                </a>
            @else
                <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;font-size:12.5px;font-weight:600;color:#94a3b8;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;cursor:not-allowed;user-select:none">
                    Next <i class="fa-solid fa-chevron-right" style="font-size:10px"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
