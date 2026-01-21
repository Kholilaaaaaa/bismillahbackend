<header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-sm">
    <div class="container-fluid">
        <!-- Toggle sidebar button -->
        <button class="navbar-toggler me-2 sidebar-toggler" type="button" id="sidebarToggle" aria-label="Toggle sidebar">
            <i class="fas fa-bars"></i>
        </button>

        <!-- User Dropdown -->
        <div class="dropdown ms-auto">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown"
                data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar me-2">
                    @if (Auth::guard('admin')->user()->foto_profile)
                        <img src="{{ asset('admins/' . Auth::guard('admin')->user()->foto_profile) }}" alt="Profile"
                            class="rounded-circle" style="width: 32px; height: 32px;">
                    @else
                        <i class="fas fa-user-circle"></i>
                    @endif
                </div>
                <div class="user-info text-start text-white">
                    <div class="user-name">{{ Auth::guard('admin')->user()->nama_lengkap }}</div>
                    <div class="user-role">Administrator</div>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i
                            class="fas fa-user me-2"></i>Profile</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>