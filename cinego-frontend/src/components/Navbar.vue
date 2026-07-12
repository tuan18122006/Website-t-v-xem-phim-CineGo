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
          <router-link to="/mua-ve" class="nav-item">Lịch chiếu</router-link>
          <router-link to="/" class="nav-item">Phim chiếu</router-link>
          <router-link to="/review-phim" class="nav-item"
            >Review phim</router-link
          >
          <router-link to="/top-phim" class="nav-item">Top phim</router-link>
          <router-link to="/blog-phim" class="nav-item">Blog phim</router-link>
          <router-link to="/ve-cinego" class="nav-item font-bold"
            >Về CineGo</router-link
          >
        </div>
      </div>

      <!-- Account & Search Section -->
      <div class="nav-right">
        <!-- Search icon/bar simulated -->
        <div class="search-box">
          <svg
            class="search-icon"
            xmlns="http://www.w3.org/2000/svg"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </div>

        <div
          v-if="bookingStore.holdExpiresAt && remainingTime > 0"
          class="nav-timer"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
          Giữ ghế: <span>{{ formatTime(remainingTime) }}</span>
        </div>

        <template v-if="authStore.isAuthenticated">
          <!-- Admin shortcut link -->
          <router-link v-if="authStore.isAdmin" to="/admin/dashboard" class="btn-admin-panel">
            Admin Panel
          </router-link>
          <!-- Staff shortcut link -->
          <router-link v-if="authStore.isStaff" to="/staff/dashboard" class="btn-admin-panel">
            Staff Panel
          </router-link>

          <div class="user-dropdown-container">
            <router-link to="/profile" class="user-avatar-btn">
              <img :src="authStore.user?.avatar_url || defaultAvatar" alt="Avatar" class="navbar-avatar" />
            </router-link>
            
            <div class="dropdown-menu glass-panel">
              <div class="dropdown-header">
                <strong>{{ authStore.user?.name }}</strong>
                <span>{{ authStore.user?.email }}</span>
              </div>
              <router-link to="/profile?tab=info" class="dropdown-item">Hồ sơ</router-link>
              <router-link to="/profile?tab=history" class="dropdown-item">Lịch sử mua hàng</router-link>
              <router-link to="/profile?tab=watched" class="dropdown-item">Phim đã xem</router-link>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item text-danger">Đăng xuất</button>
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
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { useBookingStore } from "../stores/booking";

const authStore = useAuthStore();
const bookingStore = useBookingStore();
const router = useRouter();

const remainingTime = ref(0);
let timerId = null;

const defaultAvatar = "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80";

const formatTime = (ms) => {
  if (ms <= 0) return "00:00";
  const minutes = Math.floor(ms / 60000);
  const seconds = Math.floor((ms % 60000) / 1000);
  return `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
};

const updateTimer = () => {
  if (bookingStore.holdExpiresAt) {
    const diff = bookingStore.holdExpiresAt - Date.now();
    remainingTime.value = diff > 0 ? diff : 0;

    if (diff <= 0) {
      bookingStore.clearBooking();
      alert("Thời gian giữ ghế đã hết hạn. Ghế của bạn đã được giải phóng!");
      router.push("/");
    }
  } else {
    remainingTime.value = 0;
  }
};

onMounted(() => {
  timerId = setInterval(updateTimer, 1000);
});

onUnmounted(() => {
  if (timerId) clearInterval(timerId);
});

const handleLogout = async () => {
  await authStore.logout();
  bookingStore.clearBooking();
  router.push("/");
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
  background: linear-gradient(
    135deg,
    var(--accent-pink) 0%,
    var(--accent-violet) 100%
  );
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

.nav-item:hover,
.router-link-active.nav-item {
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
}

.navbar-actions {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-dropdown-container {
  position: relative;
  display: flex;
  align-items: center;
}

.navbar-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #e5e7eb;
  transition: all 0.3s;
  cursor: pointer;
}

.navbar-avatar:hover {
  border-color: var(--accent-red);
  transform: scale(1.05);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 220px;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  border: 1px solid #f3f4f6;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  z-index: 100;
  overflow: hidden;
}

.user-dropdown-container:hover .dropdown-menu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.dropdown-header {
  padding: 15px;
  background: #f9fafb;
  border-bottom: 1px solid #f3f4f6;
  display: flex;
  flex-direction: column;
}

.dropdown-header strong {
  font-size: 14px;
  color: var(--text-dark);
}

.dropdown-header span {
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-item {
  display: block;
  padding: 12px 15px;
  font-size: 14px;
  font-weight: 500;
  color: #4b5563;
  text-decoration: none;
  transition: background 0.2s;
  text-align: left;
  border: none;
  background: transparent;
  width: 100%;
  cursor: pointer;
}

.dropdown-item:hover {
  background: #f3f4f6;
  color: var(--accent-red);
}

.dropdown-divider {
  height: 1px;
  background: #f3f4f6;
  margin: 4px 0;
}

.text-danger {
  color: #ef4444 !important;
}

.text-danger:hover {
  background: #fef2f2 !important;
}

@media (max-width: 768px) {
  .nav-links {
    display: none;
  }
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
  background: linear-gradient(
    135deg,
    var(--accent-pink) 0%,
    var(--accent-violet) 100%
  );
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
}

.user-profile-trigger {
  padding: 4px 8px;
}

/* Định dạng menu nội dung ẩn đi mặc định */
.dropdown-menu-content {
  display: none;
  position: absolute;
  right: 0;
  top: 100%;
  background-color: #ffffff;
  min-width: 160px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.1);
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  z-index: 200;
  padding: 6px 0;
}

/* Kỹ thuật Hover vào cha thì hiện con */
.user-dropdown-wrapper:hover .dropdown-menu-content {
  display: block;
}

/* Định dạng các đường link bên trong menu dropdown */
.dropdown-link {
  color: #333;
  padding: 10px 16px;
  text-decoration: none;
  display: block;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
}
.dropdown-link:hover {
  background-color: rgba(216, 45, 139, 0.05);
  color: var(--accent-pink);
}

.admin-link {
  color: var(--accent-violet);
}

.dropdown-divider {
  border: 0;
  border-top: 1px solid #eee;
  margin: 4px 0;
}

/* Định dạng riêng nút Đăng xuất trong menu */
.dropdown-logout-btn {
  width: 100%;
  background: none;
  border: none;
  text-align: left;
  padding: 10px 16px;
  font-size: 13px;
  font-weight: 600;
  color: #e71a0f;
  cursor: pointer;
}
.dropdown-logout-btn:hover {
  background-color: #fff0f0;
}
</style>
