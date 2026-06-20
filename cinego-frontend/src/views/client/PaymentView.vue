<template>
  <div class="payment-view" v-if="bookingStore.selectedSeats.length > 0">
    <div
      v-if="bookingSuccess"
      class="success-receipt-container glass-panel animate-fade-in"
    >
      <div class="success-icon-wrapper">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="60"
          height="60"
          viewBox="0 0 24 24"
          fill="none"
          stroke="#00f5a0"
          stroke-width="2.5"
        >
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h2 class="glow-text-mint">ĐẶT VÉ THÀNH CÔNG!</h2>
      <p class="success-subtitle">
        Cảm ơn bạn đã lựa chọn CineGo. Vé của bạn đã được xuất hệ thống.
      </p>

      <div class="ticket-receipt">
        <div class="receipt-header">
          <h3>
            MÃ ĐẶT VÉ: <span class="booking-code">{{ bookingCode }}</span>
          </h3>
          <p>Xuất trình mã này tại quầy vé để nhận vé giấy</p>
        </div>

        <div class="receipt-body">
          <div class="receipt-row">
            <strong>Phim:</strong>
            <span>{{ bookingStore.selectedMovie?.title }}</span>
          </div>
          <div class="receipt-row">
            <strong>Suất chiếu:</strong>
            <span
              >{{ bookingStore.selectedShowtime?.start_time }} |
              {{ bookingStore.selectedShowtime?.date }}</span
            >
          </div>
          <div class="receipt-row">
            <strong>Phòng chiếu:</strong>
            <span>{{ bookingStore.selectedShowtime?.room_name }}</span>
          </div>
          <div class="receipt-row">
            <strong>Ghế ngồi:</strong>
            <span class="seat-highlight">{{
              bookingStore.selectedSeats
                .map((s) => `${s.row}${s.number}`)
                .join(", ")
            }}</span>
          </div>
          <div
            class="receipt-row"
            v-if="bookingStore.selectedCombos.length > 0"
          >
            <strong>Bắp nước:</strong>
            <span>{{
              bookingStore.selectedCombos
                .map((c) => `${c.combo.name} (x${c.quantity})`)
                .join(", ")
            }}</span>
          </div>
          <div class="receipt-row border-top">
            <strong>Tổng tiền thanh toán:</strong>
            <span class="price-highlight">{{
              formatCurrency(bookingStore.totalAmount)
            }}</span>
          </div>
        </div>
      </div>

      <button @click="backToHome" class="btn-primary">Quay Về Trang Chủ</button>
    </div>

    <div v-else class="payment-checkout-grid">
      <div class="checkout-main">
        <section class="combos-section glass-panel">
          <h2 class="section-title gradient-text-accent">
            Chọn Bắp & Nước (Tùy chọn)
          </h2>
          <div class="combos-list">
            <div
              v-for="combo in availableCombos"
              :key="combo.id"
              class="combo-item glass-panel"
            >
              <img :src="combo.image_url" :alt="combo.name" class="combo-img" />
              <div class="combo-info">
                <h3 class="combo-name">{{ combo.name }}</h3>
                <p class="combo-desc">{{ combo.description }}</p>
                <span class="combo-price">{{
                  formatCurrency(combo.price)
                }}</span>
              </div>
              <div class="combo-controls">
                <button
                  @click="bookingStore.removeCombo(combo)"
                  class="ctrl-btn"
                >
                  -
                </button>
                <span class="ctrl-qty">{{ getComboQty(combo.id) }}</span>
                <button @click="bookingStore.addCombo(combo)" class="ctrl-btn">
                  +
                </button>
              </div>
            </div>
          </div>
        </section>

        <section class="payment-methods-section glass-panel">
          <h2 class="section-title gradient-text-accent">
            Phương Thức Thanh Toán
          </h2>
          <div class="methods-grid">
            <label
              v-for="method in paymentMethods"
              :key="method.id"
              class="method-card glass-panel"
              :class="{ active: selectedPaymentMethod === method.id }"
            >
              <input
                type="radio"
                :value="method.id"
                v-model="selectedPaymentMethod"
                name="payment_method"
                class="hidden-radio"
              />
              <span class="method-icon" v-html="method.icon"></span>
              <div class="method-info">
                <span class="method-name">{{ method.name }}</span>
                <span class="method-desc">{{ method.desc }}</span>
              </div>
            </label>
          </div>
        </section>
      </div>

      <div class="checkout-sidebar glass-panel">
        <h2 class="sidebar-title gradient-text-accent">Đơn Hàng CineGo</h2>

        <div class="invoice-details">
          <div class="invoice-group">
            <h4 class="invoice-movie-title">
              {{ bookingStore.selectedMovie?.title }}
            </h4>
            <p class="invoice-meta">
              {{ bookingStore.selectedShowtime?.start_time }} |
              {{ bookingStore.selectedShowtime?.date }} | Phòng
              {{ bookingStore.selectedShowtime?.room_name }}
            </p>
          </div>

          <div class="invoice-divider"></div>

          <div class="invoice-row">
            <span>Vé xem phim (x{{ bookingStore.selectedSeats.length }})</span>
            <span>{{ formatCurrency(bookingStore.subtotalSeats) }}</span>
          </div>
          <div class="invoice-seat-names">
            Ghế:
            {{
              bookingStore.selectedSeats
                .map((s) => `${s.row}${s.number}`)
                .join(", ")
            }}
          </div>

          <template v-if="bookingStore.selectedCombos.length > 0">
            <div
              class="invoice-row"
              v-for="item in bookingStore.selectedCombos"
              :key="item.combo.id"
            >
              <span>{{ item.combo.name }} (x{{ item.quantity }})</span>
              <span>{{
                formatCurrency(item.combo.price * item.quantity)
              }}</span>
            </div>
          </template>

          <div class="invoice-divider"></div>

          <div class="voucher-wrapper">
            <div class="voucher-input-group">
              <input
                v-model="voucherCode"
                type="text"
                placeholder="Nhập mã giảm giá..."
                class="voucher-input"
                :disabled="bookingStore.appliedVoucher"
              />
              <button
                v-if="!bookingStore.appliedVoucher"
                @click="applyVoucher"
                class="btn-apply-voucher"
              >
                Áp Dụng
              </button>
              <button v-else @click="removeVoucher" class="btn-remove-voucher">
                Hủy
              </button>
            </div>
            <p
              v-if="voucherMessage"
              :class="
                voucherSuccess ? 'voucher-msg-success' : 'voucher-msg-error'
              "
            >
              {{ voucherMessage }}
            </p>
          </div>

          <div class="invoice-divider"></div>

          <div class="invoice-row">
            <span>Tạm tính:</span>
            <span>{{ formatCurrency(bookingStore.subtotal) }}</span>
          </div>
          <div
            class="invoice-row text-discount"
            v-if="bookingStore.discountAmount > 0"
          >
            <span>Giảm giá:</span>
            <span>-{{ formatCurrency(bookingStore.discountAmount) }}</span>
          </div>

          <div class="invoice-row invoice-total">
            <span>Tổng cộng:</span>
            <span class="total-price glow-text-pink">{{
              formatCurrency(bookingStore.totalAmount)
            }}</span>
          </div>
        </div>

        <button
          @click="submitBooking"
          :disabled="submitting"
          class="btn-pay-now"
        >
          <span v-if="submitting" class="btn-spinner"></span>
          <span v-else>Thanh Toán Hóa Đơn</span>
        </button>
      </div>
    </div>
  </div>

  <div v-else class="loading-state">
    <p>Giỏ hàng trống hoặc hết hạn giữ ghế!</p>
    <router-link
      to="/"
      class="btn-primary"
      style="margin-top: 20px; display: inline-block"
      >Quay về Trang chủ</router-link
    >
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useBookingStore } from "../../stores/booking";
import api from "../../api/axios";

const router = useRouter();
const bookingStore = useBookingStore();

const submitting = ref(false);
const bookingSuccess = ref(false);
const bookingCode = ref("");
const voucherCode = ref("");
const voucherMessage = ref("");
const voucherSuccess = ref(false);
const selectedPaymentMethod = ref("vnpay");

const formatCurrency = (val) => {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(val);
};

const availableCombos = [
  {
    id: 1,
    name: "Combo Solo Bắp Ngọt",
    description: "1 Bắp lớn vị ngọt + 1 Nước ngọt size L tùy chọn",
    price: 75000,
    image_url:
      "https://images.unsplash.com/photo-1578849278619-e73505e9610f?auto=format&fit=crop&w=150&q=80",
  },
  {
    id: 2,
    name: "Combo Couple Hỗn Hợp",
    description: "1 Bắp lớn vị phô mai/caramel + 2 Nước ngọt size L tùy chọn",
    price: 115000,
    image_url:
      "https://images.unsplash.com/photo-1585647347483-22b66260dfff?auto=format&fit=crop&w=150&q=80",
  },
  {
    id: 3,
    name: "Combo Family Điện Ảnh",
    description: "2 Bắp lớn vị tự chọn + 3 Nước ngọt lớn + 1 Snack khoai tây",
    price: 185000,
    image_url:
      "https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&w=150&q=80",
  },
];

const paymentMethods = [
  {
    id: "vnpay",
    name: "Cổng thanh toán VNPay",
    desc: "Thẻ ATM nội địa, QR-Code ngân hàng hoặc Thẻ Quốc Tế",
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7000ff" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>',
  },
  {
    id: "momo",
    name: "Ví điện tử MoMo",
    desc: "Thanh toán bảo mật tức thì bằng app MoMo siêu tốc",
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff007f" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M8 12h8"></path><path d="M12 8v8"></path></svg>',
  },
];

const getComboQty = (comboId) => {
  const item = bookingStore.selectedCombos.find((c) => c.combo.id === comboId);
  return item ? item.quantity : 0;
};

// Gửi mã voucher lên hệ thống xử lý tính toán
const applyVoucher = async () => {
  voucherMessage.value = "";
  voucherSuccess.value = false;

  if (!voucherCode.value) return;
  const code = voucherCode.value.toUpperCase();

  try {
    const response = await api.post("/vouchers/verify", {
      code,
      subtotal: bookingStore.subtotal,
    });
    bookingStore.applyVoucher(response.data);
    voucherMessage.value = `Áp dụng thành công voucher giảm ${response.data.discount_value}${response.data.discount_type === "percentage" ? "%" : "đ"}!`;
    voucherSuccess.value = true;
  } catch (err) {
    console.warn("Voucher API error, simulating locally.");
    if (code === "CINEGO10") {
      const mockVoucher = {
        code: "CINEGO10",
        discount_type: "percentage",
        discount_value: 10,
        min_spend: 50000,
        max_discount: 50000,
      };
      bookingStore.applyVoucher(mockVoucher);
      voucherMessage.value = "Áp dụng mã CINEGO10 giảm 10% thành công!";
      voucherSuccess.value = true;
    } else {
      voucherMessage.value =
        "Mã giảm giá không chính xác hoặc đã hết lượt dùng!";
      voucherSuccess.value = false;
    }
  }
};

const removeVoucher = () => {
  bookingStore.removeVoucher();
  voucherCode.value = "";
  voucherMessage.value = "";
  voucherSuccess.value = false;
};

// LUỒNG XỬ LÝ CHỐT VÉ CHÍNH THỨC & GỌI CỔNG THANH TOÁN BAO PHỦ LỖI TỪ ĐỐI TÁC THỨ 3
const submitBooking = async () => {
  submitting.value = true;
  try {
    const payload = {
      showtime_id: bookingStore.selectedShowtime.id,
      seat_ids: bookingStore.selectedSeats.map((s) => s.id), // Đồng bộ danh sách chuỗi ID chuẩn của SeatMap
      combos: bookingStore.selectedCombos.map((c) => ({
        id: c.combo.id,
        quantity: c.quantity,
      })),
      voucher_id: bookingStore.appliedVoucher?.id || null,
      payment_method: selectedPaymentMethod.value,
      total_amount: bookingStore.totalAmount,
    };

    const response = await api.post("/bookings", payload);

    // Nếu API trả về đường dẫn URL (Đối với môi trường thật của VNPay / MoMo)
    if (response.data.payment_url) {
      window.location.href = response.data.payment_url;
      return;
    }

    // Trường hợp test môi trường local / API mô phỏng thành công
    bookingCode.value =
      response.data.booking_code ||
      "CG-" + Math.floor(100000 + Math.random() * 900000);
    bookingSuccess.value = true;
  } catch (err) {
    console.error(err);
    alert(
      err.response?.data?.message ||
        "Giao dịch thất bại. Thời gian giữ ghế đã hết hạn, vui lòng thao tác lại!",
    );
    router.push("/");
  } finally {
    submitting.value = false;
  }
};

const backToHome = () => {
  bookingStore.clearBooking();
  router.push("/");
};
</script>

<style scoped>
/* Toàn bộ CSS Scoped nguyên bản từ dự án CineGo của Thắng được giữ nguyên vẹn */
.payment-view {
  min-height: 500px;
}
.payment-checkout-grid {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 30px;
  align-items: start;
}
@media (max-width: 992px) {
  .payment-checkout-grid {
    grid-template-columns: 1fr;
  }
}
.checkout-main {
  display: flex;
  flex-direction: column;
  gap: 30px;
}
.section-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 24px;
}
.combos-section,
.payment-methods-section {
  padding: 30px;
}
.combos-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.combo-item {
  display: flex;
  padding: 16px;
  gap: 20px;
  align-items: center;
}
.combo-img {
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: var(--radius-sm);
  background: var(--bg-tertiary);
}
.combo-info {
  flex: 1;
}
.combo-name {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 4px;
}
.combo-desc {
  color: var(--text-muted);
  font-size: 12px;
  line-height: 1.4;
  margin-bottom: 6px;
}
.combo-price {
  color: var(--accent-pink);
  font-weight: 700;
  font-size: 14px;
}
.combo-controls {
  display: flex;
  align-items: center;
  gap: 14px;
  background: var(--bg-tertiary);
  border: 1px solid var(--border-glass);
  padding: 6px 12px;
  border-radius: var(--radius-full);
}
.ctrl-btn {
  background: transparent;
  color: var(--text-secondary);
  border: none;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  width: 24px;
  height: 24px;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: var(--transition-smooth);
}
.ctrl-btn:hover {
  color: white;
}
.ctrl-qty {
  color: white;
  font-weight: 700;
  font-size: 14px;
  min-width: 20px;
  text-align: center;
}
.methods-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}
.method-card {
  display: flex;
  padding: 20px;
  gap: 16px;
  align-items: center;
  cursor: pointer;
  transition: var(--transition-bounce);
}
.method-card:hover {
  border-color: var(--accent-violet);
  background: rgba(112, 0, 255, 0.05);
}
.method-card.active {
  border-color: var(--accent-pink);
  background: rgba(255, 0, 127, 0.08);
  box-shadow: var(--shadow-neon-pink);
}
.hidden-radio {
  display: none;
}
.method-icon {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--bg-tertiary);
  display: flex;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
}
.method-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.method-name {
  font-size: 14px;
  font-weight: 700;
  color: white;
}
.method-desc {
  color: var(--text-muted);
  font-size: 11px;
  line-height: 1.4;
}
.checkout-sidebar {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.sidebar-title {
  font-size: 22px;
  font-weight: 700;
}
.invoice-details {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.invoice-movie-title {
  font-size: 16px;
  font-weight: 700;
  line-height: 1.4;
}
.invoice-meta {
  color: var(--text-muted);
  font-size: 12px;
  margin-top: 4px;
}
.invoice-divider {
  height: 1px;
  background: var(--border-glass);
  margin: 6px 0;
}
.invoice-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: var(--text-secondary);
}
.invoice-seat-names {
  color: var(--accent-mint);
  font-weight: 700;
  font-size: 12px;
  margin-top: -8px;
}
.voucher-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.voucher-input-group {
  display: flex;
  gap: 10px;
}
.voucher-input {
  flex: 1;
  background: var(--bg-tertiary);
  color: white;
  border: 1px solid var(--border-glass);
  padding: 10px 16px;
  border-radius: var(--radius-sm);
  font-size: 13px;
}
.voucher-input:focus {
  outline: none;
  border-color: var(--accent-pink);
}
.btn-apply-voucher,
.btn-remove-voucher {
  padding: 10px 16px;
  border: none;
  font-weight: 700;
  font-size: 13px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition-smooth);
}
.btn-apply-voucher {
  background: rgba(255, 255, 255, 0.05);
  color: white;
  border: 1px solid var(--border-glass);
}
.btn-apply-voucher:hover {
  background: white;
  color: #0b0816;
}
.btn-remove-voucher {
  background: rgba(230, 0, 0, 0.15);
  color: #ff5555;
  border: 1px solid rgba(230, 0, 0, 0.2);
}
.btn-remove-voucher:hover {
  background: #e60000;
  color: white;
}
.voucher-msg-success {
  color: var(--accent-mint);
  font-size: 12px;
}
.voucher-msg-error {
  color: #ff5555;
  font-size: 12px;
}
.text-discount {
  color: var(--accent-mint);
  font-weight: 600;
}
.invoice-total {
  font-size: 16px;
  font-weight: 700;
  color: white;
  margin-top: 10px;
}
.total-price {
  font-size: 24px;
  font-weight: 800;
}
.btn-pay-now {
  background: linear-gradient(
    135deg,
    var(--accent-pink) 0%,
    var(--accent-violet) 100%
  );
  color: white;
  border: none;
  width: 100%;
  padding: 14px;
  font-size: 16px;
  font-weight: 700;
  border-radius: var(--radius-md);
  cursor: pointer;
  box-shadow: var(--shadow-neon-pink);
  transition: var(--transition-bounce);
  display: flex;
  justify-content: center;
  align-items: center;
}
.btn-pay-now:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 25px rgba(255, 0, 127, 0.5);
}
.btn-pay-now:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s infinite linear;
}
.success-receipt-container {
  max-width: 600px;
  margin: 0 auto;
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
  text-align: center;
}
.success-icon-wrapper {
  width: 100px;
  height: 100px;
  background: rgba(0, 245, 160, 0.1);
  border: 2px dashed var(--accent-mint);
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: var(--shadow-neon-mint);
}
.success-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
  max-width: 420px;
  line-height: 1.5;
}
.ticket-receipt {
  background: var(--bg-secondary);
  border: 1px dashed var(--border-glass);
  border-radius: var(--radius-md);
  padding: 30px;
  width: 100%;
  text-align: left;
  position: relative;
}
.receipt-header {
  border-bottom: 1px dashed var(--border-glass);
  padding-bottom: 20px;
  margin-bottom: 20px;
  text-align: center;
}
.booking-code {
  color: var(--accent-pink);
  font-size: 24px;
  font-weight: 800;
  letter-spacing: 0.05em;
  margin-left: 6px;
}
.receipt-header p {
  color: var(--text-muted);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-top: 6px;
}
.receipt-body {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.receipt-row {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  color: var(--text-secondary);
}
.receipt-row strong {
  color: var(--text-primary);
  font-weight: 600;
}
.seat-highlight {
  color: var(--accent-mint);
  font-weight: 700;
}
.price-highlight {
  font-size: 18px;
  font-weight: 800;
  color: var(--accent-pink);
}
.animate-fade-in {
  animation: fadeIn 0.6s ease-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 80px;
}
</style>
