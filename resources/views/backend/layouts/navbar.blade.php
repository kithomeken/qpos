<nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom shadow-sm py-2">
    <ul class="navbar-nav align-items-center">
        <li class="nav-item">
            <a class="nav-link text-dark" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-md-block ml-2">
            <h5 class="mb-0 font-weight-bold text-uppercase tracking-tight" style="color: #008b8b;">
                 @yield('title')
            </h5>
        </li>
    </ul>

    <ul class="navbar-nav ml-auto align-items-center">

        @can('sale_create')
        <li class="nav-item mr-3">
            <a class="btn bg-gradient-primary btn-sm text-white rounded-pill px-3 shadow-sm d-flex align-items-center" 
               href="{{route('backend.admin.cart.index')}}">
                <i class="fas fa-cash-register mr-2"></i>
                <span class="font-weight-bold">POS</span>
            </a>
        </li>
        @endcan

        <li class="nav-item d-none d-sm-block">
            <a class="nav-link text-muted" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand"></i>
            </a>
        </li>
        
        <li class="nav-item dropdown ml-2">
            <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#" aria-expanded="false">
                <div class="user-avatar-wrapper mr-2">
                    <i class="fas fa-user-circle fa-lg text-secondary"></i>
                </div>
                <i class="fas fa-chevron-down small text-muted"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow border-0 mt-2">
                <div class="dropdown-header text-uppercase font-weight-bold small">User Account</div>
                <a href="{{ route('backend.admin.profile') }}" class="dropdown-item py-2">
                    <i class="fas fa-user-cog mr-2 text-muted"></i>
                    Profile Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" class="dropdown-item py-2 text-danger">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Sign Out
                </a>
            </div>
        </li>
    </ul>
</nav>