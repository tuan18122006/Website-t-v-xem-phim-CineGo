<template>
  <div class="payment-view" v-if="bookingStore.selectedSeats.length > 0">
    <!-- MÀN HÌNH ĐẶT VÉ THÀNH CÔNG -->
    <div v-if="bookingSuccess" class="success-receipt-container glass-panel animate-fade-in">
      <div class="success-icon-wrapper">
        <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#00f5a0"
          stroke-width="2.5">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
      </div>
      <h2 class="success-title" style="color: var(--text-primary);">ĐẶT VÉ THÀNH CÔNG!</h2>
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
            <span>{{ bookingStore.selectedShowtime?.start_time }} | {{ bookingStore.selectedShowtime?.date }}</span>
          </div>
          <div class="receipt-row">
            <strong>Phòng chiếu:</strong>
            <span>{{ bookingStore.selectedShowtime?.room_name }}</span>
          </div>
          <div class="receipt-row">
            <strong>Ghế ngồi:</strong>
            <span class="seat-highlight">
              {{bookingStore.selectedSeats.map((s) => `${s.row}${s.number}`).join(", ")}}
            </span>
          </div>
          <div class="receipt-row" v-if="bookingStore.selectedCombos.length > 0">
            <strong>Bắp nước mua thêm:</strong>
            <span>
              {{bookingStore.selectedCombos.map((c) => `${c.combo.name} (x${c.quantity})`).join(", ")}}
            </span>
          </div>
          <div class="receipt-row" v-if="selectedGifts.length > 0">
            <strong>Bắp nước quà tặng:</strong>
            <span>
              {{selectedGifts.map((g) => g.name).join(", ")}}
            </span>
          </div>
          <div class="receipt-row border-top">
            <strong>Tổng tiền thanh toán:</strong>
            <span class="price-highlight">{{ formatCurrency(bookingStore.totalAmount) }}</span>
          </div>
        </div>
      </div>

      <button @click="backToHome" class="btn-pay-now" style="margin-top: 20px; max-width: 300px;">
        Quay Về Trang Chủ
      </button>
    </div>

    <!-- MÀN HÌNH THANH TOÁN & CHỌN COMBO -->
    <div v-else class="payment-checkout-grid">
      <div class="checkout-main">
        <section class="combos-section glass-panel">
          <!-- THANH TAB CHUYỂN ĐỔI -->
          <div class="combo-tabs">
            <button type="button" :class="['tab-btn', { active: activeComboTab === 'buy' }]"
              @click="activeComboTab = 'buy'">
              🍿 Mua Bắp Nước
            </button>
            <button type="button" :class="['tab-btn', { active: activeComboTab === 'wallet' }]"
              @click="activeComboTab = 'wallet'">
              🎁 Ví Quà Tặng ({{ walletCombos.length }})
            </button>
          </div>

          <!-- TAB 1: MUA MỚI BẰNG TIỀN -->
          <div v-if="activeComboTab === 'buy'">
            <template v-if="loadingCombos">
              <div v-for="i in 3" :key="i" class="combo-item glass-panel skeleton-card">
                <div class="skeleton skeleton-img"></div>
                <div class="combo-info">
                  <div class="skeleton skeleton-title"></div>
                  <div class="skeleton skeleton-text"></div>
                  <div class="skeleton skeleton-price"></div>
                </div>
                <div class="combo-action">
                  <div class="skeleton skeleton-btn"></div>
                  <div class="skeleton skeleton-stock"></div>
                </div>
              </div>
            </template>

            <div v-else class="combos-list">
              <div v-for="combo in availableCombos" :key="combo.id" class="combo-item glass-panel">
                <img :src="getComboImageUrl(combo.image_url)" :alt="combo.name" class="combo-img"
                  @error="handleComboImageError" />

                <div class="combo-info">
                  <h3 class="combo-name">{{ combo.name }}</h3>
                  <p class="combo-desc">{{ combo.description }}</p>
                  <span class="combo-price">{{ formatCurrency(combo.price) }}</span>
                </div>

                <div class="combo-action">
                  <div class="combo-controls">
                    <button @click="bookingStore.removeCombo(combo)" class="ctrl-btn">-</button>
                    <span class="ctrl-qty">{{ getComboQty(combo.id) }}</span>
                    <button @click="bookingStore.addCombo(combo)" class="ctrl-btn" :disabled="isMaxStock(combo)"
                      :title="isMaxStock(combo) ? 'Đã đạt số lượng tồn kho' : ''">
                      +
                    </button>
                  </div>
                  <small :class="{
                    'stock-info': getRemainingStock(combo) > 3,
                    'stock-low': getRemainingStock(combo) <= 3 && !isMaxStock(combo),
                    'stock-warning': isMaxStock(combo)
                  }">
                    {{ isMaxStock(combo) ? 'Đã đạt số lượng tối đa' : `Còn lại ${getRemainingStock(combo)} Combo` }}
                  </small>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB 2: VÍ QUÀ TẶNG -->
          <div v-if="activeComboTab === 'wallet'">
            <div v-if="walletCombos.length === 0" class="empty-wallet-msg">
              <p>Bạn không có Combo quà tặng nào khả dụng trong ví.</p>
            </div>

            <div v-else class="combos-list">
              <div v-for="gift in walletCombos" :key="gift.user_combo_id"
                :class="['combo-item glass-panel gift-card', { selected: isGiftSelected(gift.user_combo_id) }]">
                <img :src="getComboImageUrl(gift.image_url)" :alt="gift.name" class="combo-img"
                  @error="handleComboImageError" />

                <div class="combo-info">
                  <div style="margin-bottom: 4px;">
                    <span class="badge-free">MIỄN PHÍ</span>
                  </div>
                  <h3 class="combo-name">{{ gift.name }}</h3>
                  <p class="combo-desc">{{ gift.description }}</p>
                  <small class="gift-meta">
                    Mã: <strong>{{ gift.code }}</strong> |
                    HSD: {{ formatDate(gift.end_date) }}
                  </small>
                </div>

                <div class="combo-action">
                  <button type="button" class="btn-toggle-gift"
                    :class="{ 'btn-selected': isGiftSelected(gift.user_combo_id) }" @click="toggleGiftCombo(gift)">
                    {{ isGiftSelected(gift.user_combo_id) ? 'Bỏ chọn' : 'Dùng ngay' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- PHƯƠNG THỨC THANH TOÁN -->
        <section class="payment-methods-section glass-panel">
          <h2 class="section-title gradient-text-accent">Phương Thức Thanh Toán</h2>
          <div class="methods-grid">
            <label v-for="method in paymentMethods" :key="method.id" class="method-card glass-panel"
              :class="{ active: selectedPaymentMethod === method.id }">
              <input type="radio" :value="method.id" v-model="selectedPaymentMethod" name="payment_method"
                class="hidden-radio" />
              <span class="method-icon" v-html="method.icon"></span>
              <div class="method-info">
                <span class="method-name">{{ method.name }}</span>
                <span class="method-desc">{{ method.desc }}</span>
              </div>
            </label>
          </div>
        </section>
      </div>

      <!-- SIDEBAR HÓA ĐƠN -->
      <div class="checkout-sidebar glass-panel">
        <h2 class="sidebar-title gradient-text-accent">Đơn Hàng CineGo</h2>

        <div class="invoice-details">
          <div class="invoice-group">
            <h4 class="invoice-movie-title">{{ bookingStore.selectedMovie?.title }}</h4>
            <p class="invoice-meta">
              {{ bookingStore.selectedShowtime?.start_time }} | {{ bookingStore.selectedShowtime?.date }} | Phòng {{
                bookingStore.selectedShowtime?.room_name }}
            </p>
          </div>

          <div class="invoice-divider"></div>

          <div class="invoice-row">
            <span>Vé xem phim (x{{ bookingStore.selectedSeats.length }})</span>
            <span>{{ formatCurrency(bookingStore.subtotalSeats) }}</span>
          </div>
          <div class="invoice-seat-names">
            Ghế: {{bookingStore.selectedSeats.map((s) => `${s.row}${s.number}`).join(", ")}}
          </div>

          <!-- Combo mua bằng tiền -->
          <template v-if="bookingStore.selectedCombos.length > 0">
            <div class="invoice-row" v-for="item in bookingStore.selectedCombos" :key="item.combo.id">
              <span>{{ item.combo.name }} (x{{ item.quantity }})</span>
              <span>{{ formatCurrency(item.combo.price * item.quantity) }}</span>
            </div>
          </template>

          <!-- Combo quà tặng từ ví -->
          <template v-if="selectedGifts.length > 0">
            <div class="invoice-row text-mint" v-for="gift in selectedGifts" :key="gift.user_combo_id">
              <span>🎁 {{ gift.name }} (Quà tặng)</span>
              <span>0đ</span>
            </div>
          </template>

          <div class="invoice-divider"></div>

          <!-- MÃ GIẢM GIÁ -->
          <div class="voucher-wrapper">
            <div class="voucher-input-group">
              <input v-model="voucherCode" type="text" placeholder="Nhập mã giảm giá..." class="voucher-input"
                :disabled="bookingStore.appliedVoucher" />
              <button v-if="!bookingStore.appliedVoucher" @click="applyVoucher" class="btn-apply-voucher">
                Áp Dụng
              </button>
              <button v-else @click="removeVoucher" class="btn-remove-voucher">
                Hủy
              </button>
            </div>
            <p v-if="voucherMessage" :class="voucherSuccess ? 'voucher-msg-success' : 'voucher-msg-error'">
              {{ voucherMessage }}
            </p>
          </div>

          <div class="invoice-divider"></div>

          <div class="invoice-row">
            <span>Tạm tính:</span>
            <span>{{ formatCurrency(bookingStore.subtotal) }}</span>
          </div>
          <div class="invoice-row text-discount" v-if="bookingStore.appliedVoucher && bookingStore.discountAmount > 0">
            <span>Giảm giá:</span>
            <span>- {{ Number(bookingStore.discountAmount).toLocaleString('vi-VN') }}đ</span>
          </div>

          <div class="invoice-row invoice-total">
            <span>Tổng cộng:</span>
            <span class="total-price">{{ formatCurrency(bookingStore.totalAmount) }}</span>
          </div>
        </div>

        <button type="button" @click="handlePaymentAction" class="btn-pay-now" :disabled="submitting">
          <span v-if="submitting" class="btn-spinner"></span>
          <span v-else>Thanh Toán Hóa Đơn</span>
        </button>

        <button type="button" :disabled="submitting" @click="goBackToSeats" class="btn-cancel-payment">
          Quay Lại Chọn Ghế
        </button>
      </div>
    </div>
  </div>

  <div v-else class="loading-state">
    <p>Giỏ hàng trống hoặc hết hạn giữ ghế!</p>
    <router-link to="/" class="btn-pay-now" style="margin-top: 20px; display: inline-flex; max-width: 300px;">
      Quay về Trang chủ
    </router-link>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useBookingStore } from "../../stores/booking";
import { useAuthStore } from "../../stores/auth";
import api from "../../api/axios";

// Khai báo cấu hình Backend & Ảnh mặc định
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || "http://127.0.0.1:8000";
const fallbackComboImage = "/images/default-combo.png";

const router = useRouter();
const bookingStore = useBookingStore();
const authStore = useAuthStore();

const submitting = ref(false);
const bookingSuccess = ref(false);
const bookingCode = ref("");
const voucherCode = ref("");
const voucherMessage = ref("");
const voucherSuccess = ref(false);
const selectedPaymentMethod = ref("vnpay");
const activeComboTab = ref("buy");
const walletCombos = ref([]);
const selectedGifts = ref([]);
const availableCombos = ref([]);
const loadingCombos = ref(true);

const formatCurrency = (val) => {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(val || 0);
};

const goBackToSeats = () => {
  router.back();
};

const fetchWalletCombos = async () => {
  try {
    const res = await api.get("/user/available-combos");
    const rawData = res.data?.data || [];

    walletCombos.value = rawData.map((item) => {
      const expireDate =
        item.end_date ||
        item.expiry_date ||
        item.expired_at ||
        item.expires_at ||
        item.valid_until ||
        item.combo?.end_date ||
        null;

      // Ưu tiên lấy đúng ID của bản ghi bảng user_combos
      const realUserComboId = item.id || item.user_combo_id;

      return {
        ...item,
        user_combo_id: Number(realUserComboId), 
        end_date: expireDate
      };
    });
  } catch (err) {
    console.error("Lỗi khi tải combo từ ví:", err);
  }
};

const fetchCombos = async () => {
  loadingCombos.value = true;
  try {
    const res = await api.get("/combos/active");
    const rawCombos = res.data?.data || [];

    availableCombos.value = rawCombos.filter(item => 
      item.is_sellable === 1 || 
      item.is_sellable === true || 
      item.is_sellable === "1"
    );
  } catch (err) {
    console.error("Lỗi khi tải combo:", err);
  } finally {
    loadingCombos.value = false;
  }
};

const toggleGiftCombo = (gift) => {
  const targetId = gift.user_combo_id;
  const index = selectedGifts.value.findIndex(
    (item) => item.user_combo_id === targetId
  );

  if (index > -1) {
    selectedGifts.value.splice(index, 1);
  } else {
    selectedGifts.value.push(gift);
  }
};

const isGiftSelected = (userComboId) => {
  if (!userComboId) return false;
  return selectedGifts.value.some((item) => item.user_combo_id === userComboId);
};

const getComboImageUrl = (imageUrl) => {
  if (!imageUrl) return fallbackComboImage;
  if (imageUrl.startsWith("blob:") || imageUrl.startsWith("data:")) return imageUrl;
  if (imageUrl.startsWith("http://localhost/storage/")) {
    return imageUrl.replace("http://localhost", BACKEND_URL);
  }
  if (imageUrl.startsWith("http://") || imageUrl.startsWith("https://")) return imageUrl;
  if (imageUrl.startsWith("/storage/")) return `${BACKEND_URL}${imageUrl}`;
  if (imageUrl.startsWith("storage/")) return `${BACKEND_URL}/${imageUrl}`;
  return `${BACKEND_URL}/storage/${imageUrl}`;
};

const handleComboImageError = (event) => {
  event.target.onerror = null;
  event.target.src = fallbackComboImage;
};

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

const getRemainingStock = (combo) => {
  const item = bookingStore.selectedCombos.find((c) => c.combo.id === combo.id);
  const selectedQty = item ? item.quantity : 0;
  return combo.stock - selectedQty;
};

const isMaxStock = (combo) => {
  const item = bookingStore.selectedCombos.find((c) => c.combo.id === combo.id);
  if (!item) return false;
  return item.quantity >= combo.stock;
};

const applyVoucher = async () => {
  voucherMessage.value = "";
  voucherSuccess.value = false;

  try {
    const response = await api.post("/vouchers/verify", {
      code: voucherCode.value,
      user_id: authStore.user?.id,
      subtotal: bookingStore.subtotal,
      movie_id: bookingStore.selectedMovie?.id || null,
    });

    if (response.data) {
      bookingStore.applyVoucher(response.data);
      voucherSuccess.value = true;
      voucherMessage.value = "Áp dụng mã giảm giá thành công!";
    }
  } catch (error) {
    console.error("Lỗi áp dụng voucher:", error);
    voucherSuccess.value = false;
    voucherMessage.value =
      error.response?.data?.message || "Mã giảm giá không hợp lệ hoặc đã hết hạn.";
  }
};

const removeVoucher = () => {
  bookingStore.removeVoucher();
  voucherCode.value = "";
  voucherMessage.value = "";
  voucherSuccess.value = false;
};

const handlePaymentAction = () => {
  confirmPayment();
};

const formatDate = (dateString) => {
  // Kiểm tra nếu không có dữ liệu
  if (!dateString || dateString === 'null' || dateString === 'undefined') {
    return "Không thời hạn";
  }

  // Chuẩn hóa định dạng chuỗi ISO cho chuẩn Javascript Date
  let safeDateString = dateString;
  if (typeof dateString === 'string') {
    // Thay khoảng trắng giữa Ngày và Giờ thành chữ 'T' nếu có (VD: "2026-08-21 17:22:00" -> "2026-08-21T17:22:00")
    safeDateString = dateString.replace(' ', 'T');
  }

  const date = new Date(safeDateString);

  // Nếu vẫn không parse được thì fallback kiểm tra trực tiếp
  if (isNaN(date.getTime())) {
    return "Không thời hạn";
  }

  // Định dạng hiển thị: DD/MM/YYYY HH:mm
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${day}/${month}/${year} ${hours}:${minutes}`;
};
const confirmPayment = async () => {
  submitting.value = true;

  try {
    const payload = {
      showtime_id: bookingStore.selectedShowtime?.id,
      seat_ids: bookingStore.selectedSeats.map((seat) => seat.id),
      combos: bookingStore.selectedCombos.map((item) => ({
        id: item.combo.id,
        quantity: item.quantity,
      })),
      used_user_combo_ids: selectedGifts.value.map((g) => g.user_combo_id),
      voucher_id: bookingStore.appliedVoucher?.id || null,
      payment_method: selectedPaymentMethod.value,
      total_amount: bookingStore.totalAmount,
    };

    const response = await api.post("/payments/create", payload);

    if (response.data?.payment_url) {
      window.location.href = response.data.payment_url;
      return;
    }

    bookingSuccess.value = true;
    bookingCode.value =
      response.data?.booking_code || response.data?.data?.booking_code || "";
  } catch (err) {
    console.error("Lỗi thanh toán:", err.response?.data || err);
    alert(err.response?.data?.message || "Giao dịch thất bại. Vui lòng thử lại!");
  } finally {
    submitting.value = false;
  }
};

const backToHome = () => {
  bookingStore.clearBooking();
  router.push("/");
};

onMounted(() => {
  fetchCombos();
  fetchWalletCombos();
});
</script>

<style scoped>
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

.btn-cancel-payment {
  width: 100%;
  padding: 12px;
  background-color: transparent;
  color: #888888;
  border: 1px solid #cccccc;
  border-radius: 6px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-top: 8px;
}

.btn-cancel-payment:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-cancel-payment:not(:disabled):hover {
  background-color: #fcfcfc;
  color: #333333;
  border-color: #888888;
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
  color: var(--text-primary);
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
  color: var(--text-primary);
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
  color: var(--text-primary);
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
  background: transparent;
  color: var(--accent-pink);
  border: 1px solid var(--accent-pink);
}

.btn-apply-voucher:hover {
  background: var(--accent-pink);
  color: white;
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

.text-discount,
.text-mint {
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
  color: var(--accent-pink);
}

.btn-pay-now {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
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

.combo-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  min-width: 170px;
}

.stock-info {
  color: #16a34a;
}

.stock-low {
  color: #f59e0b;
  font-weight: 600;
}

.stock-warning {
  color: #ef4444;
  font-weight: 700;
}

.skeleton-card {
  display: flex;
  align-items: center;
  gap: 18px;
}

.skeleton {
  border-radius: 8px;
  background: linear-gradient(90deg, #ececec 25%, #f7f7f7 37%, #ececec 63%);
  background-size: 400% 100%;
  animation: skeleton-loading 1.4s ease infinite;
}

.skeleton-img {
  width: 90px;
  height: 90px;
}

.skeleton-title {
  width: 180px;
  height: 18px;
  margin-bottom: 12px;
}

.skeleton-text {
  width: 250px;
  height: 14px;
  margin-bottom: 12px;
}

.skeleton-price {
  width: 90px;
  height: 18px;
}

.skeleton-btn {
  width: 120px;
  height: 42px;
  border-radius: 25px;
  margin-bottom: 12px;
}

.skeleton-stock {
  width: 100px;
  height: 14px;
}

@keyframes skeleton-loading {
  0% {
    background-position: 100% 50%;
  }

  100% {
    background-position: 0 50%;
  }
}

.combo-tabs {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  border-bottom: 1px solid var(--border-glass);
  padding-bottom: 10px;
}

.tab-btn {
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 15px;
  font-weight: 700;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: var(--transition-smooth);
}

.tab-btn.active {
  color: var(--accent-pink);
  background: rgba(255, 0, 127, 0.1);
}

.gift-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  margin-bottom: 12px;
  border: 1px dashed var(--border-glass);
}

.gift-card.selected {
  border-color: var(--accent-mint);
  background: rgba(0, 245, 160, 0.05);
}

.badge-free {
  display: inline-block;
  background: rgba(0, 245, 160, 0.15);
  color: #00b874;
  border: 1px solid rgba(0, 245, 160, 0.4);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  padding: 3px 8px;
  border-radius: 6px;
}

.btn-toggle-gift {
  padding: 8px 16px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--accent-pink);
  background: transparent;
  color: var(--accent-pink);
  font-weight: 700;
  cursor: pointer;
}

.btn-toggle-gift.btn-selected {
  background: #6c757d;
  border-color: #6c757d;
  color: #fff;
}

.empty-wallet-msg {
  text-align: center;
  color: var(--text-muted);
  padding: 20px;
}
</style>