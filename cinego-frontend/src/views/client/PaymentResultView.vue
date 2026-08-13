<template>
  <div class="result-view">
    <div class="result-card glass-panel">
      <template v-if="status === 'success'">
        <div class="icon-wrapper success">
          <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
            stroke="#00f5a0" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <h2>THANH TOÁN THÀNH CÔNG!</h2>
        <p class="subtitle">Cảm ơn bạn đã đặt vé tại CineGo.</p>
        <p class="subtitle">Thông tin chi tiết đã được gửi về Gmail của bạn và mã QR để check in khi đến rạp vui lòng đưa mã QR cho staff để kiểm tra.</p>

        <p class="booking-code" v-if="bookingCode">
          Mã đặt vé: <strong>{{ bookingCode }}</strong>
        </p>

        <div class="qr-wrapper" v-if="bookingCode">
          <img :src="qrUrl" alt="QR Code" class="qr-image" />
          <p class="qr-hint">Đưa mã QR này cho nhân viên để nhận vé</p>
        </div>

        <button @click="goHome" class="btn-back">Quay Về Trang Chủ</button>
      </template>

      <template v-else-if="status === 'failed'">
        <div class="icon-wrapper failed">
          <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
            stroke="#ff5555" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </div>
        <h2>THANH TOÁN CHƯA THÀNH CÔNG</h2>
        <p class="subtitle">Đơn hàng chưa được thanh toán. Bạn có thể thanh toán lại bất cứ lúc nào.</p>
        <p class="reason-box" v-if="reason">Lý do: {{ reason }}</p>
        <p class="subtitle" v-if="!reason">Giao dịch không thành công hoặc đã bị hủy.</p>

        <button v-if="bookingId" @click="retryPayment" class="btn-retry" :disabled="retrying">
          <span v-if="retrying">Đang tạo lại thanh toán...</span>
          <span v-else>Thanh toán lại</span>
        </button>
        <p v-if="retryError" class="retry-error">{{ retryError }}</p>
        <button @click="goHome" class="btn-back">Quay Về Trang Chủ</button>
      </template>

      <template v-else-if="status === 'cancelled'">
        <div class="icon-wrapper cancelled">
          <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
            stroke="#fbbf24" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h2>BẠN ĐÃ HỦY THANH TOÁN</h2>
        <p class="subtitle">Đơn hàng <strong>{{ bookingCode }}</strong> đã được hủy và các ghế đã được trả lại.</p>
        <p class="reason-box cancelled" v-if="reason">{{ reason }}</p>
        <button @click="goHistory" class="btn-back">Xem Lịch Sử Giao Dịch</button>
        <button @click="goHome" class="btn-back" style="margin-top: 0;">Quay Về Trang Chủ</button>
      </template>

      <template v-else>
        <div class="icon-wrapper failed">
          <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
            stroke="#ff5555" stroke-width="2.5">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h2>GIAO DỊCH KHÔNG HỢP LỆ</h2>
        <p class="subtitle">Không thể xác thực giao dịch. Vui lòng liên hệ hỗ trợ nếu bạn đã bị trừ tiền.</p>
        <button v-if="bookingId" @click="retryPayment" class="btn-retry" :disabled="retrying">
          <span v-if="retrying">Đang tạo lại thanh toán...</span>
          <span v-else>Thanh toán lại</span>
        </button>
        <p v-if="retryError" class="retry-error">{{ retryError }}</p>
        <button @click="goHome" class="btn-back">Quay Về Trang Chủ</button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../../api/axios";
import { useBookingStore } from "../../stores/booking";

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();

const status = ref("");
const bookingCode = ref("");
const bookingId = ref(null);
const reason = ref("");
const retrying = ref(false);
const retryError = ref("");

onMounted(() => {
  status.value = route.query.status || "invalid";
  bookingCode.value = route.query.booking_code || route.query.code || "";
  bookingId.value = route.query.booking_id || null;
  reason.value = route.query.reason || "";

  bookingStore.clearBooking();
});

const qrUrl = computed(() => {
  if (!bookingCode.value) return '';
  const staffUrl = `${window.location.origin}/staff/dashboard?scan=${encodeURIComponent(bookingCode.value)}`;
  return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(staffUrl)}`;
});

const retryPayment = async () => {
  if (!bookingId.value || retrying.value) return;
  retrying.value = true;
  retryError.value = "";

  try {
    const res = await api.post(`/payments/retry/${bookingId.value}`);
    if (res.data?.payment_url) {
      window.location.href = res.data.payment_url;
      return;
    }
    retryError.value = res.data?.message || "Không thể thanh toán lại. Vui lòng thử lại sau.";
  } catch (err) {
    retryError.value = err.response?.data?.message ||
      "Thời gian giữ ghế đã hết hoặc ghế không còn trống. Vui lòng chọn ghế lại từ đầu.";
  } finally {
    retrying.value = false;
  }
};

const goHome = () => {
  router.push("/");
};

const goHistory = () => {
  router.push("/profile?tab=history");
};
</script>

<style scoped>
.result-view {
  min-height: 60vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.result-card {
  max-width: 480px;
  width: 100%;
  padding: 40px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.icon-wrapper {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-wrapper.success {
  background: rgba(0, 245, 160, 0.1);
  border: 2px dashed var(--accent-mint, #00f5a0);
}

.icon-wrapper.failed {
  background: rgba(255, 85, 85, 0.1);
  border: 2px dashed #ff5555;
}

.icon-wrapper.cancelled {
  background: rgba(251, 191, 36, 0.1);
  border: 2px dashed #fbbf24;
}

.subtitle {
  color: var(--text-secondary, #aaa);
  font-size: 14px;
}

.booking-code {
  font-size: 16px;
  color: var(--text-primary, #fff);
}

.reason-box {
  background: rgba(255, 85, 85, 0.08);
  border: 1px dashed #ff5555;
  color: #ff8080;
  padding: 10px 14px;
  border-radius: 10px;
  font-size: 14px;
  width: 100%;
}

.reason-box.cancelled {
  background: rgba(251, 191, 36, 0.08);
  border-color: #fbbf24;
  color: #fbbf24;
}

.retry-error {
  color: #ff8080;
  font-size: 13px;
}

.btn-retry {
  margin-top: 12px;
  background: linear-gradient(135deg, var(--accent-mint, #00f5a0) 0%, var(--accent-blue, #00bfff) 100%);
  color: #0a0a1a;
  border: none;
  width: 100%;
  padding: 14px;
  font-size: 16px;
  font-weight: 700;
  border-radius: var(--radius-md, 12px);
  cursor: pointer;
}

.btn-retry:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-back {
  margin-top: 12px;
  background: linear-gradient(135deg, var(--accent-pink, #ff007f) 0%, var(--accent-violet, #7000ff) 100%);
  color: white;
  border: none;
  width: 100%;
  padding: 14px;
  font-size: 16px;
  font-weight: 700;
  border-radius: var(--radius-md, 12px);
  cursor: pointer;
}

.qr-wrapper {
  margin-top: 10px;
  background: white;
  padding: 16px;
  border-radius: 16px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.qr-image {
  width: 200px;
  height: 200px;
  object-fit: contain;
}

.qr-hint {
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
}
</style>