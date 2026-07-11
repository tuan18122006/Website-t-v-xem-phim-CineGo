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
      </template>

      <template v-else-if="status === 'failed'">
        <div class="icon-wrapper failed">
          <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none"
            stroke="#ff5555" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </div>
        <h2>THANH TOÁN THẤT BẠI</h2>
        <p class="subtitle">Giao dịch không thành công hoặc đã bị hủy. Vui lòng thử lại.</p>
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
      </template>

      <button @click="goHome" class="btn-back">Quay Về Trang Chủ</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

const route = useRoute();
const router = useRouter();

const status = ref("");
const bookingCode = ref("");

onMounted(() => {
  status.value = route.query.status || "invalid";
  bookingCode.value = route.query.code || "";
});

const qrUrl = computed(() => {
  if (!bookingCode.value) return '';
  const staffUrl = `${window.location.origin}/staff/dashboard?scan=${encodeURIComponent(bookingCode.value)}`;
  return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(staffUrl)}`;
});

const goHome = () => {
  router.push("/");
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

.subtitle {
  color: var(--text-secondary, #aaa);
  font-size: 14px;
}

.booking-code {
  font-size: 16px;
  color: var(--text-primary, #fff);
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