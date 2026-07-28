@extends('errors.layout')

@section('title', 'Acceso denegado')
@section('code', '403')
@section('message', 'Acceso denegado')
@section('submessage', 'No tienes permiso para acceder a esta sección.')

@section('action')
    <a href="{{ url(auth()->check() ? (auth()->user()->hasRole('Administrador') || auth()->user()->tipo_usuario === 'Administrador' ? '/admin' : '/servicio-social') : '/login') }}" class="btn">
        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        Ir a mi panel
    </a>
@endsection
