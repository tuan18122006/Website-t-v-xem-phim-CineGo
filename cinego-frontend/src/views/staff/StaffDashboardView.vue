<template>
  <div class="staff-layout">
    <!-- LEFT SIDEBAR -->
    <aside class="staff-sidebar">
      <div class="sidebar-brand">
        <div class="cinego-logo-box">
          <span class="logo-cine">Cine</span><span class="logo-go">Go</span>
        </div>
        <span class="brand-name">CineGo Staff</span>
      </div>

      <nav class="sidebar-nav">
        <!-- <button 
          class="nav-link" 
          :class="{ active: activeTab === 'pos' }" 
          @click="activeTab = 'pos'"
        >
          <span class="nav-icon">🎟️</span>
          <span>Bán Vé Tại Quầy (POS)</span>
        </button> -->

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

    <!-- RIGHT MAIN CONTENT AREA -->
    <main class="staff-main-content">
      <header class="content-header">
        <div>
          <h1 class="header-title">{{ getTabTitle }}</h1>
          <p class="header-desc">{{ getTabDesc }}</p>
        </div>
        <router-link to="/" class="btn-back-client">👁️ Xem Client Website</router-link>
      </header>

      <!-- TAB: BÁN VÉ TẠI QUẦY (POS) - Tạm ẩn -->
      <!-- <div v-if="activeTab === 'pos'">
        <StaffPOSView />
      </div> -->

      <!-- TAB: TRA CỨU ĐƠN HÀNG -->
      <div v-show="activeTab === 'lookup'">
        <BookingLookupView />
      </div>

      <!-- TAB: QUÉT MÃ QR -->
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

          <!-- Kết quả quét -->
          <div v-if="scanResult" class="scan-result" :class="scanResult.status">
            <h4 v-if="scanResult.status === 'success'">✅ Vé Hợp Lệ!</h4>
            <h4 v-else>❌ Lỗi Xác Nhận!</h4>
            <p>{{ scanResult.message }}</p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';
import BookingLookupView from '../admin/BookingLookupView.vue';
import StaffPOSView from './StaffPOSView.vue';
import api from '../../api/axios';
import { QrcodeStream, QrcodeCapture } from 'vue-qrcode-reader';

const authStore = useAuthStore();
const router = useRouter();

const activeTab = ref('lookup');
const scanCode = ref('');
const scanInput = ref(null);
const scanResult = ref(null);
const showCamera = ref(false);

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

const getTabTitle = computed(() => {
  if (activeTab.value === 'pos') return 'Bán Vé Tại Quầy (POS)';
  return activeTab.value === 'lookup' ? 'Tra Cứu Đơn Hàng & Hỗ Trợ Khách' : 'Soát Vé & Quét Mã QR';
});

const getTabDesc = computed(() => {
  if (activeTab.value === 'pos') return 'Bán vé trực tiếp cho khách vãng lai, thao tác nhanh chọn phim, ghế, bắp nước và thu tiền mặt.';
  return activeTab.value === 'lookup' 
    ? 'Tìm đơn theo SĐT/email/mã đơn khi khách quên mã vé, xem ghế & bắp nước đã mua để hỗ trợ.' 
    : 'Kiểm tra tính hợp lệ của vé. Đảm bảo vé chưa được sử dụng và đúng suất chiếu.';
});

const handleLogout = async () => {
  await authStore.logout();
  router.push('/');
};

const handleScan = async () => {
  if (!scanCode.value) return;
  scanResult.value = null;
  
  try {
    // Giả lập API verify vé
    const res = await api.post('/staff/bookings/verify', { code: scanCode.value });
    scanResult.value = {
      status: 'success',
      message: `Soát vé thành công cho mã đơn ${scanCode.value}. Chúc quý khách xem phim vui vẻ!`
    };
  } catch (err) {
    scanResult.value = {
      status: 'error',
      message: err.response?.data?.message || 'Mã vé không hợp lệ hoặc đã được sử dụng.'
    };
  } finally {
    scanCode.value = '';
    scanInput.value.focus();
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
  flex: 1;
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
</style>
