@props([
    'panel' => 'admin',
    'saludo' => '',
    'nombreCorto' => '',
])

@if($panel === 'admin')
    <div class="sigem-hero">
        <div>
            <h1 id="sigem-hero-hi">👋 <span x-text="getGreeting()">{{ $saludo }}</span>, {{ $nombreCorto }}</h1>
            <p>Supervisa las solicitudes de material y autorizaciones de mantenimiento del Centro de Cómputo.</p>
        </div>
    </div>
@else
    <div class="sigem-hero" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1 id="sigem-hero-hi">👋 <span x-text="getGreeting()">{{ $saludo }}</span>, Alumno</h1>
            <p>Gestiona tus solicitudes de material, reportes de mantenimiento y consulta el inventario del instituto.</p>
        </div>
        <div class="sigem-hero-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="{{ url('/servicio-social/solicituds?abrir=create') }}" class="sigem-btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                ＋ Crear solicitud
            </a>
            <a href="{{ url('/servicio-social/mantenimientos?abrir=create') }}" class="sigem-rep-btn-sec" style="background: white; border: 1px solid var(--sig-border); color: var(--sig-text); padding: 8px 14px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                🔧 Reportar mantenimiento
            </a>
        </div>
    </div>
@endif
