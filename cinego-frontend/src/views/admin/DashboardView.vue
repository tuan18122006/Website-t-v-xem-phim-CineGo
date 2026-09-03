<template>
  <div class="admin-layout">
    <!-- LEFT SIDEBAR -->
    <aside class="admin-sidebar">
      <div class="sidebar-brand">
        <div class="cinego-logo-box">
          <span class="logo-cine">Cine</span><span class="logo-go">Go</span>
        </div>
        <span class="brand-name">CineGo Admin</span>
      </div>

      <nav class="sidebar-nav">
        <!-- 1. THỐNG KÊ & GIAO DỊCH -->
        <div class="nav-dropdown">
          <button class="nav-link dropdown-toggle" :class="{ active: statMenuOpen }" @click="statMenuOpen = !statMenuOpen">
            <div class="dropdown-left"><span class="nav-icon"><BarChart3 :size="20" /></span><span>Thống Kê & Báo Cáo</span></div>
            <span class="dropdown-arrow" :class="{ open: statMenuOpen }">▼</span>
          </button>
          <transition name="dropdown">
            <div v-show="statMenuOpen" class="dropdown-content">
              <button class="sub-nav-link" :class="{ active: activeTab === 'stats' }" @click="activeTab = 'stats'"><BarChart3 :size="15" style="vertical-align:-2px" /> Dashboard</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'orders' }" @click="activeTab = 'orders'"><Receipt :size="15" style="vertical-align:-2px" /> Danh sách đơn hàng</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'lookup' }" @click="activeTab = 'lookup'"><Search :size="15" style="vertical-align:-2px" /> Tra cứu & Hỗ trợ</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'scan' }" @click="activeTab = 'scan'"><Camera :size="15" style="vertical-align:-2px" /> Quét mã QR / Soát vé</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'payment_settings' }" @click="activeTab = 'payment_settings'"><Settings :size="15" style="vertical-align:-2px" /> Cấu hình thanh toán</button>
            </div>
          </transition>
        </div>

        <!-- 2. QUẢN LÝ PHIM -->
        <div class="nav-dropdown">
          <button class="nav-link dropdown-toggle" :class="{ active: movieMenuOpen }" @click="movieMenuOpen = !movieMenuOpen">
            <div class="dropdown-left"><span class="nav-icon"><Clapperboard :size="20" /></span><span>Quản Lý Phim</span></div>
            <span class="dropdown-arrow" :class="{ open: movieMenuOpen }">▼</span>
          </button>
          <transition name="dropdown">
            <div v-show="movieMenuOpen" class="dropdown-content">
              <button class="sub-nav-link" :class="{ active: activeTab === 'movies' }" @click="activeTab = 'movies'"><Clapperboard :size="15" style="vertical-align:-2px" /> Danh sách phim</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'genres' }" @click="activeTab = 'genres'"><Tag :size="15" style="vertical-align:-2px" /> Quản lý thể loại</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'actors' }" @click="activeTab = 'actors'"><Drama :size="15" style="vertical-align:-2px" /> Quản lý diễn viên</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'banners' }" @click="activeTab = 'banners'"><Image :size="15" style="vertical-align:-2px" /> Quản lý Banner</button>
            </div>
          </transition>
        </div>

        <!-- 3. LỊCH CHIẾU & GIÁ VÉ -->
        <div class="nav-dropdown">
          <button class="nav-link dropdown-toggle" :class="{ active: pricingMenuOpen }" @click="pricingMenuOpen = !pricingMenuOpen">
            <div class="dropdown-left"><span class="nav-icon"><Clock :size="20" /></span><span>Lịch Chiếu & Giá Vé</span></div>
            <span class="dropdown-arrow" :class="{ open: pricingMenuOpen }">▼</span>
          </button>
          <transition name="dropdown">
            <div v-show="pricingMenuOpen" class="dropdown-content">
              <button class="sub-nav-link" :class="{ active: activeTab === 'showtimes' }" @click="activeTab = 'showtimes'"><Clock :size="15" style="vertical-align:-2px" /> Lịch chiếu phim</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'pricing' }" @click="activeTab = 'pricing'"><CircleDollarSign :size="15" style="vertical-align:-2px" /> Cấu hình giá</button>
            </div>
          </transition>
        </div>

        <!-- 4. TÀI KHOẢN & VẬN HÀNH -->
        <div class="nav-dropdown">
          <button class="nav-link dropdown-toggle" :class="{ active: userMenuOpen }" @click="userMenuOpen = !userMenuOpen">
            <div class="dropdown-left"><span class="nav-icon"><Users :size="20" /></span><span>Tài Khoản & Vận Hành</span></div>
            <span class="dropdown-arrow" :class="{ open: userMenuOpen }">▼</span>
          </button>
          <transition name="dropdown">
            <div v-show="userMenuOpen" class="dropdown-content">
              <button class="sub-nav-link" :class="{ active: activeTab === 'users' }" @click="activeTab = 'users'"><Users :size="15" style="vertical-align:-2px" /> Quản lý tài khoản</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'loyalty' }" @click="activeTab = 'loyalty'"><Star :size="15" style="vertical-align:-2px" /> Tích điểm khách hàng</button>
              <button class="sub-nav-link" :class="{ active: activeTab === 'monitor' }" @click="activeTab = 'monitor'">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-monitor" style="vertical-align:-2px"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg> Giám sát vận hành
              </button>
            </div>
          </transition>
        </div>

        <!-- CÁC CHỨC NĂNG CÒN LẠI -->
        <button class="nav-link" :class="{ active: activeTab === 'rooms' }" @click="activeTab = 'rooms'">
          <span class="nav-icon"><Building2 :size="20" /></span>
          <span>Phòng Chiếu & Ghế</span>
        </button>

        <button class="nav-link" :class="{ active: activeTab === 'combos' }" @click="activeTab = 'combos'">
          <span class="nav-icon"><Popcorn :size="20" /></span>
          <span>Đồ Ăn & Combo</span>
        </button>
         
        <button class="nav-link" :class="{ active: activeTab === 'vouchers' }" @click="activeTab = 'vouchers'">
          <span class="nav-icon"><Ticket :size="20" /></span>
          <span>Mã Giảm Giá</span>
        </button>

        <button class="nav-link" :class="{ active: activeTab === 'wallet' }" @click="activeTab = 'wallet'">
          <span class="nav-icon"><CircleDollarSign :size="20" /></span>
          <span>Duyệt Rút Tiền Ví</span>
        </button>

        <button class="nav-link" :class="{ active: activeTab === 'reviews' }" @click="activeTab = 'reviews'">
          <span class="nav-icon"><Star :size="20" /></span>
          <span>Đánh Giá Khách Hàng</span>
        </button>

        <button class="nav-link" :class="{ active: activeTab === 'action_logs' }" @click="activeTab = 'action_logs'">
          <span class="nav-icon"><History :size="20" /></span>
          <span>Lịch Sử Hoạt Động</span>
        </button>

        <!-- BLOG DROPDOWN -->
        <div class="nav-dropdown">
          <button
            class="nav-link dropdown-toggle"
            :class="{
              active: activeTab === 'blogs' || activeTab === 'blog-categories' || activeTab === 'articles',
            }"
            @click="blogMenuOpen = !blogMenuOpen"
          >
            <div class="dropdown-left">
              <span class="nav-icon"><Newspaper :size="20" /></span>
              <span>Quản Lý Blog</span>
            </div>

            <span class="dropdown-arrow" :class="{ open: blogMenuOpen }">
              ▼
            </span>
          </button>

          <transition name="dropdown">
            <div v-show="blogMenuOpen" class="dropdown-content">
              <button
                class="sub-nav-link"
                :class="{ active: activeTab === 'articles' }"
                @click="activeTab = 'articles'"
              >
                <Newspaper :size="15" style="vertical-align:-2px" /> QL Trang Top Phim
              </button>

              <button
                class="sub-nav-link"
                :class="{ active: activeTab === 'blog-categories' }"
                @click="activeTab = 'blog-categories'"
              >
                <Folder :size="15" style="vertical-align:-2px" /> Thể Loại Blog
              </button>

              <button
                class="sub-nav-link"
                :class="{ active: activeTab === 'blogs' }"
                @click="activeTab = 'blogs'"
              >
                <PenLine :size="15" style="vertical-align:-2px" /> Danh sách Blog
              </button>
            </div>
          </transition>
        </div>

        <button class="nav-link" :class="{ active: activeTab === 'trash' }" @click="activeTab = 'trash'">
          <span class="nav-icon"><Trash2 :size="20" /></span>
          <span>Thùng Rác</span>
        </button>

      </nav>

      <div class="sidebar-footer">
        <div class="admin-info">
          <p class="admin-name">{{ authStore.user?.name || 'Quản trị viên' }}</p>
          <p class="admin-role">Hệ Thống CineGo</p>
        </div>
        <button @click="handleLogout" class="btn-logout-sidebar"><LogOut :size="15" style="vertical-align:-2px" /> Đăng xuất</button>
      </div>
    </aside>

    <!-- RIGHT MAIN CONTENT AREA -->
    <main class="admin-main-content">
      <!-- HEADER ROW -->
      <header class="content-header">
        <div>
          <h1 class="header-title">{{ getTabTitle }}</h1>
          <p class="header-desc">{{ getTabDesc }}</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
          <!-- Notification Dropdown -->
          <div class="notification-wrapper" @click.stop="showNotiDropdown = !showNotiDropdown">
            <div class="bell-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              <span v-if="unreadNotiCount > 0" class="badge"></span>
            </div>
            
            <div v-if="showNotiDropdown" class="notification-dropdown" @click.stop>
              <div class="notif-header">
                <strong>Thông báo</strong>
                <button v-if="unreadNotiCount > 0" class="notif-read-all" @click="markAllAsRead">Đánh dấu đã đọc tất cả</button>
              </div>
              <div class="notif-list">
                <div v-if="adminNotifications.length === 0" class="notif-empty">
                  Chưa có thông báo nào.
                </div>
                <div v-for="notif in adminNotifications" :key="notif.id" 
                     class="notif-item" 
                     :class="{'unread': notif.read_at === null}"
                     @click="markNotiAsRead(notif.id)">
                  <div class="notif-icon">
                    <Receipt v-if="notif.data.type === 'qr_payment_pending'" :size="20" />
                    <Bell v-else :size="20" />
                  </div>
                  <div class="notif-content">
                    <p class="notif-message">{{ notif.data.message }}</p>
                    <span class="notif-time">{{ new Date(notif.created_at).toLocaleString('vi-VN') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <router-link to="/" class="btn-back-client"><Eye :size="15" style="vertical-align:-2px" /> Xem Client Website</router-link>
        </div>
      </header>

      <!-- TAB 1: DASHBOARD STATS & CHARTS -->
      <div v-show="activeTab === 'stats'" class="dashboard-tab-content">
        
        <!-- Date Range Filter -->
        <div class="date-filter-panel glass-panel">
          <div class="filter-group">
            <label>Từ ngày:</label>
            <input type="date" v-model="startDate" class="date-input" />
          </div>
          <div class="filter-group">
            <label>Đến ngày:</label>
            <input type="date" v-model="endDate" class="date-input" />
          </div>
          <button @click="applyDateFilter" class="btn-filter" :disabled="statsLoading">
            <span v-if="statsLoading"><Hourglass :size="15" style="vertical-align:-2px" /></span>
            <span v-else><Search :size="15" style="vertical-align:-2px" /> Lọc Dữ Liệu</span>
          </button>
        </div>

        <!-- Widgets thông số THẬT -->
        <div class="stats-widgets" style="margin-bottom: 30px;">
          <div class="widget-card grad-pink" :class="{ 'flash-live': isLiveUpdated }">
            <div class="widget-icon"><CircleDollarSign :size="20" /></div>
            <div class="widget-info">
              <span class="widget-label">Tổng Doanh Thu 
                <span v-if="isLiveUpdated" class="live-badge">Live</span>
              </span>
              <span class="widget-value">{{ formatCurrency(totalRevenue) }}</span>
              <span class="widget-trend">Từ các đơn đã thanh toán</span>
            </div>
          </div>

          <div class="widget-card grad-violet">
            <div class="widget-icon"><Ticket :size="20" /></div>
            <div class="widget-info">
              <span class="widget-label">Vé Đã Bán</span>
              <span class="widget-value">{{ totalTickets.toLocaleString('vi-VN') }}</span>
              <span class="widget-trend">Tổng số vé xuất thành công</span>
            </div>
          </div>

          <div class="widget-card grad-orange">
            <div class="widget-icon"><Popcorn :size="20" /></div>
            <div class="widget-info">
              <span class="widget-label">Bắp Nước Đã Bán</span>
              <span class="widget-value">{{ totalCombos.toLocaleString('vi-VN') }}</span>
              <span class="widget-trend">Tổng số combo bắp &amp; nước</span>
            </div>
          </div>

          <div class="widget-card grad-blue">
            <div class="widget-icon"><Clock :size="20" /></div>
            <div class="widget-info">
              <span class="widget-label">Suất Chiếu Hôm Nay</span>
              <span class="widget-value">{{ todayShowtimes }}</span>
              <span class="widget-trend">Lịch chiếu trong ngày</span>
            </div>
          </div>
        </div>

        <!-- Biểu đồ phân tích doanh thu & lấp đầy -->
        <div class="reports-grid" style="margin-bottom: 30px; grid-template-columns: 1fr 1fr;">
          <div class="report-card glass-panel">
            <h3 class="card-title">Phân Bổ Doanh Thu</h3>
            <div class="donut-chart-container">
              <svg viewBox="0 0 36 36" class="donut-svg">
                <path
                  class="circle-bg"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                />
                <path
                  class="circle-ticket"
                  :stroke-dasharray="totalRevenue > 0 ? `${(ticketRevenue / totalRevenue) * 100}, 100` : '0, 100'"
                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                />
                <text x="18" y="19" class="donut-text">{{ totalRevenue > 0 ? Math.round((ticketRevenue / totalRevenue) * 100) : 0 }}%</text>
                <text x="18" y="24" class="donut-subtext">Từ Vé</text>
              </svg>
              <div class="donut-legends">
                <div class="legend-item"><span class="color-box bg-cinema-red"></span> Vé ({{ formatCurrency(ticketRevenue) }})</div>
                <div class="legend-item"><span class="color-box bg-gray"></span> Bắp Nước ({{ formatCurrency(comboRevenue) }})</div>
              </div>
            </div>
          </div>

          <div class="report-card glass-panel">
            <h3 class="card-title">Tỷ Lệ Lấp Đầy Rạp Hôm Nay</h3>
            <div class="occupancy-container">
              <div class="occupancy-value" :class="{ 'text-red': todayOccupancyRate < 30 }">
                {{ todayOccupancyRate }}%
              </div>
              <div class="progress-bar-bg">
                <div class="progress-bar-fill" :style="{ width: `${todayOccupancyRate}%`, background: todayOccupancyRate < 30 ? 'var(--accent-pink)' : 'var(--accent-mint)' }"></div>
              </div>
              <p class="occupancy-desc">
                {{ todayOccupancyRate < 30 ? 'Báo động đỏ: Cần đẩy mạnh Voucher khuyến mãi!' : 'Trạng thái hoạt động ổn định.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Biểu đồ doanh thu dạng SVG -->
        <div class="reports-grid" style="grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px;">
          <div class="report-card glass-panel">
            <div class="chart-head">
              <h3 class="card-title">Doanh Thu Theo {{ revenuePeriod === 'day' ? '7 Ngày Qua' : '6 Tháng Qua' }} (VNĐ)</h3>
              <div class="period-toggle">
                <button :class="{ active: revenuePeriod === 'day' }" @click="revenuePeriod = 'day'">Ngày</button>
                <button :class="{ active: revenuePeriod === 'month' }" @click="revenuePeriod = 'month'">Tháng</button>
              </div>
            </div>

            <p class="chart-total">
              Tổng: <strong>{{ formatCurrency(revenueTotal) }}</strong>
            </p>

            <div class="chart-container">
              <svg :viewBox="`0 0 ${chart.W} ${chart.H}`" class="svg-chart">
                <defs>
                  <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--accent-pink)" stop-opacity="0.25" />
                    <stop offset="100%" stop-color="var(--accent-violet)" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <!-- Lưới ngang + nhãn trục Y -->
                <g v-for="(t, i) in chart.yTicks" :key="'y' + i">
                  <line x1="55" :y1="t.y" x2="580" :y2="t.y" stroke="rgba(0,0,0,0.06)" stroke-width="1" />
                  <text x="48" :y="t.y + 3" fill="var(--text-muted)" font-size="10" text-anchor="end">
                    {{ compactVND(t.val) }}
                  </text>
                </g>

                <!-- Vùng tô + đường doanh thu -->
                <path v-if="chart.area" :d="chart.area" fill="url(#chartGrad)" />
                <path
                  v-if="chart.line"
                  :d="chart.line"
                  fill="none"
                  stroke="var(--accent-pink)"
                  stroke-width="3"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />

                <!-- Điểm + nhãn trục X -->
                <g v-for="(p, i) in chart.points" :key="'p' + i">
                  <circle :cx="p.x" :cy="p.y" r="4.5" fill="var(--accent-pink)" stroke="#fff" stroke-width="1.5">
                    <title>{{ p.label }}: {{ formatCurrency(p.revenue) }}</title>
                  </circle>
                  <text :x="p.x" :y="chart.baseY + 18" fill="var(--text-secondary)" font-size="10" text-anchor="middle">
                    {{ p.label }}
                  </text>
                </g>
              </svg>
            </div>
          </div>

          <div class="report-card glass-panel" style="padding: 24px; display: flex; flexDirection: column; align-items: stretch; border: 1px solid rgba(0,0,0,0.04); box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 15px;">
              <h3 class="card-title" style="margin: 0; font-size: 16px; color: #1e293b;"><Trophy :size="15" style="vertical-align:-2px" /> Top 5 Phim Bán Chạy Nhất</h3>
            </div>
            
            <div>
              <div v-if="topMovies.length" class="movie-ranks-list">
                <div v-for="(m, i) in topMovies" :key="m.id" class="rank-item">
                  <span class="rank-num" :class="['bg-pink', 'bg-violet', 'bg-tertiary'][i] || 'bg-tertiary'">{{ i + 1 }}</span>
                  <div class="rank-info">
                    <h4 :title="m.title">{{ m.title }}</h4>
                    <span class="rank-category">{{ m.genres || 'Chưa phân loại' }}</span>
                  </div>
                  <div class="rank-sales">
                    <span class="sales-value">{{ compactVND(m.revenue) }}</span>
                    <span class="sales-tickets">{{ m.tickets }} vé</span>
                  </div>
                </div>
              </div>
              <div v-else class="ranks-empty">
                <span><Clapperboard :size="20" /></span>
                <p>Chưa có dữ liệu bán vé để xếp hạng.</p>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- TAB 2: DYNAMIC MOVIES CRUD -->
      <div v-if="activeTab === 'movies'">
        <MoviesView />
      </div>

      <!-- TAB: BANNERS -->
      <div v-if="activeTab === 'banners'" class="view-content">
        <BannerManagement />
      </div>

      <!-- TAB: QUẢN LÝ DIỄN VIÊN -->
      <div v-if="activeTab === 'actors'">
        <ActorManagement />
      </div>

        <!-- TAB 3: DYNAMIC SHOWTIMES CRUD -->
        <div v-if="activeTab === 'showtimes'">
          <ShowtimesView />
        </div>
  
        <!-- TAB: CẤU HÌNH GIÁ -->
        <div v-if="activeTab === 'pricing'">
          <PricingConfigView />
        </div>
  
        <!-- TAB: ORDERS MANAGEMENT -->
        <div v-if="activeTab === 'orders'">
          <OrderManagementView />
        </div>
    
        <!-- TAB: BOOKING LOOKUP -->
        <div v-if="activeTab === 'lookup'">
          <BookingLookupView />
        </div>

        <!-- TAB: QUÉT MÃ QR -->
        <div v-if="activeTab === 'scan'">
          <TicketScannerView />
        </div>

        <!-- TAB: PAYMENT SETTINGS -->
        <div v-if="activeTab === 'payment_settings'">
          <PaymentSettingsView />
          <p class="text-muted" style="margin-top: 15px; font-size: 0.9em;">Cấu hình cổng thanh toán và phương thức giao dịch cho hệ thống.</p>
        </div>

      <!-- TAB: QUẢN LÝ RẠP & GHẾ -->
      <div v-if="activeTab === 'rooms'">
        <RoomsView />
      </div>

      <!-- TAB: GIÁM SÁT VẬN HÀNH -->
      <div v-if="activeTab === 'monitor'">
        <ShowtimeMonitorView />
      </div>



      <div v-show="activeTab === 'combos'">
        <ComboSelection />
      </div>

      <div v-show="activeTab === 'vouchers'">
        <VoucherManager />
      </div>

      <div v-show="activeTab === 'reviews'">
        <ReviewManagement />
      </div>

      <div v-if="activeTab === 'action_logs'">
        <ActionLogView />
      </div>

      <div v-show="activeTab === 'genres'">
        <GenreManagement />
      </div>

      <!-- TAB: USER MANAGEMENT -->
      <div v-if="activeTab === 'users'">
        <UserManagement />
      </div>

      <div v-if="activeTab === 'articles'">
        <ArticleManagementView />
      </div>

      <div v-if="activeTab === 'blogs'">
        <BlogList />
      </div>

      <div v-if="activeTab === 'blog-categories'">
        <BlogCategoryList />
      </div>

              <!-- TAB: LOYALTY -->
        <div v-if="activeTab === 'loyalty'" class="dashboard-tab-content" style="background: white; border-radius: 12px; min-height: 500px;">
          <UserLoyaltyManager />
        </div>

        <!-- TAB: DUYỆT RÚT TIỀN VÍ -->
        <div v-if="activeTab === 'wallet'" class="dashboard-tab-content" style="min-height: 500px;">
          <WithdrawalManagerView />
        </div>

        <!-- TAB: THÙNG RÁC -->
        <div v-if="activeTab === 'trash'" class="dashboard-tab-content" style="min-height: 500px;">
          <TrashView />
        </div>


     

    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { BarChart3, Tag, Clapperboard, Drama, Image, Clock, Receipt, Search, Camera, Settings, Building2, Users, Popcorn, Ticket, Star, Newspaper, Folder, PenLine, LogOut, Bell, Eye, CircleDollarSign, Trophy, Hourglass, Trash2, History } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';
import { toast } from '../../utils/alert';
import MoviesView from './MoviesView.vue';
import BannerManagement from './BannerManagement.vue';
import ActorManagement from './ActorManagement.vue';
import ShowtimesView from './ShowtimesView.vue';
import GenreManagement from './GenreManagement.vue';
import UserManagement from './UserManagement.vue';
import RoomsView from './RoomManagementView.vue';
import RoomManagementView from './RoomManagementView.vue';
import RoomEditorView from './RoomEditorView.vue';
import ShowtimeMonitorView from './ShowtimeMonitorView.vue';
import ComboSelection from './ComboSelection.vue'; 
import VoucherManager from './VoucherManager.vue';
import ReviewManagement from './ReviewManagement.vue';
import ActionLogView from './ActionLogView.vue';
import ArticleManagementView from './ArticleManagementView.vue';
import BlogList from "./blog/BlogListView.vue";
import BlogCategoryList from "./blog/BlogCategoryList.vue";
import BookingLookupView from "./BookingLookupView.vue";
import OrderManagementView from "./OrderManagementView.vue";
import TicketScannerView from "./TicketScannerView.vue";
import UserLoyaltyManager from './UserLoyaltyManager.vue';
import WithdrawalManagerView from './WithdrawalManagerView.vue';
import PaymentSettingsView from './PaymentSettingsView.vue';
import TrashView from './TrashView.vue';
import PricingConfigView from './PricingConfigView.vue';

const authStore = useAuthStore();
const router = useRouter();

const adminNotifications = ref([]);
const showNotiDropdown = ref(false);
const unreadNotiCount = computed(() => adminNotifications.value.filter(n => !n.read_at).length);

const fetchAdminNotifications = async () => {
  try {
    const res = await api.get('/notifications');
    adminNotifications.value = res.data.notifications.data || res.data.notifications;
  } catch (err) {
    console.error('Lỗi tải thông báo admin:', err);
  }
};

const markNotiAsRead = async (id) => {
  try {
    await api.post(`/notifications/${id}/read`);
    fetchAdminNotifications();
    showNotiDropdown.value = false;
    activeTab.value = 'orders';
  } catch (err) {
    console.error(err);
  }
};

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all');
    fetchAdminNotifications();
  } catch (err) {
    console.error(err);
  }
};

const isCheckinModalOpen = ref(false);
const isCheckoutModalOpen = ref(false);
const shiftData = ref({
  shift_name: '',
  workstation: '',
  reported_cash: 0,
  reported_transfer: 0
});

const submitCheckin = async () => {
  if (!shiftData.value.shift_name || !shiftData.value.workstation) {
    toast('Vui lòng nhập đầy đủ thông tin ca trực!', 'warning');
    return;
  }
  try {
    const res = await api.post('/staff/shifts/start', {
      shift_name: shiftData.value.shift_name,
      workstation: shiftData.value.workstation
    });
    if (res.data.success) {
      if (authStore.user) authStore.user.work_status = 'on_shift';
      isCheckinModalOpen.value = false;
      toast('Đã bắt đầu ca trực thành công!', 'success');
    }
  } catch (err) {
    toast(err.response?.data?.message || 'Có lỗi xảy ra khi bắt đầu ca.', 'error');
  }
};

const submitCheckout = async () => {
  try {
    const res = await api.post('/staff/shifts/end', {
      reported_cash: shiftData.value.reported_cash,
      reported_transfer: shiftData.value.reported_transfer
    });
    if (res.data.success) {
      if (authStore.user) authStore.user.work_status = 'off_shift';
      isCheckoutModalOpen.value = false;
      toast('Đã gửi yêu cầu chốt ca thành công! Vui lòng đợi quản lý duyệt.', 'success');
    }
  } catch (err) {
    toast(err.response?.data?.message || 'Có lỗi xảy ra khi chốt ca.', 'error');
  }
};

const activeTab = ref(localStorage.getItem('admin_active_tab') || 'stats');

const blogMenuOpen = ref(
  activeTab.value === 'blogs' || activeTab.value === 'blog-categories'
);

const movieMenuOpen = ref(
  ['movies', 'banners', 'actors', 'genres'].includes(activeTab.value)
);

const statMenuOpen = ref(
  ['stats', 'orders', 'lookup', 'scan', 'payment_settings'].includes(activeTab.value)
);

const pricingMenuOpen = ref(
  ['showtimes', 'pricing'].includes(activeTab.value)
);

const userMenuOpen = ref(
  ['users', 'monitor', 'loyalty'].includes(activeTab.value)
);

watch(activeTab, (newVal) => {
  localStorage.setItem('admin_active_tab', newVal);
  if (newVal !== 'blogs' && newVal !== 'blog-categories') {
    blogMenuOpen.value = false;
  }
  if (!['movies', 'banners', 'actors', 'genres'].includes(newVal)) {
    movieMenuOpen.value = false;
  }
  if (!['stats', 'orders', 'lookup', 'scan', 'payment_settings'].includes(newVal)) {
    statMenuOpen.value = false;
  }
  if (!['showtimes', 'pricing'].includes(newVal)) {
    pricingMenuOpen.value = false;
  }
  if (!['users', 'monitor', 'loyalty'].includes(newVal)) {
    userMenuOpen.value = false;
  }
});
const moviesCount = ref(0);
const showtimesCount = ref(0);
const bookings = ref([]);

/* ===== DASHBOARD THỐNG KÊ THẬT ===== */
const statsLoading = ref(false);
const totalRevenue = ref(0);
const ticketRevenue = ref(0);
const comboRevenue = ref(0);
const totalTickets = ref(0);
const totalCombos = ref(0);
const todayShowtimes = ref(0);
const todayOccupancyRate = ref(0);
const topMovies = ref([]);

const isLiveUpdated = ref(false);

const startDate = ref('');
const endDate = ref('');

const revenuePeriod = ref('day'); // 'day' | 'month'
const revenueSeries = ref([]);    // [{ label, revenue }]
const revenueTotal = ref(0);

// Rút gọn tiền tệ cho nhãn: 1500000 -> "1.5 Tr", 2000000000 -> "2 Tỷ"
const compactVND = (val) => {
  const v = Number(val) || 0;
  if (v >= 1e9) return (v / 1e9).toFixed(v % 1e9 === 0 ? 0 : 1) + ' Tỷ';
  if (v >= 1e6) return (v / 1e6).toFixed(v % 1e6 === 0 ? 0 : 1) + ' Tr';
  if (v >= 1e3) return Math.round(v / 1e3) + 'K';
  return String(v);
};

// Dựng dữ liệu vẽ biểu đồ đường SVG từ revenueSeries
const chart = computed(() => {
  const data = revenueSeries.value;
  const W = 600, H = 240, padL = 55, padR = 20, padT = 25, padB = 40;
  const innerW = W - padL - padR;
  const innerH = H - padT - padB;
  const baseY = padT + innerH;
  const n = data.length;
  const max = Math.max(...data.map(d => d.revenue), 1);

  const points = data.map((d, i) => {
    const x = n > 1 ? padL + (innerW / (n - 1)) * i : padL + innerW / 2;
    const y = baseY - (d.revenue / max) * innerH;
    return { x, y, label: d.label, revenue: d.revenue };
  });

  const line = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ');
  const area = points.length
    ? `${line} L ${points[points.length - 1].x.toFixed(1)} ${baseY} L ${points[0].x.toFixed(1)} ${baseY} Z`
    : '';

  const yTicks = [1, 0.5, 0].map(f => ({ y: baseY - f * innerH, val: max * f }));

  return { points, line, area, yTicks, baseY, W, H };
});

const getTabTitle = computed(() => {
  const titles = {
    stats: 'Dashboard Tổng Quan',
    movies: 'Danh Sách Phim',
    actors: 'Quản Lý Diễn Viên',
    banners: 'Quản Lý Banner Trang Chủ',
    showtimes: 'Quản Lý Lịch Chiếu & Lịch Trình',
    rooms: 'Quản Lý Phòng Chiếu & Ghế',
    genres: 'Quản Lý Thể Loại Phim',
    users: 'Quản Lý Tài Khoản & Phân Quyền',
    orders: 'Quản Lý Đơn Hàng',
    lookup: 'Tra Cứu Đơn Hàng & Hỗ Trợ Khách',
    scan: 'Quét mã QR soát vé',
    payment_settings: 'Cấu hình thông tin tài khoản nhận tiền',
    combos: 'Quản Lý Combo và Đồ ăn',
    vouchers: 'Quản Lý Mã Giảm Giá (Vouchers)',
    reviews: 'Kiểm Duyệt Đánh Giá & Bình Luận',
    articles: 'QL Trang Top Phim',
    blogs: 'Quản Lý Blog',
    'blog-categories': 'Quản Lý Thể Loại Blog',
    revenue: 'Báo Cáo & Thống Kê Doanh Thu',
    monitor: 'Giám Sát Vận Hành'
  };
  return titles[activeTab.value];
});



const getTabDesc = computed(() => {
  const descs = {
    stats: 'Xem tổng quan báo cáo doanh thu kinh doanh và biểu đồ tăng trưởng hệ thống CineGo.',
    movies: 'Quản lý phim đang chiếu, sắp chiếu, cấu hình các thể loại phim và hình ảnh poster.',
    actors: 'Thêm, sửa, xóa diễn viên và gán vai diễn vào từng bộ phim.',
    banners: 'Quản lý cấu hình banner trang chủ',
    showtimes: 'Quản lý lịch chiếu các phòng chiếu, kiểm tra phòng và dịch thuật, định dạng 2D/3D.',
    rooms: 'Thiết kế trực quan sơ đồ không gian rạp, quản lý các loại ghế (Thường, VIP, Đôi) và lối đi.',
    genres: 'Quản lý danh mục thể loại phim của hệ thống CineGo.',
    users: 'Thêm, sửa, phân quyền (Admin/Staff/User) và khóa/mở khóa tài khoản người dùng.',
    orders: 'Xem, lọc và tra cứu đơn hàng theo thời gian, phim và loại khách để quản lý bán vé hiệu quả.',
    lookup: 'Tìm đơn theo SĐT/email/mã đơn khi khách quên mã vé, xem ghế & bắp nước đã mua để hỗ trợ.',
    scan: 'Sử dụng camera hoặc nhập mã thủ công để kiểm tra tính hợp lệ của vé và soát vé cho khách.',
    payment_settings: 'Cài đặt mã ngân hàng, số tài khoản để nhận tiền thanh toán mã QR từ khách hàng.',
    combos: 'Thêm, sửa, xóa, combo và đồ ăn kiểm kê số lượng tồn trong kho',
    vouchers: 'Tạo mã giảm giá, giới hạn số lần dùng, thiết lập điều kiện tối thiểu.',
    reviews: 'Xem toàn bộ bình luận, lọc theo sao/phim/từ khóa, ẩn - ghim - phản hồi - xóa bình luận.',
    articles: 'Quản lý các bài viết Top Phim, cấu hình danh sách phim xếp hạng và đánh giá.',
    blogs: 'Quản lý nội dung blog, thêm/sửa/xóa bài viết và quản lý trạng thái hiển thị.',
    'blog-categories': 'Quản lý thể loại blog, thêm/sửa/xóa và quản lý trạng thái hiển thị.',
    revenue: 'Lịch sử giao dịch chi tiết các hóa đơn đặt vé qua ví điện tử của người dùng.',
      loyalty: 'Quản lý tích điểm của khách hàng'
  };
  return descs[activeTab.value];
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const handleLogout = async () => {
  await authStore.logout();
  router.push('/');
};

const fetchOverview = async () => {
  statsLoading.value = true;
  try {
    const params = {};
    if (startDate.value) params.start_date = startDate.value;
    if (endDate.value) params.end_date = endDate.value;
    
    const res = await api.get('/admin/dashboard/overview', { params });
    const d = res.data;
    totalRevenue.value = d.total_revenue;
    ticketRevenue.value = d.ticket_revenue;
    comboRevenue.value = d.combo_revenue;
    totalTickets.value = d.total_tickets;
    totalCombos.value = d.total_combos;
    moviesCount.value = d.movies_count;
    showtimesCount.value = d.today_showtimes;
    todayShowtimes.value = d.today_showtimes;
    todayOccupancyRate.value = d.today_occupancy_rate;
    topMovies.value = d.top_movies || [];
  } catch (err) {
    console.error('Fetch dashboard overview error:', err);
  } finally {
    statsLoading.value = false;
  }
};

const fetchRevenue = async () => {
  try {
    const params = { period: revenuePeriod.value };
    if (startDate.value) params.start_date = startDate.value;
    if (endDate.value) params.end_date = endDate.value;
    
    const res = await api.get('/admin/dashboard/revenue', { params });
    revenueSeries.value = res.data.series || [];
    revenueTotal.value = res.data.total || 0;
  } catch (err) {
    console.error('Fetch revenue chart error:', err);
    revenueSeries.value = [];
    revenueTotal.value = 0;
  }
};

const applyDateFilter = () => {
  if (startDate.value && endDate.value) {
    const start = new Date(startDate.value);
    const end = new Date(endDate.value);
    if (start > end) {
      toast('Ngày bắt đầu không thể lớn hơn ngày kết thúc!', 'warning');
      return;
    }
  }

  fetchOverview();
  fetchRevenue();
};

watch(revenuePeriod, fetchRevenue);

const fetchBookings = () => {
  // Populate realistic booking histories
  bookings.value = [
    { id: 1, booking_code: 'CG-582910', user_email: 'customer@cinego.com', movie_title: 'Avatar: Dòng Chảy Của Nước', total_amount: 215000, payment_method: 'vnpay', payment_status: 'paid', date: '2026-06-02 15:30' },
    { id: 2, booking_code: 'CG-942811', user_email: 'user_momo@gmail.com', movie_title: 'Doctor Strange 2', total_amount: 190000, payment_method: 'vnpay', payment_status: 'paid', date: '2026-06-02 14:15' },
    { id: 3, booking_code: 'CG-374829', user_email: 'alex_dev@gmail.com', movie_title: 'Ốc Mượn Hồn', total_amount: 150000, payment_method: 'momo', payment_status: 'pending', date: '2026-06-02 12:00' },
    { id: 4, booking_code: 'CG-102948', user_email: 'nguyenvan_a@gmail.com', movie_title: 'Ngôi Đền Kỳ Quái 5', total_amount: 230000, payment_method: 'vnpay', payment_status: 'paid', date: '2026-06-01 20:45' }
  ];
};

let adminNotiInterval;

onMounted(() => {
  fetchOverview();
  fetchAdminNotifications();
  adminNotiInterval = setInterval(fetchAdminNotifications, 10000); // Tự động lấy thông báo mỗi 10 giây
  
  fetchRevenue();
  fetchBookings();
  
  if (window.Echo) {
    window.Echo.channel('admin.dashboard')
      .listen('.BookingPaid', (e) => {
        totalRevenue.value += parseFloat(e.total_amount);
        totalTickets.value += parseInt(e.ticket_count);
        totalCombos.value += parseInt(e.combo_count);
        isLiveUpdated.value = true;
        setTimeout(() => isLiveUpdated.value = false, 3000);
      });
  }
});

onUnmounted(() => {
  if (adminNotiInterval) {
    clearInterval(adminNotiInterval);
  }
});
</script>

<style scoped>
.admin-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  min-height: 100vh;
  gap: 30px;
  background-color: #ffffff;
  color: var(--text-primary);
  border-radius: 0;
  overflow: visible;
  box-shadow: none;
}

@media (max-width: 992px) {
  .admin-layout {
    grid-template-columns: 1fr;
  }
}

/* SIDEBAR STYLES */
.admin-sidebar {
  background-color: #fcf8fa;
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  padding: 30px 20px;
  justify-content: space-between;
  /* Cố định Sidebar khi cuộn trang */
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 36px;
}

.cinego-logo-box {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  padding: 6px 10px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
}

.logo-cine {
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
  font-family: var(--font-display);
}

.logo-go {
  color: var(--accent-pink);
  background: #ffffff;
  font-size: 13px;
  font-weight: 800;
  font-family: var(--font-display);
  padding: 0px 3px;
  border-radius: 3px;
}

.brand-name {
  font-size: 15px;
  font-weight: 800;
  color: #1e293b;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.nav-link {
  background: transparent;
  border: none;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 18px;
  color: #475569;
  font-weight: 600;
  font-size: 14px;
  text-align: left;
  border-radius: 8px;
  cursor: pointer;
  width: 100%;
  transition: var(--transition-smooth);
}

.nav-link:hover {
  background-color: rgba(216, 45, 139, 0.04);
  color: var(--accent-pink);
}

.nav-link.active {
  background-color: rgba(216, 45, 139, 0.08);
  color: var(--accent-pink);
}

.nav-icon {
  font-size: 16px;
}

.nav-dropdown {
  display: flex;
  flex-direction: column;
}

.dropdown-toggle {
  justify-content: space-between;
}

.dropdown-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dropdown-arrow {
  font-size: 12px;
  transition: transform 0.25s;
}

.dropdown-arrow.open {
  transform: rotate(180deg);
}

.dropdown-content {
  display: flex;
  flex-direction: column;
  margin-left: 20px;
  margin-top: 6px;
  gap: 4px;
}

.sub-nav-link {
  border: none;
  background: transparent;
  text-align: left;
  padding: 10px 16px;
  border-radius: 8px;
  cursor: pointer;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  transition: 0.25s;
}

.sub-nav-link:hover {
  background: rgba(216, 45, 139, 0.05);
  color: var(--accent-pink);
}

.sub-nav-link.active {
  background: rgba(216, 45, 139, 0.08);
  color: var(--accent-pink);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.25s ease;
  overflow: hidden;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  max-height: 0;
}

.dropdown-enter-to,
.dropdown-leave-from {
  opacity: 1;
  max-height: 200px;
}

.sidebar-footer {
  border-top: 1px solid rgba(0,0,0,0.06);
  padding-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.admin-info {
  padding-left: 6px;
}

.admin-name {
  font-weight: 700;
  font-size: 13px;
  color: #1e293b;
}

.admin-role {
  font-size: 11px;
  color: var(--text-muted);
}

.btn-logout-sidebar {
  background: transparent;
  border: 1px solid rgba(0, 0, 0, 0.08);
  color: #475569;
  font-weight: 600;
  font-size: 13px;
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.btn-logout-sidebar:hover {
  border-color: #ff5555;
  color: #ff5555;
  background-color: rgba(255, 85, 85, 0.04);
}

/* MAIN CONTENT AREA */
.admin-main-content {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 30px;
  min-width: 0;
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(0,0,0,0.05);
  padding-bottom: 20px;
}

.header-title {
  font-size: 26px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 4px;
}

.header-desc {
  color: var(--text-secondary);
  font-size: 14px;
}

.btn-back-client {
  color: var(--accent-pink);
  border: 1px solid rgba(216, 45, 139, 0.2);
  padding: 8px 16px;
  border-radius: var(--radius-full);
  font-size: 13px;
  font-weight: 700;
  transition: var(--transition-smooth);
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-back-client:hover {
  background: rgba(216, 45, 139, 0.05);
  border-color: var(--accent-pink);
}

.btn-checkin, .btn-checkout {
  padding: 8px 16px;
  border-radius: var(--radius-full);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  display: flex;
  align-items: center;
  gap: 5px;
}
.btn-checkin {
  background: #d1fae5;
  color: #059669;
}
.btn-checkout {
  background: #fee2e2;
  color: #dc2626;
}

/* WIDGETS STATS */
.stats-widgets {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 20px;
}

.widget-card {
  display: flex;
  padding: 24px 20px;
  gap: 16px;
  align-items: center;
  border-radius: 16px;
  color: white;
  box-shadow: 0 10px 20px rgba(0,0,0,0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.widget-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 25px rgba(0,0,0,0.15);
}

.grad-pink { background: linear-gradient(135deg, #f43f5e, #e11d48); }
.grad-violet { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.grad-orange { background: linear-gradient(135deg, #f97316, #ea580c); }
.grad-blue { background: linear-gradient(135deg, #0ea5e9, #0284c7); }

.widget-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 26px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(4px);
}

.widget-info {
  display: flex;
  flex-direction: column;
}

.widget-label {
  color: rgba(255, 255, 255, 0.9);
  font-size: 12px;
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.widget-value {
  font-size: 26px;
  font-weight: 800;
  color: #ffffff;
  margin: 4px 0;
  text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.widget-trend {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.75);
}

.trend-up {
  color: #a7f3d0;
  font-weight: 700;
}

/* REPORTS GRID & SVG CHART */
.reports-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 30px;
}

@media (max-width: 992px) {
  .reports-grid {
    grid-template-columns: 1fr;
  }
}

.report-card {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  background-color: #ffffff;
  border: 1px solid rgba(0,0,0,0.05);
}

.card-title {
  font-size: 16px;
  font-weight: 700;
  border-left: 4px solid var(--accent-pink);
  padding-left: 10px;
  color: #1e293b;
}

.chart-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.period-toggle {
  display: inline-flex;
  background: #f4f1f4;
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}

.period-toggle button {
  border: none;
  background: transparent;
  padding: 6px 16px;
  border-radius: 6px;
  font-size: 12.5px;
  font-weight: 700;
  color: #64748b;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.period-toggle button.active {
  background: #fff;
  color: var(--accent-pink);
  box-shadow: 0 2px 8px rgba(216, 45, 139, 0.15);
}

.chart-total {
  font-size: 13px;
  color: var(--text-secondary);
  margin: -6px 0 0;
}
.chart-total strong {
  color: #1e293b;
  font-size: 15px;
  font-weight: 800;
}

.ranks-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 40px 10px;
  color: var(--text-muted);
  text-align: center;
}
.ranks-empty span { font-size: 34px; }
.ranks-empty p { font-size: 13px; }

.chart-container {
  width: 100%;
}

.svg-chart {
  width: 100%;
  height: auto;
}

.movie-ranks-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rank-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: #fdfcfd;
  border: 1px solid rgba(0, 0, 0, 0.03);
  border-radius: 8px;
}

.rank-num {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  color: white;
  font-weight: 800;
  font-size: 11px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.rank-num.bg-pink { background-color: var(--accent-pink); }
.rank-num.bg-violet { background-color: var(--accent-violet); }
.rank-num.bg-tertiary { background-color: #718096; }

.rank-info {
  flex: 1;
}

.rank-info h4 {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 2px;
}

.rank-category {
  font-size: 10px;
  color: var(--text-muted);
}

.rank-sales {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.sales-value {
  color: var(--accent-pink);
  font-weight: 800;
  font-size: 14px;
}

.sales-tickets {
  font-size: 10px;
  color: var(--text-muted);
}

/* TRANSACTION REPORT */
.detailed-report {
  padding: 24px;
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.report-header h3 {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
}

.btn-export {
  background: transparent;
  border: 1px solid rgba(216, 45, 139, 0.2);
  color: var(--accent-pink);
  padding: 8px 16px;
  font-size: 12px;
  font-weight: 700;
  border-radius: 6px;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.btn-export:hover {
  background-color: rgba(216, 45, 139, 0.05);
}

.report-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.report-table th, .report-table td {
  padding: 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.04);
  font-size: 13px;
}

.report-table th {
  color: var(--text-muted);
  font-weight: 700;
  text-transform: uppercase;
}

.text-pink {
  color: var(--accent-pink);
}

.method-badge {
  background-color: #f1f5f9;
  color: #475569;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.status-pill-small {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.status-pill-small.active {
  background-color: #edfcf5;
  color: var(--accent-mint);
}

.status-pill-small.pending {
  background-color: #fffaf0;
  color: #dd6b20;
}
/* LIVE BADGE */
.live-badge {
  background-color: #ffcccc;
  color: #ff0000;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 9px;
  font-weight: bold;
  margin-left: 8px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.4; }
  100% { opacity: 1; }
}

.flash-live {
  animation: flashBg 1s ease;
}

@keyframes flashBg {
  0% { background-color: #ffcccc; }
  100% { background-color: #fff; }
}

/* DONUT CHART */
.donut-chart-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 30px;
  margin-top: 15px;
}
.donut-svg {
  width: 120px;
  height: 120px;
}
.circle-bg {
  fill: none;
  stroke: #e2e8f0;
  stroke-width: 3.8;
}
.circle-ticket {
  fill: none;
  stroke: var(--accent-pink);
  stroke-width: 3.8;
  stroke-linecap: round;
  transform-origin: center;
  transform: rotate(-90deg);
  transition: stroke-dasharray 1s ease;
}
.donut-text {
  font-size: 8px;
  font-weight: bold;
  fill: var(--text-primary);
  text-anchor: middle;
}
.donut-subtext {
  font-size: 3px;
  fill: var(--text-muted);
  text-anchor: middle;
}
.donut-legends {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.legend-item {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: var(--text-secondary);
}
.color-box {
  width: 12px;
  height: 12px;
  border-radius: 3px;
  margin-right: 8px;
}
.bg-cinema-red { background-color: var(--accent-pink); }
.bg-gray { background-color: #e2e8f0; }

/* OCCUPANCY */
.occupancy-container {
  display: flex;
  flex-direction: column;
  justify-content: center;
  height: 100%;
  padding: 10px;
}
.occupancy-value {
  font-size: 32px;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 10px;
}
.text-red {
  color: var(--accent-pink) !important;
}
.progress-bar-bg {
  width: 100%;
  height: 12px;
  background-color: #f1f5f9;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 10px;
}
.progress-bar-fill {
  height: 100%;
  border-radius: 6px;
  transition: width 1s ease, background 0.3s;
}
.occupancy-desc {
  font-size: 13px;
  color: var(--text-muted);
}



/* DATE FILTER PANEL */
.date-filter-panel {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 35px;
  padding: 20px 30px;
  border-left: 4px solid var(--accent-pink);
  border-radius: 10px;
  background-color: #ffffff;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(0, 0, 0, 0.03);
}
.filter-group {
  display: flex;
  align-items: center;
  gap: 10px;
}
.filter-group label {
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 14px;
}
.date-input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  outline: none;
  font-family: inherit;
  color: var(--text-primary);
  background-color: #f8fafc;
  transition: all 0.2s;
}
.date-input:focus {
  border-color: var(--accent-pink);
  box-shadow: 0 0 0 3px rgba(228, 44, 100, 0.1);
}
.btn-filter {
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
  border: none;
  padding: 9px 20px;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: opacity 0.2s, transform 0.1s;
}
.btn-filter:hover {
  opacity: 0.9;
}
.btn-filter:active {
  transform: translateY(1px);
}
.btn-filter:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Notification Styles */
.notification-wrapper {
  position: relative;
  margin-right: 15px;
  display: flex;
  align-items: center;
}

.bell-icon {
  position: relative;
  cursor: pointer;
  padding: 8px;
  color: var(--text-dark);
  transition: color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bell-icon:hover {
  color: var(--accent-pink);
}

.badge {
  position: absolute;
  top: 5px;
  right: 6px;
  background-color: var(--accent-pink);
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 2px solid white;
}

.notification-dropdown {
  position: absolute;
  top: 100%;
  right: -10px;
  width: 320px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  border: 1px solid rgba(0,0,0,0.05);
  z-index: 1000;
  margin-top: 10px;
  overflow: hidden;
  animation: dropIn 0.2s ease-out;
  text-align: left;
}

@keyframes dropIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.notif-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.notif-header strong {
  font-size: 14px;
  color: #1e293b;
}

.notif-read-all {
  background: none;
  border: none;
  color: var(--accent-pink);
  font-size: 12px;
  cursor: pointer;
  font-weight: 500;
}

.notif-read-all:hover {
  text-decoration: underline;
}

.notif-list {
  max-height: 350px;
  overflow-y: auto;
}

.notif-empty {
  padding: 24px;
  text-align: center;
  color: #94a3b8;
  font-size: 13px;
}

.notif-item {
  display: flex;
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.2s;
  gap: 12px;
}

.notif-item:hover {
  background: #f8fafc;
}

.notif-item.unread {
  background: #fff5f5;
}

.notif-icon {
  font-size: 20px;
  margin-top: 2px;
}

.notif-content {
  flex: 1;
}

.notif-message {
  margin: 0 0 4px 0;
  font-size: 13px;
  color: #334155;
  line-height: 1.4;
}

.notif-item.unread .notif-message {
  font-weight: 600;
  color: #0f172a;
}

.notif-time {
  font-size: 11px;
  color: #94a3b8;
}

</style>
