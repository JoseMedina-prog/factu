@props([
    'title' => null,
    'subtitle' => null,
    'back' => null,
    'icon' => null,
    'avatarInitials' => null,
    'avatarSource' => null,
    'action' => null,
    'method' => 'POST',
    'submitLabel' => 'Guardar',
    'cancelHref' => null,
    'footerHint' => null,
    'tips' => [],
])

@php
    $hasAvatar = $avatarInitials !== null || $avatarSource !== null;
    $initialInitials = $avatarInitials ?? 'CL';
@endphp

<div class="form-layout-grid">
    <div class="form-main">
        <div class="form-stepper-bar">
            @if($back)
                <a href="{{ $back }}" class="form-back-btn" aria-label="Volver">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
            @endif
            <div class="stepper">
                <div class="stepper-item stepper-active">
                    <div class="stepper-dot"><span>1</span></div>
                    <div class="stepper-label">
                        <span class="stepper-title">Información</span>
                        <span class="stepper-sub">Datos básicos</span>
                    </div>
                </div>
                <div class="stepper-line stepper-line-active"></div>
                <div class="stepper-item">
                    <div class="stepper-dot"><span>2</span></div>
                    <div class="stepper-label">
                        <span class="stepper-title">Confirmar</span>
                        <span class="stepper-sub">Revisar y guardar</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ $action }}" method="POST" class="form-modern-card">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif

            <div class="form-modern-hero">
                @if($hasAvatar)
                    <div class="hero-avatar" @if($avatarSource) id="heroAvatar" @endif>
                        <span @if($avatarSource) data-avatar-text @endif>{{ $initialInitials }}</span>
                    </div>
                @endif
                <div class="hero-content">
                    <h1 class="hero-title">{{ $title }}</h1>
                    @if($subtitle)
                        <p class="hero-subtitle">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <div class="form-modern-body">
                {{ $slot }}
            </div>

            <div class="form-modern-footer">
                @if($footerHint)
                    <div class="footer-hint">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $footerHint }}</span>
                    </div>
                @else
                    <div></div>
                @endif
                <div class="footer-actions">
                    @if($cancelHref)
                        <a href="{{ $cancelHref }}" class="btn-modern btn-modern-ghost">Cancelar</a>
                    @endif
                    <button type="submit" class="btn-modern btn-modern-primary">
                        <span>{{ $submitLabel }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <aside class="form-sidebar">
        @if(!empty($tips))
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <div class="sidebar-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="sidebar-title">Consejos</h3>
                </div>
                <ul class="sidebar-tips">
                    @foreach($tips as $tip)
                        <li class="sidebar-tip">
                            <span class="tip-bullet"></span>
                            <p>{!! $tip !!}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="sidebar-card sidebar-card-info" x-data="{ helpOpen: false }">
            <div class="info-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h4 class="info-title">¿Necesitas ayuda?</h4>
            <p class="info-text">Contacta a nuestro equipo de soporte si tienes dudas sobre este formulario.</p>
            <button type="button" @click="helpOpen = true" class="info-link">
                Ir al centro de ayuda
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <div x-show="helpOpen" x-cloak
                 x-transition.opacity
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                 @keydown.escape.window="helpOpen = false">
                <div @click.outside="helpOpen = false"
                     x-transition
                     class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 sm:p-8"
                     role="dialog"
                     aria-modal="true">
                    <div class="flex items-start justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Centro de Ayuda</h3>
                                <p class="text-xs text-slate-500">Estamos aquí para ayudarte</p>
                            </div>
                        </div>
                        <button @click="helpOpen = false" type="button" class="text-slate-400 hover:text-slate-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <a href="mailto:soporte@factu.co" class="help-channel">
                            <div class="help-channel-icon help-channel-icon-mail">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="help-channel-title">Correo electrónico</p>
                                <p class="help-channel-detail">soporte@factu.co</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        <a href="tel:+573000000000" class="help-channel">
                            <div class="help-channel-icon help-channel-icon-phone">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="help-channel-title">Teléfono</p>
                                <p class="help-channel-detail">+57 300 XXX XXXX</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>

                        <a href="https://wa.me/573000000000" target="_blank" rel="noopener" class="help-channel">
                            <div class="help-channel-icon help-channel-icon-whatsapp">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="help-channel-title">WhatsApp</p>
                                <p class="help-channel-detail">Respuesta inmediata</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    <div class="mt-6 pt-5 border-t border-slate-100 text-center">
                        <p class="text-xs text-slate-500">
                            Horario de atención: <strong class="text-slate-700">Lun-Vie 8am-6pm</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>

@if($avatarSource)
    @once
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const source = document.querySelector('[data-avatar-source="{{ $avatarSource }}"]');
                    const avatar = document.getElementById('heroAvatar');
                    if (!source || !avatar) return;
                    const target = avatar.querySelector('[data-avatar-text]');
                    if (!target) return;
                    const update = () => {
                        const value = (source.value || '').trim();
                        const initials = value
                            ? value.split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase()
                            : '{{ $initialInitials }}';
                        target.textContent = initials || '{{ $initialInitials }}';
                    };
                    source.addEventListener('input', update);
                    update();
                });
            </script>
        @endpush
    @endonce
@endif
