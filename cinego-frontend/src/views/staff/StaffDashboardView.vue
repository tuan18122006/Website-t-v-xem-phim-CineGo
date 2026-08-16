<template>
  <div class="staff-layout">

    <aside class="staff-sidebar">
      <div class="sidebar-brand">
        <div class="cinego-logo-box">
          <span class="logo-cine">Cine</span><span class="logo-go">Go</span>
        </div>
        <span class="brand-name">CineGo Staff</span>
      </div>

      <nav class="sidebar-nav">
        <button
          class="nav-link"
          :class="{ active: activeTab === 'pos' }"
          @click="activeTab = 'pos'"
        >
          <span class="nav-icon">🛒</span>
          <span>Bán Vé Tại Quầy</span>
        </button>

        <button
          class="nav-link"
          :class="{ active: activeTab === 'lookup' }"
          @click="activeTab = 'lookup'"
        >
          <span class="nav-icon">🔎</span>
          <span>Tra Cứu Đơn Hàng</span>
        </button>

        <button
          class="nav-link"
          :class="{ active: activeTab === 'scan' }"
          @click="activeTab = 'scan'"
        >
          <span class="nav-icon">📷</span>
          <span>Quét Mã QR / Soát Vé</span>
        </button>
      </nav>

      <div class="sidebar-footer">
        <div class="staff-info">
          <p class="staff-name">{{ authStore.user?.name || 'Nhân viên' }}</p>
          <p class="staff-role">Nhân Viên Bán Vé</p>
        </div>
        <button @click="handleLogout" class="btn-logout-sidebar">🚪 Đăng xuất</button>
      </div>
    </aside>

    <main class="staff-main-content">
      <header class="content-header">
        <div>
          <h1 class="header-title">{{ getTabTitle }}</h1>
          <p class="header-desc">{{ getTabDesc }}</p>
        </div>
        <router-link to="/" class="btn-back-client">👁️ Xem Client Website</router-link>
      </header>

      <div v-show="activeTab === 'pos'">
        <StaffPOSView />
      </div>

      <div v-show="activeTab === 'lookup'">
        <BookingLookupView />
      </div>

      <div v-show="activeTab === 'scan'" class="scan-tab glass-panel">
        <div class="scan-container">
          <div class="scan-icon-wrapper">
            <span class="scan-icon">📷</span>
          </div>
          <h3>Soát vé qua Mã QR</h3>
          <p>Nhập mã đặt vé bên dưới hoặc dùng thiết bị quét mã QR để quét trực tiếp vé của khách hàng.</p>

          <form class="scan-form" @submit.prevent="handleScan">
            <input
              ref="scanInput"
              v-model="scanCode"
              type="text"
              class="scan-input"
              placeholder="Quét mã QR hoặc nhập mã vé (VD: CG-123456)..."
              autofocus
            />
            <div class="scan-actions">
              <button type="submit" class="btn-scan" :disabled="!scanCode">Xác Nhận</button>
              <button type="button" class="btn-camera" @click="showCamera = !showCamera">
                {{ showCamera ? 'Tắt Camera' : 'Bật Camera' }}
              </button>
              <label class="btn-camera" style="display: flex; align-items: center; justify-content: center; cursor: pointer; margin: 0;">
                Tải Ảnh Lên
                <qrcode-capture @detect="onDetect" style="display: none;"></qrcode-capture>
              </label>
            </div>
          </form>

          <div v-if="showCamera" class="camera-wrapper">
            <qrcode-stream @detect="onDetect"></qrcode-stream>
          </div>

          <div v-if="scanResult" class="scan-result" :class="scanResult.status">
            <h4 v-if="scanResult.status === 'success'">✅ Vé Hợp Lệ!</h4>
            <h4 v-else>❌ Lỗi Xác Nhận!</h4>
            <p>{{ scanResult.message }}</p>
          </div>

          <p v-if="loadingTicket" class="scan-loading">Đang tải thông tin vé…</p>
        </div>
      </div>

      <!-- Vé sau khi soát: hiện thông tin + in -->
      <transition name="st-fade">
        <div v-if="scanTicket" class="st-backdrop" @click.self="scanTicket = null">
          <div class="st-modal">
            <div class="st-head">
              <h3>✅ Vé hợp lệ — {{ scanTicket.booking_code }}</h3>
              <button class="st-close" @click="scanTicket = null">Đóng</button>
            </div>
            <TicketPrintable :booking="scanTicket" />
          </div>
        </div>
      </transition>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import BookingLookupView from '../admin/BookingLookupView.vue';
import StaffPOSView from './StaffPOSView.vue';
import TicketPrintable from '../../components/TicketPrintable.vue';
import api from '../../api/axios';
import { QrcodeStream, QrcodeCapture } from 'vue-qrcode-reader';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const VALID_TABS = ['pos', 'lookup', 'scan'];
const savedTab = localStorage.getItem('staff_active_tab');
const activeTab = ref(VALID_TABS.includes(savedTab) ? savedTab : 'pos');
if (route.query.pos_pay) activeTab.value = 'pos';
watch(activeTab, (v) => localStorage.setItem('staff_active_tab', v));
const scanCode = ref('');
const scanInput = ref(null);
const scanResult = ref(null);
const showCamera = ref(false);
const scanTicket = ref(null);
const loadingTicket = ref(false);

const onDetect = (detectedCodes) => {
  if (detectedCodes && detectedCodes.length > 0) {
    let rawValue = detectedCodes[0].rawValue;
    try {
      const url = new URL(rawValue);
      const scanParam = url.searchParams.get('scan');
      if (scanParam) rawValue = scanParam;
    } catch(e) {}

    scanCode.value = rawValue;
    showCamera.value = false;
    handleScan();
  }
};

watch(activeTab, (newVal) => {
  if (newVal === 'scan') {
    nextTick(() => {
      if (scanInput.value) {
        scanInput.value.focus();
      }
    });
  }
});

const TAB_META = {
  pos:    { title: 'Bán Vé Tại Quầy', desc: 'Chọn phim → suất → ghế → bắp nước → khách, thu tiền và tạo vé ngay tại quầy.' },
  lookup: { title: 'Tra Cứu Đơn Hàng & Hỗ Trợ Khách', desc: 'Tìm đơn theo tên/SĐT/email/mã đơn khi khách quên mã vé, xem ghế & bắp nước đã mua để hỗ trợ.' },
  scan:   { title: 'Soát Vé & Quét Mã QR', desc: 'Kiểm tra tính hợp lệ của vé. Đảm bảo vé chưa được sử dụng và đúng suất chiếu.' },
};

const getTabTitle = computed(() => (TAB_META[activeTab.value] || TAB_META.lookup).title);
const getTabDesc = computed(() => (TAB_META[activeTab.value] || TAB_META.lookup).desc);

const handleLogout = async () => {
  await authStore.logout();
  router.push('/');
};

const handleScan = async () => {
  const code = scanCode.value.trim();
  if (!code) return;
  scanResult.value = null;
  scanTicket.value = null;

  try {
    const res = await api.post('/staff/bookings/verify', { code });
    scanResult.value = {
      status: 'success',
      message: `Soát vé thành công cho mã đơn ${code}. Chúc quý khách xem phim vui vẻ!`
    };
    await loadScanTicket(res.data?.data?.booking_code || code);
  } catch (err) {
    scanResult.value = {
      status: 'error',
      message: err.response?.data?.message || 'Mã vé không hợp lệ hoặc đã được sử dụng.'
    };
  } finally {
    scanCode.value = '';
    if (scanInput.value) scanInput.value.focus();
  }
};

const loadScanTicket = async (code) => {
  loadingTicket.value = true;
  try {
    const look = await api.get('/staff/bookings/lookup', { params: { q: code } });
    const found = (look.data.data || [])[0];
    if (!found) return;
    const detail = await api.get(`/staff/bookings/${found.id}`);
    scanTicket.value = detail.data;
  } catch (e) {
    // không chặn kết quả soát nếu tải chi tiết vé lỗi
  } finally {
    loadingTicket.value = false;
  }
};
</script>

<style scoped>
.staff-layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  min-height: 100vh;
  gap: 30px;
  background-color: #ffffff;
  color: var(--text-primary);
}

@media (max-width: 992px) {
  .staff-layout {
    grid-template-columns: 1fr;
  }
}

.staff-sidebar {
  background-color: #fcf8fa;
  border-right: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  padding: 30px 20px;
  justify-content: space-between;
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

.sidebar-footer {
  border-top: 1px solid rgba(0,0,0,0.06);
  padding-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.staff-info {
  padding-left: 6px;
}

.staff-name {
  font-weight: 700;
  font-size: 13px;
  color: #1e293b;
}

.staff-role {
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

.staff-main-content {
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

.scan-tab {
  padding: 40px;
  display: flex;
  justify-content: center;
}

.scan-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  max-width: 500px;
  width: 100%;
}

.scan-icon-wrapper {
  width: 80px;
  height: 80px;
  background: rgba(216, 45, 139, 0.1);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  margin-bottom: 20px;
  border: 2px dashed var(--accent-pink);
}

.scan-container h3 {
  font-size: 22px;
  font-weight: 800;
  margin-bottom: 8px;
}

.scan-container p {
  color: var(--text-muted);
  margin-bottom: 30px;
}

.scan-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
}

.scan-input {
  width: 100%;
  padding: 16px;
  border: 2px solid var(--border-glass);
  border-radius: 12px;
  font-size: 16px;
  text-align: center;
  font-weight: 700;
  background: #f8fafc;
  outline: none;
  transition: var(--transition-smooth);
}

.scan-input:focus {
  border-color: var(--accent-pink);
  background: #fff;
  box-shadow: 0 0 0 4px rgba(216, 45, 139, 0.1);
}

.scan-actions {
  display: flex;
  gap: 12px;
}

.btn-scan {
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
  border: none;
  padding: 14px;
  font-size: 16px;
  font-weight: 700;
  border-radius: 12px;
  cursor: pointer;
}

.btn-camera {
  flex: 1;
  background: white;
  color: var(--accent-pink);
  border: 2px solid var(--accent-pink);
  padding: 14px;
  font-size: 16px;
  font-weight: 700;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-camera:hover {
  background: var(--accent-pink);
  color: white;
}

.camera-wrapper {
  margin-top: 20px;
  width: 100%;
  max-width: 400px;
  border-radius: 12px;
  overflow: hidden;
  border: 4px solid var(--accent-pink);
}

.btn-scan:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.scan-result {
  margin-top: 30px;
  padding: 20px;
  border-radius: 12px;
  width: 100%;
}

.scan-result.success {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid rgba(16, 185, 129, 0.3);
  color: #059669;
}

.scan-result.error {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #dc2626;
}

.scan-loading { text-align: center; color: var(--text-muted); font-size: 13px; margin-top: 12px; }
.st-backdrop { position: fixed; inset: 0; z-index: 2000; background: rgba(15, 6, 8, 0.5); backdrop-filter: blur(6px); display: flex; align-items: flex-start; justify-content: center; padding: 40px 20px; overflow-y: auto; }
.st-modal { background: #fff; border-radius: 18px; padding: 22px; max-width: 820px; width: 100%; }
.st-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.st-head h3 { font-size: 17px; font-weight: 800; color: #059669; }
.st-close { border: 1px solid #e2e8f0; background: #fff; color: #475569; padding: 8px 16px; border-radius: 9px; font-weight: 700; font-size: 13px; cursor: pointer; }
.st-close:hover { background: #f8fafc; }
.st-fade-enter-active, .st-fade-leave-active { transition: opacity 0.2s; }
.st-fade-enter-from, .st-fade-leave-to { opacity: 0; }
</style>
