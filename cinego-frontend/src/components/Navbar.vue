<template>
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-left">
        <!-- CineGo Red Logo Box & Branding -->
        <router-link to="/" class="nav-logo">
          <div class="cinego-logo-box">
            <span class="logo-cine">Cine</span><span class="logo-go">Go</span>
          </div>
          <div class="logo-divider"></div>
          <div class="logo-subtext">
            <span class="subtext-line1">Hệ Thống</span>
            <span class="subtext-line2">Đặt Vé</span>
          </div>
        </router-link>
        
        <div class="nav-links">
          <div class="nav-dropdown-wrapper">
  <span class="nav-item has-dropdown">
    Phim <svg class="dropdown-arrow" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
  </span>
  <div class="nav-dropdown-menu">
    <router-link to="/phim?type=now_showing" class="nav-dropdown-item">Tìm kiếm phim</router-link>

    <router-link to="/mua-ve" class="nav-dropdown-item">Lịch chiếu</router-link>
  </div>
</div>
          <router-link to="/review-phim" class="nav-item">Review phim</router-link>
          <router-link to="/top-phim" class="nav-item">Top phim</router-link>
          <router-link to="/blog-phim" class="nav-item">Blog phim</router-link>
          <router-link to="/ve-cinego" class="nav-item font-bold">Về CineGo</router-link>
        </div>
      </div>
      
      <!-- Account & Search Section -->
      <div class="nav-right">
        <!-- Search icon/bar simulated -->
      

        <div v-if="bookingStore.holdExpiresAt && remainingTime > 0" class="nav-hold">
          <router-link to="/booking/seats" class="nav-timer" :title="holdTooltip">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>Giữ ghế: <strong>{{ formatTime(remainingTime) }}</strong></span>
          </router-link>
          <button class="hold-cancel" title="Hủy giữ ghế" @click="cancelHold">
            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        
        <template v-if="authStore.isAuthenticated">
          <!-- Notification Dropdown -->
          <div class="notification-wrapper" @click.stop="toggleNotifications">
            <div class="bell-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
              <span v-if="unreadCount > 0" class="badge"></span>
            </div>
            
            <div v-if="isNotificationOpen" class="notification-dropdown" @click.stop>
              <div class="notif-header">
                <strong>Thông báo</strong>
                <button v-if="unreadCount > 0" class="notif-read-all" @click="markAllAsRead">Đánh dấu đã đọc tất cả</button>
              </div>
              <div class="notif-list">
                <div v-if="notifications.length === 0" class="notif-empty">
                  Chưa có thông báo nào.
                </div>
                <div v-for="notif in notifications" :key="notif.id" 
                     class="notif-item" 
                     :class="{'unread': notif.read_at === null}"
                     @click="markAsRead(notif.id)">
                  <div class="notif-icon">🎁</div>
                  <div class="notif-content">
                    <p class="notif-message">{{ notif.data.message }}</p>
                    <span class="notif-time">{{ new Date(notif.created_at).toLocaleString('vi-VN') }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <div class="user-dropdown-wrapper">
            <div class="user-profile-trigger">
              <img :src="authStore.user?.avatar_url || defaultAvatar" alt="Avatar" class="navbar-avatar" />
              <div class="user-profile">
                <span class="user-name">{{ authStore.user?.name }}</span>
                <span class="user-role">{{ authStore.user?.role === 'admin' ? 'ADMIN' : (authStore.user?.role === 'staff' ? 'NHÂN VIÊN' : 'THÀNH VIÊN') }}</span>
              </div>
            </div>
            
            <div class="dropdown-menu-content">
              <div class="dropdown-header">
                <strong>{{ authStore.user?.name }}</strong>
                <span>{{ authStore.user?.email }}</span>
              </div>
              <router-link v-if="authStore.isStaff" :to="authStore.isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="dropdown-link admin-link">Trang Quản Lý</router-link>
              <router-link to="/profile?tab=info" class="dropdown-link">Hồ sơ cá nhân</router-link>
              <router-link to="/profile?tab=history" class="dropdown-link">Lịch sử mua hàng</router-link>
              <router-link to="/profile?tab=watched" class="dropdown-link">Phim đã xem</router-link>
              <hr class="dropdown-divider" />
              <button @click="handleLogout" class="dropdown-logout-btn">Đăng xuất</button>
            </div>
          </div>
        </template>
        
        <template v-else>
          <router-link to="/login" class="btn-login">Đăng nhập</router-link>
          <router-link to="/register" class="btn-signup">Đăng ký</router-link>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router'; 
import { useAuthStore } from '../stores/auth';
import { useBookingStore } from '../stores/booking';
import api from '../api/axios';
import Swal from 'sweetalert2';

const authStore = useAuthStore();
const bookingStore = useBookingStore();
const router = useRouter();
const route = useRoute(); 

const currentTab = ref('now_showing');
const movies = ref([]);
const isLoading = ref(false);

const fetchMovies = async (type) => {
  isLoading.value = true;
  try {
    const response = await api.get(`/movies?status=${type}`);
    movies.value = response.data.data || response.data;
  } catch (error) {
    console.error('Lỗi tải danh sách phim:', error);
  } finally {
    isLoading.value = false;
  }
};

const notifications = ref([]);
const unreadCount = ref(0);
const isNotificationOpen = ref(false);

const toggleNotifications = () => {
  isNotificationOpen.value = !isNotificationOpen.value;
  if (isNotificationOpen.value) {
    fetchNotifications();
  }
};

const fetchNotifications = async () => {
  if (!authStore.isAuthenticated) return;
  try {
    const response = await api.get('/notifications');
    notifications.value = response.data.notifications.data || response.data.notifications;
    unreadCount.value = response.data.unread_count;
  } catch (err) {
    console.error('Lỗi lấy thông báo:', err);
  }
};

const markAsRead = async (id) => {
  try {
    await api.post(`/notifications/${id}/read`);
    fetchNotifications();
  } catch (err) {
    console.error(err);
  }
};

const markAllAsRead = async () => {
  try {
    await api.post('/notifications/read-all');
    fetchNotifications();
  } catch (err) {
    console.error(err);
  }
};

const remainingTime = ref(0);
let timerId = null;

const defaultAvatar = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";

const holdMovieTitle = computed(() => bookingStore.selectedMovie?.title || '');
const holdTooltip = computed(() =>
  `Đang giữ ${bookingStore.selectedSeats?.length || 0} ghế${holdMovieTitle.value ? ` của phim ${holdMovieTitle.value}` : ''}. Bấm để quay lại trang chọn ghế.`
);

const cancelHold = async () => {
  const result = await Swal.fire({
    title: 'Hủy giữ ghế?',
    text: 'Các ghế đang chọn sẽ được giải phóng ngay lập tức.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e30613',
    cancelButtonColor: '#6f6a63',
    confirmButtonText: 'Đồng ý hủy',
    cancelButtonText: 'Giữ tiếp'
  });
  if (!result.isConfirmed) return;

  const showtimeId = bookingStore.selectedShowtime?.id;
  const seats = bookingStore.selectedSeats || [];
  if (showtimeId && seats.length > 0) {
    await Promise.allSettled(
      seats.map((seat) =>
        api.post('/seat-holds/release', {
          showtime_id: showtimeId,
          seat_id: seat.id,
        })
      )
    );
  }
  bookingStore.clearBooking();
};

const formatTime = (ms) => {
  if (ms <= 0) return '00:00';
  const minutes = Math.floor(ms / 60000);
  const seconds = Math.floor((ms % 60000) / 1000);
  return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
};

const updateTimer = () => {
  if (bookingStore.holdExpiresAt) {
    const diff = bookingStore.holdExpiresAt - Date.now();
    remainingTime.value = diff > 0 ? diff : 0;
    
    if (diff <= 0) {
      bookingStore.clearBooking();
      alert('Thời gian giữ ghế đã hết hạn. Ghế của bạn đã được giải phóng!');
      router.push('/');
    }
  } else {
    remainingTime.value = 0;
  }
};

onMounted(() => {
  timerId = setInterval(updateTimer, 1000);
  if (authStore.isAuthenticated) {
    fetchNotifications();
  }
  document.addEventListener('click', () => {
    isNotificationOpen.value = false;
  });

  currentTab.value = route.query.type || 'now_showing';
  fetchMovies(currentTab.value);
});

watch(() => authStore.isAuthenticated, (newVal) => {
  if (newVal) {
    fetchNotifications();
  }
});

watch(() => route.query.type, (newType) => {
  currentTab.value = newType || 'now_showing';
  fetchMovies(currentTab.value);
});

onUnmounted(() => {
  if (timerId) clearInterval(timerId);
});

const handleLogout = async () => {
  await authStore.logout();
  bookingStore.clearBooking();
  router.push('/');
};
</script>

<style scoped>
.navbar {
  position: sticky;
  top: 0;
  z-index: 100;
  width: 100%;
  background: #ffffff;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.nav-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
  padding: 12px 24px;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 36px;
}

/* CineGo Logo Styling */
.nav-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.cinego-logo-box {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  padding: 8px 14px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 2px;
  box-shadow: 0 4px 10px rgba(229, 9, 20, 0.2);
}

.logo-cine {
  color: #ffffff;
  font-size: 15px;
  font-weight: 800;
  font-family: var(--font-display);
}

.logo-go {
  color: #ffffff;
  font-size: 15px;
  font-weight: 800;
  font-family: var(--font-display);
  background: #ffffff;
  color: var(--accent-pink);
  padding: 0px 4px;
  border-radius: 4px;
}

.logo-divider {
  width: 1px;
  height: 28px;
  background-color: rgba(0, 0, 0, 0.1);
}

.logo-subtext {
  display: flex;
  flex-direction: column;
}

.subtext-line1 {
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-weight: 700;
  letter-spacing: 0.05em;
  line-height: 1.1;
}

.subtext-line2 {
  font-size: 13px;
  color: var(--accent-pink);
  font-weight: 800;
  line-height: 1.1;
}

/* Nav Links */
.nav-links {
  display: flex;
  gap: 22px;
  align-items: center;
}

.nav-item {
  color: #2c3e50;
  font-weight: 600;
  font-size: 14px;
  transition: var(--transition-smooth);
}

.nav-item:hover, .router-link-active.nav-item {
  color: var(--accent-pink);
}

.font-bold {
  font-weight: 700;
}

/* Right Section */
.nav-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.search-box {
  color: var(--text-secondary);
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: var(--transition-smooth);
}

.search-box:hover {
  color: var(--accent-pink);
}

.nav-hold {
  display: flex;
  align-items: center;
  gap: 6px;
}

.nav-timer {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(216, 45, 139, 0.08);
  border: 1px solid rgba(216, 45, 139, 0.15);
  color: var(--accent-pink);
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: var(--transition-smooth);
  white-space: nowrap;
}

.nav-timer:hover {
  background: rgba(216, 45, 139, 0.14);
  border-color: var(--accent-pink);
}

.hold-cancel {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: 1px solid rgba(216, 45, 139, 0.2);
  border-radius: 50%;
  background: transparent;
  color: var(--accent-pink);
  cursor: pointer;
  transition: var(--transition-smooth);
}

.hold-cancel:hover {
  background: rgba(216, 45, 139, 0.12);
  border-color: var(--accent-pink);
}

.user-profile {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.user-name {
  color: var(--text-primary);
  font-weight: 700;
  font-size: 13px;
}

.user-role {
  color: var(--text-muted);
  font-size: 10px;
  text-transform: uppercase;
}

.btn-admin-panel {
  color: var(--accent-violet);
  border: 1px solid rgba(165, 0, 100, 0.2);
  padding: 6px 12px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
  transition: var(--transition-smooth);
}

.btn-admin-panel:hover {
  background: rgba(165, 0, 100, 0.05);
  border-color: var(--accent-violet);
}

.btn-logout {
  background: transparent;
  border: 1px solid var(--border-glass);
  color: var(--text-secondary);
  padding: 6px 14px;
  border-radius: var(--radius-full);
  cursor: pointer;
  font-weight: 600;
  font-size: 12px;
  transition: var(--transition-smooth);
}

.btn-logout:hover {
  border-color: var(--accent-pink);
  color: var(--accent-pink);
  background: rgba(216, 45, 139, 0.04);
}

.btn-login {
  color: #2c3e50;
  font-weight: 600;
  font-size: 14px;
  padding: 6px 12px;
  transition: var(--transition-smooth);
}

.btn-login:hover {
  color: var(--accent-pink);
}

.btn-signup {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 13px;
  padding: 8px 18px;
  border-radius: var(--radius-full);
  box-shadow: 0 4px 10px rgba(216, 45, 139, 0.2);
  transition: var(--transition-bounce);
}

.btn-signup:hover {
  transform: scale(1.03);
  box-shadow: 0 4px 15px rgba(216, 45, 139, 0.4);
}

/* Thiết lập khung cha tương đối */
.user-dropdown-wrapper {
  position: relative;
  display: inline-block;
  cursor: pointer;
  margin-left: 10px;
}

.user-profile-trigger {
  padding: 4px 8px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.navbar-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e2e8f0;
  transition: all 0.3s;
}

.user-dropdown-wrapper:hover .navbar-avatar {
  border-color: var(--accent-pink);
  transform: scale(1.05);
}

/* Định dạng menu nội dung ẩn đi mặc định */
.dropdown-menu-content {
  display: none;
  position: absolute;
  right: 0;
  top: 100%;
  background-color: #ffffff;
  min-width: 180px;
  box-shadow: 0px 8px 24px rgba(0,0,0,0.12);
  border: 1px solid rgba(0,0,0,0.06);
  border-radius: 12px;
  z-index: 200;
  padding: 8px 0;
}

/* Kỹ thuật Hover vào cha thì hiện con */
.user-dropdown-wrapper:hover .dropdown-menu-content {
  display: block;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-5px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Header bên trong Menu */
.dropdown-header {
  padding: 10px 20px 12px;
  border-bottom: 1px solid #f1f5f9;
  margin-bottom: 6px;
  display: flex;
  flex-direction: column;
}

.dropdown-header strong {
  font-size: 14px;
  color: #0f172a;
}

.dropdown-header span {
  font-size: 12px;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Định dạng các đường link bên trong menu dropdown */
.dropdown-link {
  color: #334155;
  padding: 10px 20px;
  text-decoration: none;
  display: block;
  font-size: 13.5px;
  font-weight: 600;
  text-align: left;
  transition: all 0.2s;
}
.dropdown-link:hover {
  background-color: rgba(216, 45, 139, 0.06);
  color: var(--accent-pink);
  padding-left: 24px;
}

.dropdown-link.admin-link {
  color: #dc2626;
  font-weight: 700;
}

.dropdown-link.admin-link:hover {
  background-color: #fef2f2;
  color: #b91c1c;
}

.dropdown-divider {
  border: 0;
  border-top: 1px solid #f1f5f9;
  margin: 6px 0;
}

/* Định dạng riêng nút Đăng xuất trong menu */
.dropdown-logout-btn {
  width: 100%;
  background: none;
  border: none;
  text-align: left;
  padding: 10px 20px;
  font-size: 13.5px;
  font-weight: 600;
  color: #e50914;
  cursor: pointer;
  transition: all 0.2s;
}
.dropdown-logout-btn:hover {
  background-color: #fef2f2;
  padding-left: 24px;
}
</style>


<style scoped>
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
.nav-dropdown-wrapper {
  position: relative;
  display: inline-block;
}

.nav-item.has-dropdown {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  padding: 8px 0;
}

.dropdown-arrow {
  transition: transform 0.2s ease;
}

.nav-dropdown-wrapper:hover .dropdown-arrow {
  transform: rotate(180deg);
}

.nav-dropdown-menu {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  background-color: #ffffff;
  min-width: 170px;
  box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.12);
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 10px;
  z-index: 200;
  padding: 6px 0;
  overflow: hidden;
}

.nav-dropdown-wrapper:hover .nav-dropdown-menu {
  display: block;
  animation: fadeIn 0.2s ease;
}

.nav-dropdown-item {
  color: #334155;
  padding: 10px 16px;
  text-decoration: none;
  display: block;
  font-size: 13.5px;
  font-weight: 600;
  transition: all 0.2s;
}

.nav-dropdown-item:hover,
.router-link-active.nav-dropdown-item {
  background-color: rgba(216, 45, 139, 0.06);
  color: var(--accent-pink);
  padding-left: 20px;
}
</style>
