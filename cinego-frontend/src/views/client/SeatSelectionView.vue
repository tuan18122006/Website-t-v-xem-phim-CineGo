<template>
  <div class="seat-selection-view" v-if="bookingStore.selectedShowtime">
    <div class="selection-grid-container glass-panel">
      <!-- Màn hình rạp -->
      <div class="screen-wrapper">
        <div class="screen-curved">MÀN HÌNH</div>
        <div class="screen-glow"></div>
      </div>

      <!-- Sơ đồ ghế -->
      <div class="seats-map">
        <div class="seats-row" v-for="row in rows" :key="row">
          <span class="row-label">{{ row }}</span>
          
          <div class="seats-list">
            <button 
              v-for="col in 12" 
              :key="col"
              class="seat-btn"
              :class="getSeatClasses(row, col)"
              :disabled="isSeatSold(row, col)"
              @click="handleSeatClick(row, col)"
            >
              {{ col }}
            </button>
          </div>
          
          <span class="row-label">{{ row }}</span>
        </div>
      </div>

      <!-- Chú thích loại ghế -->
      <div class="seats-legend">
        <div class="legend-item"><span class="legend-box seat-available"></span> Standard (75k)</div>
        <div class="legend-item"><span class="legend-box seat-vip"></span> VIP (95k)</div>
        <div class="legend-item"><span class="legend-box seat-couple"></span> Couple (140k)</div>
        <div class="legend-item"><span class="legend-box seat-selected"></span> Đang chọn</div>
        <div class="legend-item"><span class="legend-box seat-held"></span> Đang giữ (khóa)</div>
        <div class="legend-item"><span class="legend-box seat-sold"></span> Đã bán</div>
      </div>
    </div>

    <!-- Tóm tắt đơn hàng bên phải / dưới -->
    <div class="booking-sidebar glass-panel">
      <h2 class="sidebar-title gradient-text-accent">Thông Tin Vé</h2>
      
      <div class="summary-details">
        <div class="summary-row">
          <span class="sum-label">Phim:</span>
          <span class="sum-value">{{ bookingStore.selectedMovie?.title }}</span>
        </div>
        <div class="summary-row">
          <span class="sum-label">Lịch chiếu:</span>
          <span class="sum-value">{{ bookingStore.selectedShowtime?.start_time }} | {{ bookingStore.selectedShowtime?.date }}</span>
        </div>
        <div class="summary-row">
          <span class="sum-label">Phòng chiếu:</span>
          <span class="sum-value">{{ bookingStore.selectedShowtime?.room_name }}</span>
        </div>
        <div class="summary-row border-top">
          <span class="sum-label">Ghế chọn:</span>
          <span class="sum-value seat-names">
            {{ bookingStore.selectedSeats.map(s => `${s.row}${s.number}`).join(', ') || 'Chưa chọn ghế' }}
          </span>
        </div>
      </div>

      <!-- Hiển thị đồng hồ đếm ngược -->
      <div v-if="bookingStore.holdExpiresAt && bookingStore.selectedSeats.length > 0" class="timer-card pulse-active">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="timer-icon"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        <div class="timer-text">
          <span class="timer-desc">Vui lòng hoàn tất trong</span>
          <span class="timer-countdown">{{ countdownText }}</span>
        </div>
      </div>

      <div class="price-summary">
        <div class="price-row">
          <span>Tạm tính ghế:</span>
          <span class="price-value">{{ formatCurrency(bookingStore.subtotalSeats) }}</span>
        </div>
      </div>

      <button 
        @click="proceedToPayment" 
        :disabled="bookingStore.selectedSeats.length === 0" 
        class="btn-checkout"
      >
        Tiếp Tục
      </button>
    </div>
  </div>
  <div v-else class="loading-state">
    <p>Không có thông tin suất chiếu! Vui lòng quay lại chọn phim.</p>
    <router-link to="/" class="btn-primary" style="margin-top: 20px; display: inline-block;">Quay về Trang chủ</router-link>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useBookingStore } from '../../stores/booking';
import api from '../../api/axios';

const router = useRouter();
const bookingStore = useBookingStore();

const rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J']; // Hàng J là Couple
const soldSeats = ref([]); // Danh sách các ghế đã bán (dạng 'row-number' ví dụ 'B-5')
const heldSeats = ref([]); // Danh sách các ghế đang bị người khác giữ

const countdownText = ref('10:00');
let timerInterval = null;

const formatCurrency = (val) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const getSeatType = (row) => {
  if (row === 'J') return 'couple';
  if (['F', 'G', 'H'].includes(row)) return 'vip';
  return 'standard';
};

const getSeatPrice = (type) => {
  if (type === 'couple') return 140000;
  if (type === 'vip') return 95000;
  return 75000;
};

const isSeatSold = (row, col) => {
  return soldSeats.value.includes(`${row}-${col}`);
};

const isSeatHeldByOthers = (row, col) => {
  return heldSeats.value.includes(`${row}-${col}`);
};

const isSeatSelected = (row, col) => {
  return bookingStore.selectedSeats.some(s => s.row === row && s.number === col);
};

const getSeatClasses = (row, col) => {
  const type = getSeatType(row);
  const sold = isSeatSold(row, col);
  const held = isSeatHeldByOthers(row, col);
  const selected = isSeatSelected(row, col);
  
  return {
    'seat-standard': type === 'standard' && !sold && !held && !selected,
    'seat-vip': type === 'vip' && !sold && !held && !selected,
    'seat-couple': type === 'couple' && !sold && !held && !selected,
    'seat-sold': sold,
    'seat-held': held && !selected,
    'seat-selected': selected
  };
};

const handleSeatClick = async (row, col) => {
  const type = getSeatType(row);
  const price = getSeatPrice(type);
  
  const seatId = `${row}-${col}`;
  const seatObj = {
    id: seatId,
    row,
    number: col,
    type,
    price
  };

  const isSelected = isSeatSelected(row, col);

  try {
    if (!isSelected) {
      // Gọi API giữ ghế tạm thời
      try {
        await api.post('/seat-holds', {
          showtime_id: bookingStore.selectedShowtime.id,
          seat_row: row,
          seat_number: col
        });
      } catch (err) {
        console.warn('API Seat Hold not running or failed. Simulating local hold.');
      }
      
      // Toggle ghế vào Store
      bookingStore.toggleSeat(seatObj);
      
      // Nếu là ghế đầu tiên được chọn -> thiết lập bộ đếm 10 phút
      if (bookingStore.selectedSeats.length === 1) {
        bookingStore.setHoldExpiry(10);
        startTimer();
      }
    } else {
      // Hủy chọn ghế
      try {
        await api.post('/seat-holds/release', {
          showtime_id: bookingStore.selectedShowtime.id,
          seat_row: row,
          seat_number: col
        });
      } catch (err) {
        console.warn('API Seat Release failure. Simulating locally.');
      }
      bookingStore.toggleSeat(seatObj);
      
      // Nếu hủy hết ghế -> xóa bộ đếm
      if (bookingStore.selectedSeats.length === 0) {
        bookingStore.holdExpiresAt = null;
        stopTimer();
      }
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Có lỗi xảy ra khi giữ ghế. Ghế có thể vừa được giữ bởi người khác!');
  }
};

const updateTimer = () => {
  if (bookingStore.holdExpiresAt) {
    const diff = bookingStore.holdExpiresAt - Date.now();
    if (diff <= 0) {
      countdownText.value = '00:00';
      stopTimer();
      bookingStore.clearBooking();
      alert('Hết thời gian giữ ghế. Vui lòng đặt vé lại!');
      router.push('/');
    } else {
      const minutes = Math.floor(diff / 60000);
      const seconds = Math.floor((diff % 60000) / 1000);
      countdownText.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }
  } else {
    stopTimer();
  }
};

const startTimer = () => {
  stopTimer();
  updateTimer();
  timerInterval = setInterval(updateTimer, 1000);
};

const stopTimer = () => {
  if (timerInterval) {
    clearInterval(timerInterval);
    timerInterval = null;
  }
};

const fetchSeatStatus = async () => {
  try {
    const response = await api.get(`/showtimes/${bookingStore.selectedShowtime.id}/seats`);
    soldSeats.value = response.data.sold_seats || [];
    heldSeats.value = response.data.held_seats || [];
  } catch (err) {
    console.warn('Fetch seats API error, using mock seat statuses:');
    // Mock sold seats (Ghế đã bán)
    soldSeats.value = ['A-5', 'A-6', 'D-11', 'F-7', 'F-8', 'H-1', 'J-3', 'J-4'];
    // Mock held seats (Ghế đang bị giữ bởi user khác)
    heldSeats.value = ['C-2', 'G-10', 'B-10'];
  }
};

onMounted(() => {
  fetchSeatStatus();
  if (bookingStore.holdExpiresAt) {
    startTimer();
  }
});

onUnmounted(() => {
  stopTimer();
});

const proceedToPayment = () => {
  if (bookingStore.selectedSeats.length > 0) {
    router.push('/booking/payment');
  }
};
</script>

<style scoped>
.seat-selection-view {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 30px;
  align-items: start;
}

@media (max-width: 992px) {
  .seat-selection-view {
    grid-template-columns: 1fr;
  }
}

.selection-grid-container {
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  overflow-x: auto;
  width: 100%;
}

.screen-wrapper {
  perspective: 400px;
  margin-bottom: 60px;
  width: 80%;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.screen-curved {
  width: 100%;
  height: 24px;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 50% / 100% 100% 0 0;
  transform: rotateX(-15deg);
  box-shadow: 0 10px 40px rgba(255, 255, 255, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  color: #0b0816;
  font-family: var(--font-display);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.8em;
  padding-left: 0.8em;
}

.screen-glow {
  width: 80%;
  height: 60px;
  background: radial-gradient(ellipse at top, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
  margin-top: 4px;
  filter: blur(8px);
}

.seats-map {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 40px;
  min-width: 620px;
}

.seats-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.row-label {
  color: var(--text-muted);
  font-family: var(--font-display);
  font-weight: 700;
  width: 20px;
  text-align: center;
}

.seats-list {
  display: flex;
  gap: 8px;
}

.seat-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  color: white;
  border: none;
  cursor: pointer;
  transition: var(--transition-bounce);
  display: flex;
  justify-content: center;
  align-items: center;
}

.seat-btn:hover:not(:disabled) {
  transform: scale(1.15);
}

/* Các loại ghế */
.seat-standard {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.seat-standard:hover {
  background: rgba(255, 255, 255, 0.2);
}

.seat-vip {
  background: rgba(112, 0, 255, 0.2);
  border: 1px solid rgba(112, 0, 255, 0.5);
  box-shadow: 0 0 5px rgba(112, 0, 255, 0.1);
}
.seat-vip:hover {
  background: rgba(112, 0, 255, 0.4);
}

.seat-couple {
  background: rgba(255, 0, 127, 0.2);
  border: 1px solid rgba(255, 0, 127, 0.5);
  width: 72px; /* Ghế đôi rộng gấp đôi */
  box-shadow: 0 0 5px rgba(255, 0, 127, 0.1);
}
.seat-couple:hover {
  background: rgba(255, 0, 127, 0.4);
}

.seat-selected {
  background: var(--accent-mint) !important;
  color: #0b0816 !important;
  border: none !important;
  box-shadow: var(--shadow-neon-mint) !important;
}

.seat-held {
  background: #ff5500;
  border: none;
  cursor: not-allowed;
  opacity: 0.6;
}

.seat-sold {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  color: var(--text-muted);
  cursor: not-allowed;
  opacity: 0.3;
}

.seats-legend {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
  border-top: 1px solid var(--border-glass);
  padding-top: 24px;
  width: 100%;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
}

.legend-box {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  display: inline-block;
}

.booking-sidebar {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sidebar-title {
  font-size: 22px;
  font-weight: 700;
}

.summary-details {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.sum-label {
  color: var(--text-muted);
  font-size: 13px;
}

.sum-value {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 14px;
  text-align: right;
}

.seat-names {
  color: var(--accent-mint);
  font-weight: 700;
}

.border-top {
  border-top: 1px solid var(--border-glass);
  padding-top: 14px;
}

.timer-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 0, 127, 0.08);
  border: 1px solid rgba(255, 0, 127, 0.2);
  border-radius: var(--radius-md);
  padding: 14px 20px;
  color: var(--accent-pink);
}

.timer-icon {
  animation: spin-timer 10s linear infinite;
  flex-shrink: 0;
}

.timer-text {
  display: flex;
  flex-direction: column;
}

.timer-desc {
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
}

.timer-countdown {
  font-size: 20px;
  font-weight: 800;
}

.price-summary {
  border-top: 1px solid var(--border-glass);
  padding-top: 20px;
  margin-top: auto;
}

.price-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 15px;
  color: var(--text-secondary);
}

.price-value {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-primary);
}

.btn-checkout {
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
}

.btn-checkout:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 0 25px rgba(255, 0, 127, 0.5);
}

.btn-checkout:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
}

@keyframes spin-timer {
  100% { transform: rotate(360deg); }
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
