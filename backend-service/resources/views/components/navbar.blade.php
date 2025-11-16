<nav class="navbar navbar-expand-md navbar-dark shadow-sm sticky-top" id="mainNavbar"
    style="background-color: #7060c8; transition: transform 0.3s ease-in-out;">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.public_name') }}"
                style="height: 32px; width: 32px; margin-right: 8px;">
            {{ config('app.public_name') }}
        </a>

        @auth
            <!-- User avatar for mobile (toggler) -->
            <button class="navbar-toggler border-0 p-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <div class="bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center overflow-hidden"
                    style="width: 32px; height: 32px;">
                    @if (auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="rounded-circle"
                            style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <span class="text-white fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    @endif
                </div>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto w-100">
                    <li class="nav-item d-md-none">
                        <a class="nav-link text-white" href="{{ route('dashboard') }}">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item d-md-none">
                        <a class="nav-link text-white" href="{{ route('user.settings') }}">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </li>
                    <li class="nav-item d-md-none">
                        <a class="nav-link text-white" href="{{ route('account.manage') }}">
                            <i class="fas fa-user-cog me-2"></i>Account Management
                        </a>
                    </li>
                    <li class="nav-item d-md-none">
                        <a class="nav-link text-white" href="{{ route('billing.subscriptions') }}">
                            <i class="fas fa-credit-card me-2"></i>Billing & Subscriptions
                        </a>
                    </li>
                    <li class="nav-item d-md-none">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="nav-link text-white text-decoration-none w-100 text-start bg-transparent border-0">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </button>
                        </form>
                    </li>

                    <!-- Desktop view: User dropdown -->
                    <li class="nav-item dropdown d-none d-md-block ms-auto">
                        <a class="nav-link d-flex align-items-center" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center me-2 overflow-hidden"
                                style="width: 32px; height: 32px;">
                                @if (auth()->user()->avatar)
                                    <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}"
                                        class="rounded-circle" style="width: 100%; height: 100%; object-fit: cover;">
                                @else
                                    <span
                                        class="text-white fw-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                @endif
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                <small class="text-muted">{{ auth()->user()->email }}</small>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('user.settings') }}">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('account.manage') }}">
                                    <i class="fas fa-user-cog me-2"></i>Account Management
                                </a></li>
                            <li><a class="dropdown-item" href="{{ route('billing.subscriptions') }}">
                                    <i class="fas fa-credit-card me-2"></i>Billing & Subscriptions
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        @else
            <!-- Guest view: Login button -->
            <div class="ms-auto">
                <a href="{{ route('login') }}" class="btn btn-light">
                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                </a>
            </div>
        @endauth
    </div>
</nav>

<script>
    // Auto-hide/show navbar on scroll with smooth animation
    (function() {
        const navbar = document.getElementById('mainNavbar');
        const navbarCollapse = document.getElementById('navbarNav');
        const navbarToggler = document.querySelector('.navbar-toggler');
        let lastScrollTop = 0;
        let isTogglerClicked = false;

        // Track when toggler is clicked to prevent hiding
        if (navbarToggler) {
            navbarToggler.addEventListener('click', function() {
                isTogglerClicked = true;
                navbar.style.transform = 'translateY(0)';

                // Reset flag after animation completes
                setTimeout(function() {
                    isTogglerClicked = false;
                }, 500);
            });
        }

        window.addEventListener('scroll', function() {
            // Don't hide navbar if mobile menu is open or toggler was just clicked
            if (isTogglerClicked || (navbarCollapse && navbarCollapse.classList.contains('show'))) {
                navbar.style.transform = 'translateY(0)';
                return;
            }

            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (scrollTop > 10) {
                // Hide when scrolling down, show when scrolling up
                if (scrollTop > lastScrollTop) {
                    // Scrolling down - hide navbar
                    navbar.style.transform = 'translateY(-100%)';
                } else {
                    // Scrolling up - show navbar
                    navbar.style.transform = 'translateY(0)';
                }
            } else {
                // At top of page - always visible
                navbar.style.transform = 'translateY(0)';
            }

            lastScrollTop = scrollTop;
        });

        // Ensure navbar is visible when mobile menu opens/closes
        if (navbarCollapse) {
            navbarCollapse.addEventListener('show.bs.collapse', function() {
                navbar.style.transform = 'translateY(0)';
            });

            navbarCollapse.addEventListener('hide.bs.collapse', function() {
                navbar.style.transform = 'translateY(0)';
            });
        }
    })();
</script>
