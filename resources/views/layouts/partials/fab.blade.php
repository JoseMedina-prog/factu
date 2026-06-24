<div x-data="{ open: false }" class="fixed bottom-6 right-6 z-50">
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute bottom-16 right-0 mb-2 w-64 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden" @click.outside="open = false">
        <div class="p-2">
            <p class="px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Crear nuevo</p>
            <a href="{{ route('invoices.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50:bg-slate-700 transition-colors group">
                <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200:bg-blue-900/50 transition-colors">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Factura</p>
                    <p class="text-xs text-slate-500">Crear nueva factura</p>
                </div>
            </a>
            <a href="{{ route('credit-notes.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50:bg-slate-700 transition-colors group">
                <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center group-hover:bg-emerald-200:bg-emerald-900/50 transition-colors">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Nota de Crédito</p>
                    <p class="text-xs text-slate-500">Devolución o ajuste</p>
                </div>
            </a>
            <a href="{{ route('clients.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50:bg-slate-700 transition-colors group">
                <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center group-hover:bg-purple-200:bg-purple-900/50 transition-colors">
                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Cliente</p>
                    <p class="text-xs text-slate-500">Agregar nuevo cliente</p>
                </div>
            </a>
            <a href="{{ route('products.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-50:bg-slate-700 transition-colors group">
                <div class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center group-hover:bg-amber-200:bg-amber-900/50 transition-colors">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-900">Producto</p>
                    <p class="text-xs text-slate-500">Agregar producto</p>
                </div>
            </a>
        </div>
    </div>

    <button @click="open = !open" class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 hover:shadow-xl" :class="open ? 'bg-slate-600 rotate-45' : 'bg-primary-600 hover:bg-primary-700'" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
        <svg class="w-6 h-6 text-white transition-transform duration-300" :class="{ 'rotate-45': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>
</div>
