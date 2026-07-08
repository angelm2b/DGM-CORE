@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación">
        <ul>
            @if ($paginator->onFirstPage())
                <li><span>« Anterior</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">« Anterior</a></li>
            @endif

            <li><span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span></li>

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente »</a></li>
            @else
                <li><span>Siguiente »</span></li>
            @endif
        </ul>
    </nav>
@endif
