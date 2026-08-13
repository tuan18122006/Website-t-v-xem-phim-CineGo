<template>
  <div class="qr-payment-view">
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Đang tải thông tin thanh toán...</p>
    </div>
    <div v-else-if="error" class="error-state">
      <i class="fi fi-rr-exclamation"></i>
      <p>{{ error }}</p>
      <button @click="goBack" class="btn-cinego-main">Quay lại</button>
    </div>
    <div v-else class="qr-container shadow-container">
      <h2 class="section-title text-center">Thanh toán vé phim</h2>
      
      <div class="qr-layout">
        <!-- Cột trái: QR và hướng dẫn -->
        <div class="qr-left">
          <div class="qr-image-wrapper">
            <img v-if="qrUrl" :src="qrUrl" alt="Mã QR Thanh Toán" class="qr-image" />
            <div v-else class="qr-placeholder">
              <p>Chưa cấu hình thông tin ngân hàng. Vui lòng liên hệ Admin.</p>
            </div>
          </div>
          <div class="instruction">
            <p>1. Mở ứng dụng ngân hàng trên điện thoại</p>
            <p>2. Chọn tính năng <strong>Quét mã QR</strong></p>
            <p>3. Quét mã phía trên để thanh toán tự động</p>
            <p class="warning-text">Lưu ý: Giữ nguyên nội dung chuyển khoản để hệ thống tự động xác nhận.</p>
          </div>
        </div>

        <!-- Cột phải: Thông tin thanh toán -->
        <div class="qr-right">
          <div class="payment-info">
            <h3 class="info-title">Thông tin giao dịch</h3>
            <div class="info-row">
              <span>Mã đơn hàng:</span>
              <strong>{{ booking.booking_code }}</strong>
            </div>
            <div class="info-row">
              <span>Tổng tiền:</span>
              <strong class="price-highlight">{{ formatCurrency(booking.total_amount || booking.total_price) }}</strong>
            </div>
            <div class="info-row" v-if="booking.booking_details && booking.booking_details.length">
              <span>Ghế:</span>
              <span class="value">{{ booking.booking_details.map(t => (t.seat?.row || '') + (t.seat?.number || '')).join(', ') }}</span>
            </div>
            <div class="info-row" v-if="booking.booking_combos && booking.booking_combos.length">
              <span>Combo:</span>
              <span class="value">{{ booking.booking_combos.map(c => c.combo?.name).join(', ') }}</span>
            </div>
            
            <div class="divider"></div>
            
            <h3 class="info-title">Thông tin thụ hưởng</h3>
            <div class="info-row" v-if="bankConfig.vietqr_bank_id">
              <span>Ngân hàng:</span>
              <strong>{{ bankConfig.vietqr_bank_id }}</strong>
            </div>
            <div class="info-row" v-if="bankConfig.vietqr_account_name">
              <span>Chủ tài khoản:</span>
              <strong>{{ bankConfig.vietqr_account_name }}</strong>
            </div>
            <div class="info-row" v-if="bankConfig.vietqr_account_no">
              <span>Số tài khoản:</span>
              <strong>{{ bankConfig.vietqr_account_no }}</strong>
            </div>
          </div>

          <div class="actions">
            <template v-if="booking.payment_status !== 'waiting_confirmation'">
              <button class="btn-cinego-main" @click="confirmTransfer" :disabled="checking">
                {{ checking ? 'Đang gửi...' : 'Tôi đã chuyển khoản xong' }}
              </button>
            </template>
            <div v-else class="waiting-alert">
              <i class="fi fi-rr-time-fast"></i> Đơn hàng đang được xác nhận. Vui lòng chờ Admin kiểm duyệt.
            </div>
            <button class="btn-cinego-outline" @click="goBack">Quay lại trang chủ</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/axios';
import { toast } from '../../utils/alert';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const error = ref('');
const booking = ref(null);
const bankConfig = ref({});
const checking = ref(false);

const bookingId = route.query.booking_id;

const formatCurrency = (value) => {
  if (!value) return '0 ₫';
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

const qrUrl = computed(() => {
  if (!bankConfig.value.vietqr_bank_id || !bankConfig.value.vietqr_account_no) return null;
  const amount = booking.value?.total_amount || booking.value?.total_price || 0;
  const addInfo = booking.value?.booking_code ? `Thanh toan ve ${booking.value.booking_code}` : 'Thanh toan ve CineGo';
  const accountName = bankConfig.value.vietqr_account_name || '';
  
  return `https://img.vietqr.io/image/${bankConfig.value.vietqr_bank_id}-${bankConfig.value.vietqr_account_no}-compact2.png?amount=${amount}&addInfo=${encodeURIComponent(addInfo)}&accountName=${encodeURIComponent(accountName)}`;
});

const fetchData = async () => {
  if (!bookingId) {
    error.value = 'Không tìm thấy thông tin mã đơn hàng.';
    loading.value = false;
    return;
  }

  try {
    // Lấy thông tin đơn hàng
    const bookingRes = await api.get(`/bookings/${bookingId}`);
    if (bookingRes.data.success) {
      booking.value = bookingRes.data.data;
    } else {
      throw new Error('Không thể tải thông tin đơn hàng.');
    }

    // Lấy cấu hình VietQR
    const configRes = await api.get('/settings/payment');
    if (configRes.data.success) {
      bankConfig.value = configRes.data.data;
    }

  } catch (err) {
    console.error(err);
    error.value = err.response?.data?.message || 'Có lỗi xảy ra khi tải dữ liệu.';
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.push('/');
};

const confirmTransfer = async () => {
  checking.value = true;
  try {
    const res = await api.patch(`/bookings/${bookingId}/confirm-transfer`);
    if (res.data.success) {
      toast('Đã báo cáo chuyển khoản thành công!', 'success');
      if (booking.value) {
        booking.value.payment_status = 'waiting_confirmation';
      }
    }
  } catch (err) {
    if (err.response?.data?.message) {
      toast(err.response.data.message, 'error');
    } else {
      toast('Có lỗi xảy ra khi xác nhận thanh toán.', 'error');
    }
  } finally {
    checking.value = false;
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.qr-payment-view {
  min-height: 80vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 40px 20px;
}

.qr-container {
  width: 100%;
  max-width: 900px;
  padding: 40px;
  border-radius: 16px;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  margin: 0 auto;
}

.section-title {
  color: var(--accent-red);
  margin-bottom: 30px;
  font-size: 28px;
  font-weight: 700;
}

.text-center {
  text-align: center;
}

.qr-layout {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 40px;
}

@media (max-width: 768px) {
  .qr-layout {
    grid-template-columns: 1fr;
    gap: 20px;
  }
}

.payment-info {
  background: #f8f9fa;
  padding: 25px;
  border-radius: 12px;
  border: 1px solid #e9ecef;
  margin-bottom: 25px;
}

.info-title {
  font-size: 16px;
  font-weight: 700;
  color: #333;
  margin-bottom: 15px;
  margin-top: 0;
}

.divider {
  height: 1px;
  background: #e9ecef;
  margin: 20px 0;
}

.info-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  color: #555;
  font-size: 15px;
}

.info-row:last-child {
  margin-bottom: 0;
}

.info-row strong, .info-row .value {
  color: #222;
  font-weight: 600;
  text-align: right;
  max-width: 65%;
}

.price-highlight {
  color: var(--accent-red) !important;
  font-size: 18px !important;
}

.qr-image-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 20px;
  background: white;
  padding: 15px;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.qr-image {
  max-width: 100%;
  width: 280px;
  height: auto;
  border-radius: 8px;
}

.qr-placeholder {
  height: 250px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: #666;
  font-size: 14px;
}

.instruction {
  background: #fff3f3;
  padding: 20px;
  border-radius: 12px;
  border: 1px dashed #ffcdd2;
}

.instruction p {
  margin: 0 0 10px 0;
  color: #444;
  font-size: 14.5px;
}

.instruction p:last-child {
  margin-bottom: 0;
}

.warning-text {
  color: var(--accent-red) !important;
  margin-top: 15px !important;
  font-style: italic;
  font-weight: 500;
  font-size: 13.5px !important;
}

.actions {
  display: flex;
  gap: 15px;
  flex-direction: column;
}

.btn-cinego-main, .btn-cinego-outline {
  width: 100%;
  padding: 14px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.btn-cinego-main {
  background-color: #e50914 !important;
  color: #ffffff !important;
  border: none;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.2);
}

.btn-cinego-main:hover:not(:disabled) {
  background: #ff1e27;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(229, 9, 20, 0.3);
}

.btn-cinego-main:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.btn-cinego-outline {
  background: white;
  color: #333;
  border: 1px solid #ddd;
}

.btn-cinego-outline:hover {
  background: #f8f9fa;
  border-color: #bbb;
}

.loading-state, .error-state {
  text-align: center;
  color: #555;
  margin-top: 50px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top-color: var(--accent-red);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-state i {
  font-size: 48px;
  color: var(--accent-red);
  margin-bottom: 20px;
}

.error-state .btn-cinego-main {
  width: auto;
  display: inline-block;
  padding: 10px 30px;
  margin-top: 20px;
}

.waiting-alert {
  background: #fff3cd;
  color: #856404;
  padding: 12px 15px;
  border-radius: 8px;
  margin-bottom: 15px;
  text-align: center;
  font-weight: 500;
  border: 1px solid #ffeeba;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
</style>
