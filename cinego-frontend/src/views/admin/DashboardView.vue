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
        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'stats' }" 
          @click="activeTab = 'stats'"
        >
          <span class="nav-icon">📊</span>
          <span>Dashboard</span>
        </button>

        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'movies' }" 
          @click="activeTab = 'movies'"
        >
          <span class="nav-icon">🎬</span>
          <span>Quản Lý Phim</span>
        </button>

        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'showtimes' }" 
          @click="activeTab = 'showtimes'"
        >
          <span class="nav-icon">🕒</span>
          <span>Quản Lý Lịch Chiếu</span>
        </button>

        <button 
          class="nav-link" 
          :class="{ active: activeTab === 'revenue' }" 
          @click="activeTab = 'revenue'"
        >
          <span class="nav-icon">💰</span>
          <span>Thống Kê Doanh Thu</span>
        </button>
      </nav>

      <div class="sidebar-footer">
        <div class="admin-info">
          <p class="admin-name">{{ authStore.user?.name || 'Quản trị viên' }}</p>
          <p class="admin-role">Hệ Thống CineGo</p>
        </div>
        <button @click="handleLogout" class="btn-logout-sidebar">🚪 Đăng xuất</button>
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
        <router-link to="/" class="btn-back-client">👁️ Xem Client Website</router-link>
      </header>

      <!-- TAB 1: DASHBOARD STATS & CHARTS -->
      <div v-if="activeTab === 'stats'" class="dashboard-tab-content">
        <!-- Widgets thông số -->
        <div class="stats-widgets">
          <div class="widget-card glass-panel">
            <div class="widget-icon bg-pink">🎬</div>
            <div class="widget-info">
              <span class="widget-label">Tổng Số Phim</span>
              <span class="widget-value">{{ moviesCount }}</span>
              <span class="widget-trend">+3 phim đang chiếu</span>
            </div>
          </div>

          <div class="widget-card glass-panel">
            <div class="widget-icon bg-violet">🕒</div>
            <div class="widget-info">
              <span class="widget-label">Suất Chiếu Hôm Nay</span>
              <span class="widget-value">{{ showtimesCount }}</span>
              <span class="widget-trend">Phân bổ 2 phòng chiếu</span>
            </div>
          </div>

          <div class="widget-card glass-panel">
            <div class="widget-icon bg-mint">💰</div>
            <div class="widget-info">
              <span class="widget-label">Doanh Thu Tuần</span>
              <span class="widget-value">42.5 M</span>
              <span class="widget-trend trend-up">+18.4% so với tuần trước</span>
            </div>
          </div>
        </div>

        <!-- Biểu đồ doanh thu dạng SVG -->
        <div class="reports-grid">
          <div class="report-card glass-panel">
            <h3 class="card-title">Thống Kê Doanh Thu 7 Ngày Qua (VNĐ)</h3>
            
            <div class="chart-container">
              <svg viewBox="0 0 600 240" class="svg-chart">
                <!-- Grid Lines -->
                <line x1="50" y1="30" x2="550" y2="30" stroke="rgba(0,0,0,0.05)" stroke-width="1" />
                <line x1="50" y1="80" x2="550" y2="80" stroke="rgba(0,0,0,0.05)" stroke-width="1" />
                <line x1="50" y1="130" x2="550" y2="130" stroke="rgba(0,0,0,0.05)" stroke-width="1" />
                <line x1="50" y1="180" x2="550" y2="180" stroke="rgba(0,0,0,0.1)" stroke-width="1.5" />
                
                <!-- Revenue Area (Gradient Fill) -->
                <defs>
                  <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="var(--accent-pink)" stop-opacity="0.25"/>
                    <stop offset="100%" stop-color="var(--accent-violet)" stop-opacity="0.0"/>
                  </linearGradient>
                </defs>
                <path d="M 50 180 L 120 140 L 190 155 L 260 110 L 330 90 L 400 120 L 470 65 L 540 50 L 540 180 Z" fill="url(#chartGrad)" />
                
                <!-- Revenue Line -->
                <path d="M 50 180 L 120 140 L 190 155 L 260 110 L 330 90 L 400 120 L 470 65 L 540 50" fill="none" stroke="var(--accent-pink)" stroke-width="3.5" stroke-linecap="round" />
                
                <!-- Dots -->
                <circle cx="120" cy="140" r="4.5" fill="var(--accent-pink)" />
                <circle cx="190" cy="155" r="4.5" fill="var(--accent-pink)" />
                <circle cx="260" cy="110" r="4.5" fill="var(--accent-pink)" />
                <circle cx="330" cy="90" r="4.5" fill="var(--accent-pink)" />
                <circle cx="400" cy="120" r="4.5" fill="var(--accent-pink)" />
                <circle cx="470" cy="65" r="4.5" fill="var(--accent-pink)" />
                <circle cx="540" cy="50" r="5" fill="var(--accent-pink)" stroke="#ffffff" stroke-width="2" />
                
                <!-- Axis Labels -->
                <text x="50" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Thứ 2</text>
                <text x="120" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Thứ 3</text>
                <text x="190" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Thứ 4</text>
                <text x="260" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Thứ 5</text>
                <text x="330" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Thứ 6</text>
                <text x="400" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Thứ 7</text>
                <text x="470" y="198" fill="var(--text-secondary)" font-size="10" text-anchor="middle">Chủ Nhật</text>
                <text x="540" y="198" fill="var(--accent-pink)" font-size="10" font-weight="bold" text-anchor="middle">Hôm nay</text>
                
                <text x="40" y="34" fill="var(--text-muted)" font-size="10" text-anchor="end">10 Triệu</text>
                <text x="40" y="84" fill="var(--text-muted)" font-size="10" text-anchor="end">6 Triệu</text>
                <text x="40" y="134" fill="var(--text-muted)" font-size="10" text-anchor="end">3 Triệu</text>
                <text x="40" y="184" fill="var(--text-muted)" font-size="10" text-anchor="end">0đ</text>
              </svg>
            </div>
          </div>

          <div class="report-card glass-panel">
            <h3 class="card-title">Top Phim Ăn Khách Tuần Này</h3>
            <div class="movie-ranks-list">
              <div class="rank-item">
                <span class="rank-num bg-pink">1</span>
                <div class="rank-info">
                  <h4>Doctor Strange: Đa Vũ Trụ Hỗn Loạn</h4>
                  <span class="rank-category">Hành Động, Sci-Fi</span>
                </div>
                <div class="rank-sales">
                  <span class="sales-value">18.4M</span>
                  <span class="sales-tickets">224 vé</span>
                </div>
              </div>

              <div class="rank-item">
                <span class="rank-num bg-violet">2</span>
                <div class="rank-info">
                  <h4>Avatar: Dòng Chảy Của Nước</h4>
                  <span class="rank-category">Kỳ Ảo, Viễn Tưởng</span>
                </div>
                <div class="rank-sales">
                  <span class="sales-value">15.1M</span>
                  <span class="sales-tickets">160 vé</span>
                </div>
              </div>

              <div class="rank-item">
                <span class="rank-num bg-tertiary">3</span>
                <div class="rank-info">
                  <h4>Kẻ Kiến Tạo (The Creator)</h4>
                  <span class="rank-category">Hành Động, Drama</span>
                </div>
                <div class="rank-sales">
                  <span class="sales-value">9.0M</span>
                  <span class="sales-tickets">112 vé</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: DYNAMIC MOVIES CRUD -->
      <div v-else-if="activeTab === 'movies'">
        <MoviesView />
      </div>

      <!-- TAB 3: DYNAMIC SHOWTIMES CRUD -->
      <div v-else-if="activeTab === 'showtimes'">
        <ShowtimesView />
      </div>

      <!-- TAB 4: REVENUE TRANSACTION REPORT -->
      <div v-else-if="activeTab === 'revenue'" class="revenue-tab-content">
        <div class="glass-panel detailed-report">
          <div class="report-header">
            <h3>Báo Cáo Chi Tiết Hóa Đơn Đặt Vé</h3>
            <button class="btn-export">📥 Xuất File Báo Cáo (Excel)</button>
          </div>
          
          <table class="report-table">
            <thead>
              <tr>
                <th>Mã Hóa Đơn</th>
                <th>Khách Hàng</th>
                <th>Suất Chiếu</th>
                <th>Tổng Tiền</th>
                <th>Phương Thức</th>
                <th>Trạng Thái</th>
                <th>Thời Gian</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in bookings" :key="b.id">
                <td class="font-bold text-pink">{{ b.booking_code }}</td>
                <td>{{ b.user_email }}</td>
                <td>{{ b.movie_title }}</td>
                <td class="font-bold">{{ formatCurrency(b.total_amount) }}</td>
                <td><span class="method-badge">{{ b.payment_method }}</span></td>
                <td>
                  <span class="status-pill-small" :class="{ active: b.payment_status === 'paid', pending: b.payment_status === 'pending' }">
                    {{ b.payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ xử lý' }}
                  </span>
                </td>
                <td class="text-secondary">{{ b.date }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import MoviesView from './MoviesView.vue';
import ShowtimesView from './ShowtimesView.vue';
import api from '../../api/axios';

const authStore = useAuthStore();
const router = useRouter();

const activeTab = ref('stats');
const moviesCount = ref(0);
const showtimesCount = ref(0);
const bookings = ref([]);

const getTabTitle = computed(() => {
  const titles = {
    stats: 'Dashboard Quản Trị Hệ Thống',
    movies: 'Quản Lý Danh Sách Phim',
    showtimes: 'Quản Lý Suất Chiếu & Lịch Trình',
    revenue: 'Báo Cáo & Thống Kê Doanh Thu'
  };
  return titles[activeTab.value];
});

const getTabDesc = computed(() => {
  const descs = {
    stats: 'Xem tổng quan báo cáo doanh thu kinh doanh và biểu đồ tăng trưởng hệ thống CineGo.',
    movies: 'Quản lý phim đang chiếu, sắp chiếu, cấu hình các thể loại phim và hình ảnh poster.',
    showtimes: 'Quản lý lịch chiếu các phòng chiếu, kiểm tra phòng và dịch thuật, định dạng 2D/3D.',
    revenue: 'Lịch sử giao dịch chi tiết các hóa đơn đặt vé qua ví điện tử của người dùng.'
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

const fetchDashboardStats = async () => {
  try {
    const moviesRes = await api.get('/movies');
    moviesCount.value = moviesRes.data.length;
    
    const showtimesRes = await api.get('/admin/showtimes');
    showtimesCount.value = showtimesRes.data.length;
  } catch (err) {
    console.error('Fetch dashboard stats error:', err);
    moviesCount.value = 5;
    showtimesCount.value = 8;
  }
};

const fetchBookings = () => {
  // Populate realistic booking histories
  bookings.value = [
    { id: 1, booking_code: 'CG-582910', user_email: 'customer@cinego.com', movie_title: 'Avatar: Dòng Chảy Của Nước', total_amount: 215000, payment_method: 'vnpay', payment_status: 'paid', date: '2026-06-02 15:30' },
    { id: 2, booking_code: 'CG-942811', user_email: 'user_momo@gmail.com', movie_title: 'Doctor Strange 2', total_amount: 190000, payment_method: 'vnpay', payment_status: 'paid', date: '2026-06-02 14:15' },
    { id: 3, booking_code: 'CG-374829', user_email: 'alex_dev@gmail.com', movie_title: 'Ốc Mượn Hồn', total_amount: 150000, payment_method: 'momo', payment_status: 'pending', date: '2026-06-02 12:00' },
    { id: 4, booking_code: 'CG-102948', user_email: 'nguyenvan_a@gmail.com', movie_title: 'Ngôi Đền Kỳ Quái 5', total_amount: 230000, payment_method: 'vnpay', payment_status: 'paid', date: '2026-06-01 20:45' }
  ];
};

onMounted(() => {
  fetchDashboardStats();
  fetchBookings();
});
</script>

<style scoped>
.admin-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  min-height: 85vh;
  gap: 30px;
  background-color: #ffffff;
  color: var(--text-primary);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: 0 4px 30px rgba(0,0,0,0.03);
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
}

.btn-back-client:hover {
  background: rgba(216, 45, 139, 0.05);
  border-color: var(--accent-pink);
}

/* WIDGETS STATS */
.stats-widgets {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 20px;
}

.widget-card {
  display: flex;
  padding: 20px;
  gap: 16px;
  align-items: center;
  background-color: #ffffff;
  border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 4px 15px rgba(0,0,0,0.01);
}

.widget-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 22px;
}

.bg-violet { background-color: #f1ecf7; color: var(--accent-violet); }
.bg-pink { background-color: #fdf1f7; color: var(--accent-pink); }
.bg-mint { background-color: #edfcf5; color: var(--accent-mint); }

.widget-info {
  display: flex;
  flex-direction: column;
}

.widget-label {
  color: var(--text-muted);
  font-size: 11px;
  text-transform: uppercase;
  font-weight: 700;
}

.widget-value {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin: 2px 0;
}

.widget-trend {
  font-size: 11px;
  color: var(--text-secondary);
}

.trend-up {
  color: var(--accent-mint);
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
</style>
