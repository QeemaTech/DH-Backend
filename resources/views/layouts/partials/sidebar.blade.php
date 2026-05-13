<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar" style="overflow-y: scroll">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                @auth
                    @if(auth()->user()->hasRole('admin'))
                        <!-- Admin Menu -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Catalog & Content') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                                <i class="bi bi-grid"></i>
                                <span>{{ __('Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.variants*') ? 'active' : '' }}" href="{{ route('admin.variants.index') }}">
                                <i class="bi bi-tags"></i>
                                <span>{{ __('Variants') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                                <i class="bi bi-box-seam"></i>
                                <span>{{ __('Products') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.product-ratings*') ? 'active' : '' }}" href="{{ route('admin.product-ratings.index') }}">
                                <i class="bi bi-star"></i>
                                <span>{{ __('Product Ratings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.product-reports*') ? 'active' : '' }}" href="{{ route('admin.product-reports.index') }}">
                                <i class="bi bi-flag"></i>
                                <span>{{ __('Product Reports') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.branches*') ? 'active' : '' }}" href="{{ route('admin.branches.index') }}">
                                <i class="bi bi-shop"></i>
                                <span>{{ __('Branches') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.sliders*') ? 'active' : '' }}" href="{{ route('admin.sliders.index') }}">
                                <i class="bi bi-images"></i>
                                <span>{{ __('Sliders') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.faqs*') ? 'active' : '' }}" href="{{ route('admin.faqs.index') }}">
                                <i class="bi bi-question-circle"></i>
                                <span>{{ __('FAQs') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                                <i class="bi bi-ticket-perforated"></i>
                                <span>{{ __('Coupons') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Digital Products') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.digital-categories*') ? 'active' : '' }}" href="{{ route('admin.digital-categories.index') }}">
                                <i class="bi bi-grid-3x3-gap"></i>
                                <span>{{ __('Digital Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.digital-sub-categories*') ? 'active' : '' }}" href="{{ route('admin.digital-sub-categories.index') }}">
                                <i class="bi bi-diagram-3"></i>
                                <span>{{ __('Digital Sub Categories') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.digital-merchants*') ? 'active' : '' }}" href="{{ route('admin.digital-merchants.index') }}">
                                <i class="bi bi-building"></i>
                                <span>{{ __('Digital Merchants') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.digital-products*') ? 'active' : '' }}" href="{{ route('admin.digital-products.index') }}">
                                <i class="bi bi-bag-check"></i>
                                <span>{{ __('Digital Products') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Vendors & Customers') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.vendors*') ? 'active' : '' }}" href="{{ route('admin.vendors.index') }}">
                                <i class="bi bi-people"></i>
                                <span>{{ __('Vendors') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                                <i class="bi bi-people-fill"></i>
                                <span>{{ __('Customers') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Subscriptions & Requests') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.subscriptions*') ? 'active' : '' }}" href="{{ route('admin.subscriptions.index') }}">
                                <i class="bi bi-credit-card-2-front"></i>
                                <span>{{ __('Subscriptions') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.category-requests*') ? 'active' : '' }}" href="{{ route('admin.category-requests.index') }}">
                                <i class="bi bi-inbox"></i>
                                <span>{{ __('Category Requests') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.variant-requests*') ? 'active' : '' }}" href="{{ route('admin.variant-requests.index') }}">
                                <i class="bi bi-inbox"></i>
                                <span>{{ __('Variant Requests') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">
                                <i class="bi bi-inbox"></i>
                                <span>{{ __('Tickets') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Orders & Finance') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                                <i class="bi bi-cart-check"></i>
                                <span>{{ __('Orders') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.order-refund-requests*') ? 'active' : '' }}" href="{{ route('admin.order-refund-requests.index') }}">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>{{ __('Order Refund Requests') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.vendor-withdrawals*') ? 'active' : '' }}" href="{{ route('admin.vendor-withdrawals.index') }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>{{ __('Vendor Withdrawals') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Ratings & Support') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.product-ratings*') ? 'active' : '' }}" href="{{ route('admin.product-ratings.index') }}">
                                <i class="bi bi-star"></i>
                                <span>{{ __('Product Ratings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.product-reports*') ? 'active' : '' }}" href="{{ route('admin.product-reports.index') }}">
                                <i class="bi bi-flag"></i>
                                <span>{{ __('Product Reports') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.vendor-ratings*') ? 'active' : '' }}" href="{{ route('admin.vendor-ratings.index') }}">
                                <i class="bi bi-star-half"></i>
                                <span>{{ __('Vendor Ratings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.vendor-reports*') ? 'active' : '' }}" href="{{ route('admin.vendor-reports.index') }}">
                                <i class="bi bi-flag-fill"></i>
                                <span>{{ __('Vendor Reports') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.fraud-reports*') ? 'active' : '' }}" href="{{ route('admin.fraud-reports.index') }}">
                                <i class="bi bi-shield-exclamation"></i>
                                <span>{{ __('Fraud Reports') }}</span>
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Analytics') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                                <i class="bi bi-graph-up"></i>
                                <span>{{ __('Reports & Analytics') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reports.earnings') ? 'active' : '' }}" href="{{ route('admin.reports.earnings') }}">
                                <i class="bi bi-wallet2"></i>
                                <span>{{ __('Earnings Dashboard') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reports.vendor-performance') ? 'active' : '' }}" href="{{ route('admin.reports.vendor-performance') }}">
                                <i class="bi bi-person-lines-fill"></i>
                                <span>{{ __('Vendor Performance') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.reports.product-performance') ? 'active' : '' }}" href="{{ route('admin.reports.product-performance') }}">
                                <i class="bi bi-bar-chart-line"></i>
                                <span>{{ __('Product Performance') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.order-refund-requests*') ? 'active' : '' }}" href="{{ route('admin.order-refund-requests.index') }}">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span>{{ __('Order Refund Requests') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.vendor-withdrawals*') ? 'active' : '' }}" href="{{ route('admin.vendor-withdrawals.index') }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>{{ __('Vendor Withdrawals') }}</span>
                            </a>
                        </li>

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('System') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.countries*') ? 'active' : '' }}" href="{{ route('admin.countries.index') }}">
                                <i class="bi bi-globe2"></i>
                                <span>{{ __('Countries & verification') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
                                <i class="bi bi-gear"></i>
                                <span>{{ __('Settings') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('security*') ? 'active' : '' }}" href="{{ route('security') }}">
                                <i class="bi bi-shield-check"></i>
                                <span>{{ __('Security') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('help*') ? 'active' : '' }}" href="{{ route('help') }}">
                                <i class="bi bi-question-circle"></i>
                                <span>{{ __('Help & Support') }}</span>
                            </a>
                        </li>

                    @elseif(auth()->user()->hasRole('vendor') || auth()->user()->hasRole('vendor_employee'))
                        <!-- Vendor Menu -->
                        @if($isBranchUser ?? false)
                            <!-- Branch Dashboard -->
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.branch.dashboard') ? 'active' : '' }}" href="{{ route('vendor.branch.dashboard') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>{{ __('Branch Dashboard') }}</span>
                                </a>
                            </li>
                        @elseif(vendorCan('view-dashboard'))
                            <!-- Vendor Dashboard -->
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}">
                                    <i class="bi bi-speedometer2"></i>
                                    <span>{{ __('Vendor Dashboard') }}</span>
                                </a>
                            </li>
                        @endif

                        @if(vendorCan('view-categories') || vendorCan('view-products') || vendorCan('view-branches'))
                            <li class="nav-item mt-3">
                                <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Catalog & Products') }}</small>
                            </li>
                        @endif
                        @if(vendorCan('view-categories'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.categories*') ? 'active' : '' }}" href="{{ route('vendor.categories.index') }}">
                                    <i class="bi bi-grid"></i>
                                    <span>{{ __('Categories') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-variants'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.variants*') ? 'active' : '' }}" href="{{ route('vendor.variants.index') }}">
                                    <i class="bi bi-tags"></i>
                                    <span>{{ __('Variants') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-branches') || vendorCan('manage-branches'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.branches*') ? 'active' : '' }}" href="{{ route('vendor.branches.index') }}">
                                    <i class="bi bi-shop"></i>
                                    <span>{{ __('My Branches') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-products') || vendorCan('manage-products'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.products*') ? 'active' : '' }}" href="{{ route('vendor.products.index') }}">
                                    <i class="bi bi-box-seam"></i>
                                    <span>{{ __('My Products') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.product-ratings*') ? 'active' : '' }}" href="{{ route('vendor.product-ratings.index') }}">
                                    <i class="bi bi-star"></i>
                                    <span>{{ __('Product Ratings') }}</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.product-reports*') ? 'active' : '' }}" href="{{ route('vendor.product-reports.index') }}">
                                    <i class="bi bi-flag"></i>
                                    <span>{{ __('Product Reports') }}</span>
                                </a>
                            </li>
                        @endif
                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Sales & Finance') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.orders*') ? 'active' : '' }}" href="{{ route('vendor.orders.index') }}">
                                <i class="bi bi-cart-check"></i>
                                <span>{{ __('Orders') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.withdrawals*') ? 'active' : '' }}" href="{{ route('vendor.withdrawals.index') }}">
                                <i class="bi bi-cash-coin"></i>
                                <span>{{ __('Withdrawals') }}</span>
                            </a>
                        </li>
                        @if(setting('profit_type') == 'subscription' && vendorCan('view-plans'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.plans*') ? 'active' : '' }}" href="{{ route('vendor.plans.index') }}">
                                    <i class="bi bi-credit-card"></i>
                                    <span>{{ __('Plans') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-subscriptions') || vendorCan('cancel-subscriptions'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.subscriptions*') ? 'active' : '' }}" href="{{ route('vendor.subscriptions.index') }}">
                                    <i class="bi bi-credit-card"></i>
                                    <span>{{ __('Subscriptions') }}</span>
                                </a>
                            </li>
                        @endif

                        @if(vendorCan('view-vendor-users') || vendorCan('manage-vendor-users') || vendorCan('edit-profile') || vendorCan('view-category-requests') || vendorCan('view-variant-requests') || canCreateProducts())
                            <li class="nav-item mt-3">
                                <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Customers & Account') }}</small>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.customers*') ? 'active' : '' }}" href="{{ route('vendor.customers.index') }}">
                                <i class="bi bi-people"></i>
                                <span>{{ __('Customers') }}</span>
                            </a>
                        </li>
                        @if(vendorCan('view-vendor-users') || vendorCan('manage-vendor-users'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.vendor-users*') ? 'active' : '' }}" href="{{ route('vendor.vendor-users.index') }}">
                                    <i class="bi bi-people"></i>
                                    <span>{{ __('Vendor Users') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(canCreateProducts())
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.settings*') ? 'active' : '' }}" href="{{ route('vendor.settings.index') }}">
                                    <i class="bi bi-gear"></i>
                                    <span>{{ __('Settings') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('edit-profile'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('profile*') ? 'active' : '' }}" href="{{ route('profile') }}">
                                    <i class="bi bi-person"></i>
                                    <span>{{ __('Profile') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-category-requests') || vendorCan('create-category-requests') || vendorCan('view-variant-requests') || vendorCan('create-variant-requests') || vendorCan('view-tickets') || vendorCan('manage-tickets'))
                            <li class="nav-item mt-3">
                                <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Requests & Support') }}</small>
                            </li>
                        @endif
                        @if(vendorCan('view-category-requests') || vendorCan('create-category-requests'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.category-requests*') ? 'active' : '' }}" href="{{ route('vendor.category-requests.index') }}">
                                    <i class="bi bi-inbox"></i>
                                    <span>{{ __('Category Requests') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-variant-requests') || vendorCan('create-variant-requests'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.variant-requests*') ? 'active' : '' }}" href="{{ route('vendor.variant-requests.index') }}">
                                    <i class="bi bi-inbox"></i>
                                    <span>{{ __('Variant Requests') }}</span>
                                </a>
                            </li>
                        @endif
                        @if(vendorCan('view-tickets') || vendorCan('manage-tickets'))
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('vendor.tickets*') ? 'active' : '' }}" href="{{ route('vendor.tickets.index') }}">
                                    <i class="bi bi-inbox"></i>
                                    <span>{{ __('Tickets') }}</span>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item mt-3">
                            <small class="text-muted px-3 text-uppercase fw-bold">{{ __('Analytics') }}</small>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.reports*') ? 'active' : '' }}" href="{{ route('vendor.reports.index') }}">
                                <i class="bi bi-graph-up"></i>
                                <span>{{ __('Reports & Analytics') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.reports.earnings') ? 'active' : '' }}" href="{{ route('vendor.reports.earnings') }}">
                                <i class="bi bi-wallet2"></i>
                                <span>{{ __('Earnings Dashboard') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.reports.vendor-performance') ? 'active' : '' }}" href="{{ route('vendor.reports.vendor-performance') }}">
                                <i class="bi bi-person-lines-fill"></i>
                                <span>{{ __('Vendor Performance') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('vendor.reports.product-performance') ? 'active' : '' }}" href="{{ route('vendor.reports.product-performance') }}">
                                <i class="bi bi-bar-chart-line"></i>
                                <span>{{ __('Product Performance') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('help*') ? 'active' : '' }}" href="{{ route('help') }}">
                                <i class="bi bi-question-circle"></i>
                                <span>{{ __('Help & Support') }}</span>
                            </a>
                        </li>

                    @else
                        <!-- Default Menu (for users without specific role) -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('profile*') ? 'active' : '' }}" href="{{ route('profile') }}">
                                <i class="bi bi-person"></i>
                                <span>{{ __('Profile') }}</span>
                            </a>
                        </li>
                    @endif
                @endauth
            </ul>
        </nav>
    </div>
</aside>
