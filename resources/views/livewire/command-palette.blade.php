<div
    x-data="{
        abierto: @entangle('abierto'),
        selIndex: 0,
        totalItems: 0,
        lastFocused: null,
        init() {
            this.$watch('abierto', (val) => {
                if (val) {
                    this.lastFocused = document.activeElement;
                    this.selIndex = 0;
                    this.$nextTick(() => {
                        this.$refs.searchInput?.focus();
                        this.$refs.searchInput?.select();
                    });
                } else {
                    if (this.lastFocused && typeof this.lastFocused.focus === 'function') {
                        this.lastFocused.focus();
                    }
                }
            });
        },
        abrir() {
            this.abierto = true;
            $wire.abrir();
        },
        cerrar() {
            this.abierto = false;
            $wire.cerrar();
        },
        moverSel(delta) {
            const count = this.totalItems;
            if (count <= 0) return;
            this.selIndex = (this.selIndex + delta + count) % count;
            this.scrollSelectedIntoView();
        },
        scrollSelectedIntoView() {
            this.$nextTick(() => {
                const sel = this.$el.querySelector(`.sigem-pal-i[data-index='${this.selIndex}']`);
                if (sel) {
                    sel.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        navegar(url) {
            if (!url) {
                const sel = this.$el.querySelector(`.sigem-pal-i[data-index='${this.selIndex}']`);
                if (sel && sel.dataset.url) {
                    url = sel.dataset.url;
                }
            }
            if (url) {
                this.cerrar();
                window.location.href = url;
            }
        }
    }"
    @abrir-command-palette.window="abrir()"
    @keydown.window="if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); if (abierto) { cerrar(); } else { abrir(); } }"
    @keydown.escape.window="if (abierto) { cerrar(); }"
    class="sigem-palette-wrapper"
>
    @php
        $flatCount = 0;
        foreach ($grupos as $items) {
            $flatCount += count($items);
        }
    @endphp

    <div
        class="sigem-pal-ov"
        :class="{ 'show': abierto }"
        x-show="abierto"
        x-cloak
        @click.self="cerrar()"
        x-effect="totalItems = {{ $flatCount }}; if (selIndex >= totalItems) { selIndex = Math.max(0, totalItems - 1); }"
        role="dialog"
        aria-modal="true"
        aria-label="Buscador global SIGEM"
    >
        <div class="sigem-pal" @click.outside="cerrar()">
            {{-- Barra superior del buscador --}}
            <div class="sigem-pal-in">
                <span style="opacity: 0.7; font-size: 18px;" aria-hidden="true">🔍</span>
                <input
                    x-ref="searchInput"
                    wire:model.live.debounce.150ms="q"
                    type="text"
                    placeholder="Buscar equipos, solicitudes, mantenimientos o ir a una sección…"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Buscar equipos, solicitudes, mantenimientos o secciones"
                    @keydown.down.prevent="moverSel(1)"
                    @keydown.up.prevent="moverSel(-1)"
                    @keydown.enter.prevent="navegar()"
                >
                <button
                    type="button"
                    @click="cerrar()"
                    aria-label="Cerrar búsqueda"
                    style="background: transparent; border: 1px solid var(--sig-border); border-radius: 6px; padding: 2px 7px; color: var(--sig-muted); font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px;"
                >
                    <kbd style="font-family: inherit; font-size: inherit; background: transparent; border: 0; padding: 0;">Esc</kbd>
                </button>
            </div>

            {{-- Lista de resultados agrupados --}}
            <div class="sigem-pal-list" role="listbox">
                @php $currentIndex = 0; @endphp
                @forelse ($grupos as $grupoNombre => $items)
                    <div class="sigem-pal-g" role="presentation">{{ $grupoNombre }}</div>
                    @foreach ($items as $item)
                        @php $thisIdx = $currentIndex; @endphp
                        <div
                            class="sigem-pal-i"
                            :class="{ 'sel': selIndex === {{ $thisIdx }} }"
                            data-index="{{ $thisIdx }}"
                            data-url="{{ $item['url'] }}"
                            @mouseenter="selIndex = {{ $thisIdx }}"
                            @click="navegar('{{ $item['url'] }}')"
                            role="option"
                            :aria-selected="selIndex === {{ $thisIdx }} ? 'true' : 'false'"
                        >
                            <div class="sigem-pal-ico" aria-hidden="true">{{ $item['icono'] }}</div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="sigem-pal-t" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['titulo'] }}</div>
                                <div class="sigem-pal-s" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['subtitulo'] }}</div>
                            </div>
                        </div>
                        @php $currentIndex++; @endphp
                    @endforeach
                @empty
                    <div style="padding: 28px 16px; text-align: center; color: var(--sig-muted); font-size: 15px;">
                        No se encontraron resultados para "<strong>{{ $q }}</strong>"
                    </div>
                @endforelse
            </div>

            {{-- Pie de atajos de navegación universales --}}
            <div class="sigem-pal-foot">
                <span><kbd>↑</kbd> <kbd>↓</kbd> navegar</span>
                <span style="opacity: 0.5;">·</span>
                <span><kbd>Enter</kbd> abrir</span>
                <span style="opacity: 0.5;">·</span>
                <span><kbd>Esc</kbd> cerrar</span>
            </div>
        </div>
    </div>
</div>
