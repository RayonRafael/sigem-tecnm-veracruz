@props([
    'tipo',
    'id',
])

<div class="sigem-pdf-actions">
    <a href="{{ route('reportes.pdf', ['tipo' => $tipo, 'id' => $id, 'preview' => 1]) }}"
       target="_blank"
       class="sigem-btn-pdf-view"
       title="Abrir PDF en nueva pestaña">
        📄
    </a>
    <a href="{{ route('reportes.pdf', ['tipo' => $tipo, 'id' => $id, 'download' => 1]) }}"
       class="sigem-btn-pdf-download"
       title="Descargar PDF">
        ⬇
    </a>
</div>
