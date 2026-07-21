<style>
/* Custom style overrides to merge dual sidebar into a single standard 270px wide sidebar */

/* 1. Global changes for both Desktop and Mobile */
.side-mini-panel .mini-nav {
    display: none !important;
}
.side-mini-panel {
    width: 270px !important;
}
.side-mini-panel .sidebarmenu {
    left: 0 !important;
    width: 270px !important;
    overflow-y: auto !important;
    overflow-x: hidden !important; /* Force hide horizontal scroll */
    height: calc(100vh - 72px) !important;
}

/* Custom scrollbar for sidebar menu - small and clean */
.sidebarmenu::-webkit-scrollbar {
    width: 5px !important;
    height: 5px !important;
}
.sidebarmenu::-webkit-scrollbar-track {
    background: transparent !important;
}
.sidebarmenu::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.12) !important;
    border-radius: 10px !important;
}
.sidebarmenu::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.25) !important;
}
[data-bs-theme="dark"] .sidebarmenu::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15) !important;
}

.side-mini-panel .sidebarmenu .sidebar-nav {
    overflow-x: hidden !important; /* Force hide horizontal scroll */
}
.side-mini-panel .nav-logo {
    position: relative !important;
    left: 0 !important;
    width: 270px !important;
    display: flex !important;
    height: 72px !important;
    padding: 0 24px !important;
}
.side-mini-panel .sidebarmenu nav.sidebar-nav {
    position: relative !important;
    display: block !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: auto !important;
    padding: 10px 20px !important;
}
.side-mini-panel .sidebarmenu nav.sidebar-nav ul.sidebar-menu {
    padding-bottom: 10px !important;
}
/* Smooth collapse transition for submenus */
.sidebar-nav ul .sidebar-item .first-level {
    display: block !important;
    max-height: 0 !important;
    overflow: hidden !important;
    opacity: 0 !important;
    transition: max-height 0.28s cubic-bezier(0.4, 0, 0.2, 1), 
                opacity 0.28s cubic-bezier(0.4, 0, 0.2, 1), 
                padding 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
.sidebar-nav ul .sidebar-item .first-level.in {
    max-height: 600px !important;
    opacity: 1 !important;
    padding-top: 8px !important;
    padding-bottom: 8px !important;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), 
                opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1), 
                padding 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

/* Force hide SimpleBar horizontal track */
.simplebar-track.simplebar-horizontal {
    display: none !important;
    visibility: hidden !important;
}

/* 2. Desktop specific layout spacing updates */
@media (min-width: 1200px) {
    /* Adjust page-wrapper left margin with correct specificity */
    html[data-layout="vertical"] .page-wrapper {
        margin-left: 270px !important;
    }
    html[data-layout="vertical"] .topbar {
        left: 270px !important;
        width: calc(100% - 270px) !important;
    }

    /* Handle collapsed sidebar mode (mini-sidebar) on desktop */
    html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .side-mini-panel {
        width: 0 !important;
        left: -270px !important;
    }
    html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .page-wrapper {
        margin-left: 0 !important;
    }
    html[data-layout="vertical"] body[data-sidebartype="mini-sidebar"] .topbar {
        left: 0 !important;
        width: 100% !important;
    }
}

/* 3. Mobile specific adjustments */
@media (max-width: 1199.98px) {
    .side-mini-panel {
        left: -270px !important;
    }
    .show-sidebar .side-mini-panel {
        left: 0 !important;
    }
    html[data-layout="vertical"] .page-wrapper {
        margin-left: 0 !important;
    }
    html[data-layout="vertical"] .topbar {
        left: 0 !important;
        width: 100% !important;
    }
}
</style>

  <aside class="side-mini-panel with-vertical">
      <!-- ---------------------------------- -->
      <!-- Start Vertical Layout Sidebar -->
      <!-- ---------------------------------- -->
      <div class="iconbar">
          <div>
              <div class="mini-nav">
                  <div class="brand-logo d-flex align-items-center justify-content-center">
                      <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                          <iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-7"></iconify-icon>
                      </a>
                  </div>
                  <ul class="mini-nav-ul" data-simplebar="">
                      <!-- Icon 1: E-Commerce -->
                      <li class="mini-nav-item" id="mini-1">
                          <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                              data-bs-placement="right" data-bs-title="{{ __('admin.sidebar.ecommerce') }}">
                              <iconify-icon icon="solar:cart-large-4-line-duotone" class="fs-7"></iconify-icon>
                          </a>
                      </li>

                      <!-- Icon 2: Landingpage -->
                      <li class="mini-nav-item" id="mini-2">
                          <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                              data-bs-placement="right" data-bs-title="{{ __('admin.sidebar.landingpage') }}">
                              <iconify-icon icon="solar:notes-line-duotone" class="fs-7"></iconify-icon>
                          </a>
                      </li>

                      <!-- Icon 3: Website khác -->
                      <li class="mini-nav-item" id="mini-3">
                          <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                              data-bs-placement="right" data-bs-title="{{ __('admin.sidebar.other_web') }}">
                              <iconify-icon icon="solar:layers-line-duotone" class="fs-7"></iconify-icon>
                          </a>
                      </li>

                      <!-- Icon 4: Addon -->
                      <li class="mini-nav-item" id="mini-4">
                          <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                              data-bs-placement="right" data-bs-title="{{ __('admin.sidebar.addon') }}">
                              <iconify-icon icon="solar:archive-line-duotone" class="fs-7"></iconify-icon>
                          </a>
                      </li>

                      <!-- Icon 5: Settings -->
                      <li class="mini-nav-item" id="mini-5">
                          <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-custom-class="custom-tooltip"
                              data-bs-placement="right" data-bs-title="{{ __('admin.sidebar.settings') }}">
                              <iconify-icon icon="solar:settings-line-duotone" class="fs-7"></iconify-icon>
                          </a>
                      </li>
                  </ul>
              </div>
              <div class="sidebarmenu">
                  <div class="brand-logo d-flex align-items-center nav-logo">
                      <a href="{{ route('admin.dashboard') }}" class="text-nowrap logo-img">
                          <img src="{{ asset('admin-assets/images/logos/favicon.png') }}" width="35" height="35" alt="Logo">
                          <span class="fs-4 fw-bold text-dark ms-2">E-Com Core</span>
                      </a>
                  </div>

                  <!-- ---------------------------------- -->
                  <!-- Icon 1: E-Commerce Panel -->
                  <!-- ---------------------------------- -->
                  <nav class="sidebar-nav" id="menu-right-mini-1" data-simplebar="">
                      <ul class="sidebar-menu" id="sidebarnav">
                          <li class="nav-small-cap">
                              <span class="hide-menu">E-Commerce</span>
                          </li>
                          
                          <!-- Trang quản trị -->
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.dashboard') }}" id="get-url" aria-expanded="false">
                                  <iconify-icon icon="solar:chart-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.dashboard') }}</span>
                              </a>
                          </li>

                          <!-- Đơn hàng -->
                          @if(app(\App\Support\FeatureGate::class)->enabled('catalog'))
                              @can('manage_orders')
                              <li class="sidebar-item">
                                  <a class="sidebar-link" href="{{ route('admin.orders.index') }}" aria-expanded="false">
                                      <iconify-icon icon="solar:bill-list-line-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.orders') }}</span>
                                  </a>
                              </li>
                              @endcan
                          @endif

                          <!-- Sản phẩm (Products, Categories, Brands) -->
                          @if(app(\App\Support\FeatureGate::class)->enabled('catalog'))
                              @can('manage_products')
                              <li class="sidebar-item">
                                  <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                                      <iconify-icon icon="solar:cart-3-line-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.products') }}</span>
                                  </a>
                                  <ul aria-expanded="false" class="collapse first-level">
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.products.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.product_list') }}
                                          </a>
                                      </li>
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.categories.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.product_categories') }}
                                          </a>
                                      </li>
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.brands.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.brands') }}
                                          </a>
                                      </li>
                                  </ul>
                              </li>
                              @endcan
                          @endif

                          <!-- Đánh giá bình luận -->
                          @if(app(\App\Support\FeatureGate::class)->enabled('review'))
                              @can('manage_reviews')
                              <li class="sidebar-item">
                                  <a class="sidebar-link" href="{{ route('admin.reviews.index') }}" aria-expanded="false">
                                      <iconify-icon icon="solar:chat-round-line-line-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.reviews') }}</span>
                                  </a>
                              </li>
                              @endcan
                          @endif

                          <!-- Mã giảm giá -->
                          @if(app(\App\Support\FeatureGate::class)->enabled('voucher'))
                              @can('manage_vouchers')
                              <li class="sidebar-item">
                                  <a class="sidebar-link" href="{{ route('admin.vouchers.index') }}" aria-expanded="false">
                                      <iconify-icon icon="solar:ticket-sale-line-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.vouchers') }}</span>
                                  </a>
                              </li>
                              @endcan
                          @endif

                          <!-- Quản lý Banner -->
                          @if(app(\App\Support\FeatureGate::class)->enabled('banner'))
                              @can('manage_banners')
                              <li class="sidebar-item">
                                  <a class="sidebar-link" href="{{ route('admin.banners.index') }}" aria-expanded="false">
                                      <iconify-icon icon="solar:gallery-bold-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.banners') }}</span>
                                  </a>
                              </li>
                              @endcan
                          @endif

                          <!-- Trang tĩnh (Custom Pages) -->
                          @can('viewAny', \App\Models\CustomPage::class)
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('pagebuilder.pages.index', ['locale' => app()->getLocale()]) }}" aria-expanded="false">
                                  <iconify-icon icon="solar:document-bold-duotone"></iconify-icon>
                                  <span class="hide-menu">Quản lý Trang Tĩnh</span>
                              </a>
                          </li>
                          @endcan

                          <!-- Quản lý người dùng -->
                          @if(auth()->check() && (auth()->user()->isSuperAdmin() || app(\App\Support\FeatureGate::class)->enabled('multi_admin')))
                               @if(auth()->user()->can('manage_users') || auth()->user()->isSuperAdmin())
                               <li class="sidebar-item">
                                  <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                                      <iconify-icon icon="solar:shield-user-line-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.user_management') }}</span>
                                  </a>
                                  <ul aria-expanded="false" class="collapse first-level">
                                      @can('manage_users')
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.users.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.user_list') }}
                                          </a>
                                      </li>
                                      @endcan
                                      @if(auth()->user()->isSuperAdmin())
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.roles.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.roles_permissions') }}
                                          </a>
                                      </li>
                                      @endif
                                  </ul>
                              </li>
                              @endif
                          @endif


                          {{-- <!-- Hóa đơn của tôi -->
                          @can('manage_settings')
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.invoices.index') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:receipt-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.my_invoices') }}</span>
                              </a>
                          </li>
                          @endcan --}}

                      </ul>
                  </nav>

                  <!-- ---------------------------------- -->
                  <!-- Icon 2: Landing Page Panel -->
                  <!-- ---------------------------------- -->
                  <nav class="sidebar-nav" id="menu-right-mini-2" style="display: none;" data-simplebar="">
                      <ul class="sidebar-menu" id="sidebarnav">
                          <li class="nav-small-cap">
                              <span class="hide-menu">Landing Page</span>
                          </li>
                          <!-- Bài viết (Posts, Post Categories) -->
                          @if(app(\App\Support\FeatureGate::class)->enabled('cms_page'))
                              @can('manage_posts')
                              <li class="sidebar-item">
                                  <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                                      <iconify-icon icon="solar:widget-4-line-duotone"></iconify-icon>
                                      <span class="hide-menu">{{ __('admin.sidebar.blog') }}</span>
                                  </a>
                                  <ul aria-expanded="false" class="collapse first-level">
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.posts.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.post_list') }}
                                          </a>
                                      </li>
                                      <li class="sidebar-item">
                                          <a class="sidebar-link" href="{{ route('admin.post-categories.index') }}">
                                              <span class="icon-small"></span>{{ __('admin.sidebar.post_categories') }}
                                          </a>
                                      </li>
                                  </ul>
                              </li>
                              @endcan
                          @endif
                          <!-- Thư viện Media -->
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.media.index') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:gallery-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.menu.media_library') }}</span>
                              </a>
                          </li>
                          <li class="sidebar-item">
                              <a href="#" class="sidebar-link">
                                  <iconify-icon icon="solar:question-circle-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.faq') }}</span>
                              </a>
                          </li>
                      </ul>
                  </nav>

                  <!-- ---------------------------------- -->
                  <!-- Icon 3: Website khác Panel -->
                  <!-- ---------------------------------- -->
                  <nav class="sidebar-nav" id="menu-right-mini-3" style="display: none;" data-simplebar="">
                      <ul class="sidebar-menu" id="sidebarnav">
                          <li class="nav-small-cap">
                              <span class="hide-menu">{{ __('admin.sidebar.other_web') }}</span>
                          </li>
                          <li class="sidebar-item">
                              <a href="#" class="sidebar-link">
                                  <iconify-icon icon="solar:cardholder-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.widgets') }}</span>
                              </a>
                          </li>
                          <li class="sidebar-item">
                              <a href="#" class="sidebar-link">
                                  <iconify-icon icon="solar:chart-square-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.charts') }}</span>
                              </a>
                          </li>
                          <li class="sidebar-item">
                              <a href="#" class="sidebar-link">
                                  <iconify-icon icon="solar:widget-6-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.ui_components') }}</span>
                              </a>
                          </li>
                      </ul>
                  </nav>

                  <!-- ---------------------------------- -->
                  <!-- Icon 4: Addon Panel -->
                  <!-- ---------------------------------- -->
                  <nav class="sidebar-nav" id="menu-right-mini-4" style="display: none;" data-simplebar="">
                      <ul class="sidebar-menu" id="sidebarnav">
                          <li class="nav-small-cap">
                              <span class="hide-menu">Cửa hàng tính năng</span>
                          </li>
                          @can('manage_settings')
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.addons.index') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:archive-line-duotone"></iconify-icon>
                                  <span class="hide-menu">Cửa hàng Addons</span>
                              </a>
                          </li>
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.addons.invoices') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:receipt-line-duotone"></iconify-icon>
                                  <span class="hide-menu">Hóa đơn mua Addons</span>
                              </a>
                          </li>
                          @if(auth()->check() && auth()->user()->isSuperAdmin())
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.addons.manage') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                                  <span class="hide-menu">Quản lý Addons</span>
                              </a>
                          </li>
                          @endif
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ url('/api/docs') }}" target="_blank" aria-expanded="false">
                                  <iconify-icon icon="solar:document-text-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.api_docs') }}</span>
                              </a>
                          </li>
                          @endcan
                      </ul>
                  </nav>

                  <!-- ---------------------------------- -->
                  <!-- Icon 5: Cấu hình Hệ thống & Kênh bán hàng Panel -->
                  <!-- ---------------------------------- -->
                  <nav class="sidebar-nav" id="menu-right-mini-5" style="display: none;" data-simplebar="">
                      <ul class="sidebar-menu" id="sidebarnav">
                          <li class="nav-small-cap">
                              <span class="hide-menu">{{ __('admin.sidebar.settings') }}</span>
                          </li>
                          @can('manage_settings')
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.settings.index') }}#general-pane" aria-expanded="false">
                                  <iconify-icon icon="solar:settings-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.general_settings') }}</span>
                              </a>
                          </li>
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.shipping-partners.index') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:delivery-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.shipping_settings') }}</span>
                              </a>
                          </li>
                          <li class="sidebar-item">
                               <a class="sidebar-link" href="{{ route('admin.payment-methods.index') }}" aria-expanded="false">
                                   <iconify-icon icon="solar:card-recive-line-duotone"></iconify-icon>
                                   <span class="hide-menu">{{ __('admin.sidebar.payment_settings') }}</span>
                               </a>
                           </li>
                           <li class="sidebar-item">
                               <a class="sidebar-link" href="{{ route('admin.notification-settings.index') }}" aria-expanded="false">
                                   <iconify-icon icon="solar:bell-bing-line-duotone"></iconify-icon>
                                   <span class="hide-menu">{{ __('admin.sidebar.notification_settings') }}</span>
                               </a>
                           </li>
                          @endcan
                          @if(auth()->check() && auth()->user()->isSuperAdmin())
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.features.index') }}" aria-expanded="false">
                                  <iconify-icon icon="solar:widget-add-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.feature_settings') }}</span>
                              </a>
                          </li>
                          @endif
                          @can('manage_settings')
                          <li class="sidebar-item">
                              <a class="sidebar-link" href="{{ route('admin.settings.index') }}#social-pane" aria-expanded="false">
                                  <iconify-icon icon="solar:share-circle-line-duotone"></iconify-icon>
                                  <span class="hide-menu">{{ __('admin.sidebar.sales_channels') }}</span>
                              </a>
                          </li>
                          @endcan
                      </ul>
                  </nav>
              </div>
          </div>
      </div>
  </aside>