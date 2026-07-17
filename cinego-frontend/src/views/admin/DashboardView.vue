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
          :class="{ active: activeTab === 'genres' }" 
          @click="activeTab = 'genres'"
        >
          <span class="nav-icon">🏷️</span>
          <span>Quản Lý Thể Loại</span>
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
  :class="{ active: activeTab === 'rooms' }" 
  @click="activeTab = 'rooms'"
>
  <span class="nav-icon">🏟️</span>
  <span>Quản Lý Phòng Chiếu & Ghế</span>
</button>

        <button
          class="nav-link"
          :class="{ active: activeTab === 'users' }"
          @click="activeTab = 'users'"
        >
          <span class="nav-icon">👥</span>
          <span>Quản Lý Tài Khoản</span>
        </button>

        <button class="nav-link" :class="{ active: activeTab === 'orders' }" @click="activeTab = 'orders'">
          <span class="nav-icon">🧾</span>
          <span>Quản Lý Đơn Hàng</span>
        </button>

         <button class="nav-link" :class="{ active: activeTab === 'combos' }" @click="activeTab = 'combos'">
          <span class="nav-icon">🍿</span>
          <span>Quản Lý Đồ Ăn Và Combo</span>
        </button>
         
        <button class="nav-link" :class="{ active: activeTab === 'vouchers' }" @click="activeTab = 'vouchers'">
          <span class="nav-icon">🎟️</span>
          <span>Quản Lý Mã Giảm Giá</span>
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
      <div v-show="activeTab === 'stats'" class="dashboard-tab-content">
        <!-- Widgets thông số THẬT -->
        <div class="stats-widgets">
          <div class="widget-card glass-panel">
            <div class="widget-icon bg-mint">💰</div>
            <div class="widget-info">
              <span class="widget-label">Tổng Doanh Thu</span>
              <span class="widget-value">{{ formatCurrency(totalRevenue) }}</span>
              <span class="widget-trend">Từ các đơn đã thanh toán</span>
            </div>
          </div>

          <div class="widget-card glass-panel">
            <div class="widget-icon bg-pink">🎟️</div>
            <div class="widget-info">
              <span class="widget-label">Vé Đã Bán</span>
              <span class="widget-value">{{ totalTickets.toLocaleString('vi-VN') }}</span>
              <span class="widget-trend">Tổng số vé xuất thành công</span>
            </div>
          </div>

          <div class="widget-card glass-panel">
            <div class="widget-icon bg-violet">🍿</div>
            <div class="widget-info">
              <span class="widget-label">Bắp Nước Đã Bán</span>
              <span class="widget-value">{{ totalCombos.toLocaleString('vi-VN') }}</span>
              <span class="widget-trend">Tổng số combo bắp &amp; nước</span>
            </div>
          </div>

          <div class="widget-card glass-panel">
            <div class="widget-icon bg-pink">🕒</div>
            <div class="widget-info">
              <span class="widget-label">Suất Chiếu Hôm Nay</span>
              <span class="widget-value">{{ todayShowtimes }}</span>
              <span class="widget-trend">Lịch chiếu trong ngày</span>
            </div>
          </div>
        </div>

        <!-- Biểu đồ doanh thu dạng SVG -->
        <div class="reports-grid">
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

          <div class="report-card glass-panel">
            <h3 class="card-title">Top Phim Bán Chạy Nhất</h3>
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
              <span>🎬</span>
              <p>Chưa có dữ liệu bán vé để xếp hạng.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: DYNAMIC MOVIES CRUD -->
      <div v-if="activeTab === 'movies'">
        <MoviesView />
      </div>

      <!-- TAB 3: DYNAMIC SHOWTIMES CRUD -->
      <div v-if="activeTab === 'showtimes'">
        <ShowtimesView />
      </div>

      <!-- TAB: QUẢN LÝ RẠP & GHẾ -->
      <div v-if="activeTab === 'rooms'">
        <RoomsView />
      </div>



      <div v-show="activeTab === 'combos'">
        <ComboSelection />
      </div>

      <div v-show="activeTab === 'vouchers'">
        <VoucherManager />
      </div>

      <div v-show="activeTab === 'genres'">
        <GenreManagement />
      </div>

      <!-- TAB: USER MANAGEMENT -->
      <div v-if="activeTab === 'users'">
        <UserManagement />
      </div>

      <div v-if="activeTab === 'orders'" class="orders-tab-content">
        <div class="glass-panel orders-panel">
          <div class="orders-panel__head">
            <div>
              <h3>Quản lý đơn hàng</h3>
              <p>Xem đơn đã đặt / đang đặt, phân biệt thành viên và khách vãng lai, lọc theo thời gian hoặc phim.</p>
            </div>
            <button class="btn-export" @click="loadOrders">🔄 Tải lại</button>
          </div>

          <div class="orders-toolbar">
            <input v-model="ordersSearch" type="text" placeholder="Tìm theo mã đơn, tên khách, phim…" />
            <input v-model="ordersFromDate" type="date" />
            <input v-model="ordersToDate" type="date" />
            <select v-model="ordersMovieFilter">
              <option value="">Tất cả phim</option>
              <option v-for="movie in movieOptions" :key="movie" :value="movie">{{ movie }}</option>
            </select>
            <select v-model="ordersCustomerType">
              <option value="">Tất cả khách</option>
              <option value="member">Thành viên</option>
              <option value="guest">Khách vãng lai</option>
            </select>
            <select v-model="ordersStatusFilter">
              <option value="">Tất cả trạng thái</option>
              <option value="pending">Chờ thanh toán</option>
              <option value="paid">Đã thanh toán</option>
              <option value="cancelled">Đã hủy</option>
              <option value="refunded">Đã hoàn tiền</option>
            </select>
            <button class="orders-filter-btn" @click="loadOrders">Lọc</button>
          </div>

          <div v-if="ordersLoading" class="lookup-state">
            <div class="lookup-spinner"></div>
            <p>Đang tải đơn hàng…</p>
          </div>

          <div v-else-if="filteredOrders.length === 0" class="lookup-state">
            <span class="lookup-state__art">🧾</span>
            <h4>Không có đơn hàng phù hợp</h4>
          </div>

          <div v-else class="orders-table-wrap">
            <table class="report-table orders-table">
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Khách</th>
                  <th>Loại khách</th>
                  <th>Phim / Suất</th>
                  <th>Tổng tiền</th>
                  <th>Thanh toán</th>
                  <th>Trạng thái</th>
                  <th>Thời gian</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in filteredOrders" :key="order.id">
                  <td class="font-bold text-pink">{{ order.booking_code }}</td>
                  <td>
                    <div class="customer-cell">
                      <span class="cell-avatar">{{ initials(order.customer_name) }}</span>
                      <div>
                        <strong>{{ order.customer_name || 'Khách vãng lai' }}</strong>
                        <div class="muted small">{{ order.customer_phone || '—' }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="type-pill" :class="order.customer_type === 'member' ? 'is-member' : 'is-guest'">
                      {{ order.customer_type === 'member' ? 'Thành viên' : 'Khách vãng lai' }}
                    </span>
                  </td>
                  <td>
                    <div class="movie-cell">
                      <strong>{{ order.movie_title }}</strong>
                      <div class="muted small">{{ order.room_name }} • {{ order.showtime_at || '—' }}</div>
                    </div>
                  </td>
                  <td class="font-bold">{{ formatCurrency(order.total_amount) }}</td>
                  <td>{{ order.payment_method || '—' }}</td>
                  <td>
                    <span class="status-pill-small" :class="statusClass(order.order_status)">{{ statusLabel(order.order_status) }}</span>
                  </td>
                  <td>{{ order.created_at || '—' }}</td>
                  <td><button class="btn-ghost" @click="viewOrderDetail(order)">Xem chi tiết</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <transition name="lk-fade">
          <div v-if="showOrderModal" class="lk-backdrop" @click.self="showOrderModal = false">
            <div class="lk-modal">
              <div class="lk-modal__head">
                <h3>🎟️ Chi tiết đơn {{ selectedOrder?.booking_code }}</h3>
                <button class="lk-modal__close" @click="showOrderModal = false">✕</button>
              </div>

              <div v-if="orderDetailLoading" class="lookup-state">
                <div class="lookup-spinner"></div>
                <p>Đang tải chi tiết đơn…</p>
              </div>

              <div v-else-if="selectedOrderDetail" class="lk-body">
                <section class="lk-section">
                  <h4 class="lk-section__title">👤 Khách hàng</h4>
                  <div class="lk-kv"><span>Họ tên</span><b>{{ selectedOrderDetail.customer.name || '—' }}</b></div>
                  <div class="lk-kv"><span>Điện thoại</span><b>{{ selectedOrderDetail.customer.phone || '—' }}</b></div>
                  <div class="lk-kv"><span>Email</span><b>{{ selectedOrderDetail.customer.email || '—' }}</b></div>
                </section>

                <section class="lk-section">
                  <h4 class="lk-section__title">🎬 Suất chiếu</h4>
                  <div class="lk-movie">
                    <img v-if="selectedOrderDetail.movie.poster_url" :src="selectedOrderDetail.movie.poster_url" :alt="selectedOrderDetail.movie.title" />
                    <div>
                      <strong>{{ selectedOrderDetail.movie.title }}</strong>
                      <p class="muted">🏛️ {{ selectedOrderDetail.room_name }} • 🕒 {{ selectedOrderDetail.showtime_at || '—' }}</p>
                    </div>
                  </div>
                </section>

                <section class="lk-section">
                  <h4 class="lk-section__title">🎟️ Chi tiết vé</h4>
                  <TicketPrintable :booking="selectedOrderDetail" />
                </section>

                <section class="lk-section">
                  <h4 class="lk-section__title">⚙️ Trạng thái đơn hàng</h4>
                  <div class="lk-kv">
                    <span>Trạng thái hiện tại</span>
                    <select v-model="selectedOrderStatus" class="status-select">
                      <option value="pending">Chờ thanh toán</option>
                      <option value="paid">Đã thanh toán</option>
                      <option value="cancelled">Đã hủy</option>
                      <option value="refunded">Đã hoàn tiền</option>
                    </select>
                  </div>
                  <button class="btn-primary" :disabled="orderStatusUpdating || !selectedOrderStatus" @click="saveOrderStatus">
                    {{ orderStatusUpdating ? 'Đang cập nhật…' : 'Cập nhật trạng thái' }}
                  </button>
                </section>
              </div>
            </div>
          </div>
        </transition>
      </div>

      <!-- TAB: COMBO MANAGEMENT -->
     
    

      <!-- TAB 4: REVENUE TRANSACTION REPORT -->
      <div v-if="activeTab === 'revenue'" class="revenue-tab-content">
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
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import api from '../../api/axios';
import MoviesView from './MoviesView.vue';
import ShowtimesView from './ShowtimesView.vue';
import GenreManagement from './GenreManagement.vue';
import UserManagement from './UserManagement.vue';
import RoomsView from './RoomManagementView.vue';
import RoomManagementView from './RoomManagementView.vue';
import RoomEditorView from './RoomEditorView.vue';
import ComboSelection from './ComboSelection.vue'; 
import ComboManagementView from './ComboManagementView.vue';
import VoucherManager from './VoucherManager.vue';
import TicketPrintable from '../../components/TicketPrintable.vue';

const authStore = useAuthStore();
const router = useRouter();

const activeTab = ref(localStorage.getItem('admin_active_tab') || 'stats');
watch(activeTab, (newVal) => {
  localStorage.setItem('admin_active_tab', newVal);
});
const moviesCount = ref(0);
const showtimesCount = ref(0);
const bookings = ref([]);
const orders = ref([]);
const ordersLoading = ref(false);
const ordersSearch = ref('');
const ordersFromDate = ref('');
const ordersToDate = ref('');
const ordersMovieFilter = ref('');
const ordersCustomerType = ref('');
const ordersStatusFilter = ref('');
const showOrderModal = ref(false);
const orderDetailLoading = ref(false);
const orderStatusUpdating = ref(false);
const selectedOrder = ref(null);
const selectedOrderDetail = ref(null);
const selectedOrderStatus = ref('');
const movieOptions = ref([]);

/* ===== DASHBOARD THỐNG KÊ THẬT ===== */
const statsLoading = ref(false);
const totalRevenue = ref(0);
const totalTickets = ref(0);
const totalCombos = ref(0);
const todayShowtimes = ref(0);
const topMovies = ref([]);

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
    stats: 'Dashboard Quản Trị Hệ Thống',
    movies: 'Quản Lý Danh Sách Phim',
    showtimes: 'Quản Lý Suất Chiếu & Lịch Trình',
    rooms: 'Quản Lý Phòng Chiếu & Ghế',
    genres: 'Quản Lý Thể Loại Phim',
    users: 'Quản Lý Tài Khoản & Phân Quyền',
    orders: 'Quản Lý Đơn Hàng',
    lookup: 'Tra Cứu Đơn Hàng & Hỗ Trợ Khách',
    combos: 'Quản Lý Combo và Đồ ăn',
    combos: 'Quản Lý Bắp Nước (Combos)',
    vouchers: 'Quản Lý Mã Giảm Giá (Vouchers)',
    revenue: 'Báo Cáo & Thống Kê Doanh Thu'
  };
  return titles[activeTab.value];
});



const getTabDesc = computed(() => {
  const descs = {
    stats: 'Xem tổng quan báo cáo doanh thu kinh doanh và biểu đồ tăng trưởng hệ thống CineGo.',
    movies: 'Quản lý phim đang chiếu, sắp chiếu, cấu hình các thể loại phim và hình ảnh poster.',
    showtimes: 'Quản lý lịch chiếu các phòng chiếu, kiểm tra phòng và dịch thuật, định dạng 2D/3D.',
    rooms: 'Thiết kế trực quan sơ đồ không gian rạp, quản lý các loại ghế (Thường, VIP, Đôi) và lối đi.',
    genres: 'Quản lý danh mục thể loại phim của hệ thống CineGo.',
    users: 'Thêm, sửa, phân quyền (Admin/Staff/User) và khóa/mở khóa tài khoản người dùng.',
    orders: 'Xem, lọc và tra cứu đơn hàng theo thời gian, phim và loại khách để quản lý bán vé hiệu quả.',
    lookup: 'Tìm đơn theo SĐT/email/mã đơn khi khách quên mã vé, xem ghế & bắp nước đã mua để hỗ trợ.',
    combos: 'Thêm, sửa, xóa, combo và đồ ăn kiểm kê số lượng tồn trong kho',
    combos: 'Cấu hình giá cả, thêm/sửa/xóa và quản lý trạng thái hiển thị của các gói Bắp & Nước.',
    vouchers: 'Tạo mã giảm giá, giới hạn số lần dùng, thiết lập điều kiện tối thiểu.',
    revenue: 'Lịch sử giao dịch chi tiết các hóa đơn đặt vé qua ví điện tử của người dùng.'
  };
  return descs[activeTab.value];
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/');
};

const fetchOverview = async () => {
  statsLoading.value = true;
  try {
    const res = await api.get('/admin/dashboard/overview');
    const d = res.data;
    totalRevenue.value = d.total_revenue;
    totalTickets.value = d.total_tickets;
    totalCombos.value = d.total_combos;
    moviesCount.value = d.movies_count;
    showtimesCount.value = d.today_showtimes;
    todayShowtimes.value = d.today_showtimes;
    topMovies.value = d.top_movies || [];
  } catch (err) {
    console.error('Fetch dashboard overview error:', err);
  } finally {
    statsLoading.value = false;
  }
};

const fetchRevenue = async () => {
  try {
    const res = await api.get('/admin/dashboard/revenue', {
      params: { period: revenuePeriod.value },
    });
    revenueSeries.value = res.data.series || [];
    revenueTotal.value = res.data.total || 0;
  } catch (err) {
    console.error('Fetch revenue chart error:', err);
    revenueSeries.value = [];
    revenueTotal.value = 0;
  }
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

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val || 0);
};

const initials = (name) => {
  if (!name) return '👤';
  const parts = name.trim().split(/\s+/);
  return (parts[0][0] + (parts[parts.length - 1][0] || '')).toUpperCase();
};

const loadOrders = async () => {
  ordersLoading.value = true;
  try {
    const res = await api.get('/admin/orders', {
      params: {
        search: ordersSearch.value,
        from_date: ordersFromDate.value,
        to_date: ordersToDate.value,
        movie: ordersMovieFilter.value,
        customer_type: ordersCustomerType.value,
        order_status: ordersStatusFilter.value,
      },
    });
    orders.value = res.data.data || [];
    movieOptions.value = [...new Set(orders.value.map((o) => o.movie_title).filter(Boolean))];
  } catch (err) {
    console.error('Load orders error:', err);
    orders.value = [];
  } finally {
    ordersLoading.value = false;
  }
};

const filteredOrders = computed(() => {
  const search = ordersSearch.value.trim().toLowerCase();
  return orders.value.filter((order) => {
    const matchesSearch = !search || [order.booking_code, order.customer_name, order.customer_email, order.customer_phone, order.movie_title].some((value) => String(value || '').toLowerCase().includes(search));
    const matchesMovie = !ordersMovieFilter.value || order.movie_title === ordersMovieFilter.value;
    const matchesType = !ordersCustomerType.value || order.customer_type === ordersCustomerType.value;
    const matchesStatus = !ordersStatusFilter.value || order.order_status === ordersStatusFilter.value;
    const createdAt = order.created_at_full || '';
    const fromOk = !ordersFromDate.value || createdAt >= ordersFromDate.value;
    const toOk = !ordersToDate.value || createdAt <= `${ordersToDate.value} 23:59:59`;
    return matchesSearch && matchesMovie && matchesType && matchesStatus && fromOk && toOk;
  });
});

const statusLabel = (status) => {
  return {
    pending: 'Chờ thanh toán',
    paid: 'Đã thanh toán',
    cancelled: 'Đã hủy',
    refunded: 'Đã hoàn tiền',
  }[status] || status;
};

const statusClass = (status) => {
  return {
    paid: 'active',
    pending: 'pending',
    cancelled: 'cancelled',
    refunded: 'refunded',
  }[status] || '';
};

const viewOrderDetail = async (order) => {
  showOrderModal.value = true;
  orderDetailLoading.value = true;
  selectedOrder.value = order;
  selectedOrderDetail.value = null;
  selectedOrderStatus.value = order.order_status || 'pending';
  try {
    const res = await api.get(`/admin/orders/${order.id}`);
    selectedOrderDetail.value = res.data;
    selectedOrderStatus.value = res.data.order_status || 'pending';
  } catch (err) {
    console.error('Load order detail error:', err);
  } finally {
    orderDetailLoading.value = false;
  }
};

const saveOrderStatus = async () => {
  if (!selectedOrder.value || !selectedOrderStatus.value) {
    return;
  }
  orderStatusUpdating.value = true;
  try {
    await api.patch(`/admin/orders/${selectedOrder.value.id}/status`, {
      order_status: selectedOrderStatus.value,
    });
    await loadOrders();
    if (selectedOrderDetail.value) {
      selectedOrderDetail.value.order_status = selectedOrderStatus.value;
    }
  } catch (err) {
    console.error('Update order status error:', err);
    alert(err.response?.data?.message || 'Không thể cập nhật trạng thái đơn hàng.');
  } finally {
    orderStatusUpdating.value = false;
  }
};

onMounted(() => {
  fetchOverview();
  fetchRevenue();
  fetchBookings();
  loadOrders();
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

/* ORDERS TAB STYLES */
.orders-tab-content { display: flex; flex-direction: column; gap: 18px; }
.orders-panel { padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.orders-panel__head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.orders-panel__head h3 { margin: 0 0 4px; font-size: 20px; color: #1e293b; }
.orders-panel__head p { margin: 0; color: var(--text-secondary); font-size: 13px; }
.orders-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.orders-toolbar input, .orders-toolbar select { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; min-width: 170px; }
.orders-toolbar input:focus, .orders-toolbar select:focus { outline: none; border-color: var(--accent-pink); box-shadow: 0 0 0 3px rgba(216, 45, 139, 0.12); }
.orders-filter-btn { border: none; background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet)); color: white; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 700; }
.orders-table-wrap { overflow-x: auto; }
.orders-table th, .orders-table td { white-space: nowrap; }
.customer-cell { display: flex; align-items: center; gap: 10px; }
.cell-avatar { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #fde2ef; color: var(--accent-pink); font-weight: 800; }
.type-pill { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; }
.type-pill.is-member { background: rgba(24, 144, 255, 0.12); color: #1677ff; }
.type-pill.is-guest { background: rgba(255, 140, 0, 0.12); color: #d97706; }
.movie-cell { display: flex; flex-direction: column; gap: 4px; }
.small { font-size: 12px; }

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
</style>
