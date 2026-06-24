<aside class="fixed left-0 top-0 bottom-0 w-64 bg-white border-r border-slate-200 flex flex-col z-30">
    <div class="p-6 border-b border-slate-100">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="w-9 h-9 bg-primary-600 rounded-lg flex items-center justify-center">
                <x-icon name="invoice" class="w-5 h-5 text-white" />
            </div>
            <span class="text-xl font-bold text-slate-900">Factu</span>
        </a>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto" aria-label="Navegación principal">
        <a href="{{ route('dashboard') }}"
           class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <x-icon name="dashboard" class="w-5 h-5" />
            Dashboard
        </a>

        <a href="{{ route('clients.index') }}"
           class="nav-link {{ request()->routeIs('clients.*') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('clients.*')) aria-current="page" @endif>
            <x-icon name="users" class="w-5 h-5" />
            Clientes
        </a>

        <a href="{{ route('products.index') }}"
           class="nav-link {{ request()->routeIs('products.*') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('products.*')) aria-current="page" @endif>
            <x-icon name="product" class="w-5 h-5" />
            Productos
        </a>

        <a href="{{ route('invoices.index') }}"
           class="nav-link {{ request()->routeIs('invoices.*') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('invoices.*')) aria-current="page" @endif>
            <x-icon name="invoice" class="w-5 h-5" />
            Facturas
        </a>

        <a href="{{ route('credit-notes.index') }}"
           class="nav-link {{ request()->routeIs('credit-notes.*') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('credit-notes.*')) aria-current="page" @endif>
            <x-icon name="invoice" class="w-5 h-5" />
            Notas Crédito
        </a>

        <a href="{{ route('reports.sales') }}"
           class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('reports.*')) aria-current="page" @endif>
            <x-icon name="chart" class="w-5 h-5" />
            Reportes
        </a>

        @can('viewAny', App\Models\Payment::class)
            <a href="{{ route('payments.index') }}"
               class="nav-link {{ request()->routeIs('payments.*') ? 'nav-link-active' : '' }}">
                <x-icon name="invoice" class="w-5 h-5" />
                Pagos
            </a>
        @endcan

        <a href="{{ route('accounts-receivable.index') }}"
           class="nav-link {{ request()->routeIs('accounts-receivable.*') ? 'nav-link-active' : '' }}">
            <x-icon name="invoice" class="w-5 h-5" />
            Por cobrar
        </a>

        <a href="{{ route('inventory.index') }}"
           class="nav-link {{ request()->routeIs('inventory.*') ? 'nav-link-active' : '' }}">
            <x-icon name="product" class="w-5 h-5" />
            Inventario
        </a>

        <a href="{{ route('settings.index') }}"
           class="nav-link {{ request()->routeIs('settings.index') || request()->routeIs('settings.update') || request()->routeIs('settings.logo') ? 'nav-link-active' : '' }}"
           @if(request()->routeIs('settings.index') || request()->routeIs('settings.update') || request()->routeIs('settings.logo')) aria-current="page" @endif>
            <x-icon name="settings" class="w-5 h-5" />
            Configuración
        </a>

        @can('viewAny', App\Models\NumberingRange::class)
            <a href="{{ route('settings.numbering.index') }}"
               class="nav-link {{ request()->routeIs('settings.numbering.*') ? 'nav-link-active' : '' }}">
                <x-icon name="invoice" class="w-5 h-5" />
                Numeración DIAN
            </a>
        @endcan

        @can('viewAny', App\Models\Tenant::class)
            <div class="pt-4 mt-4 border-t border-slate-100">
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Administración</p>
                <a href="{{ route('admin.tenants.index') }}"
                   class="nav-link {{ request()->routeIs('admin.tenants.*') ? 'nav-link-active' : '' }}">
                    <x-icon name="dashboard" class="w-5 h-5" />
                    Empresas
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">
                    <x-icon name="user" class="w-5 h-5" />
                    Usuarios
                </a>
            </div>
        @endcan
    </nav>

    <div class="p-4 border-t border-slate-100">
        <div class="flex items-center gap-3 px-3 py-3 bg-slate-50 rounded-xl mb-3">
            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                <span class="text-sm font-semibold text-primary-600">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-900 truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500 truncate">{{ auth()->user()->tenant?->name ?? 'Sin empresa' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('logout') }}" id="logoutForm">
            @csrf
            <button type="submit" class="btn btn-outline w-full text-red-600 border-red-200 hover:bg-red-50 hover:border-red-300">
                <x-icon name="logout" class="w-4 h-4" />
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
