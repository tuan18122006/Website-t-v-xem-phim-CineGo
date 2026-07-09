<template>
  <div class="pos-container">
    <div class="pos-left-panel">
      <!-- Màn 1: Chọn Phim & Suất Chiếu -->
      <div v-if="step === 1" class="pos-step-box">
        <h2 class="step-title">1. Chọn Phim & Suất Chiếu</h2>
        
        <div class="movie-list">
          <div 
            v-for="movie in movies" 
            :key="movie.id"
            class="movie-card"
            :class="{ active: selectedMovie?.id === movie.id }"
            @click="selectMovie(movie)"
          >
            <img 
              :src="movie.poster_url" 
              alt="poster" 
              class="movie-poster" 
              @error="(e) => e.target.src = 'https://via.placeholder.com/120x160.png?text=No+Image'"
            />
            <p class="movie-name">{{ movie.title }}</p>
          </div>
        </div>

        <div v-if="selectedMovie" class="showtime-section">
          <div class="showtime-header">
            <h3>Suất chiếu ngày:</h3>
            <input type="date" class="date-picker" v-model="selectedDate" @change="fetchShowtimes" />
          </div>
          <div v-if="showtimes.length === 0" class="no-data">Không có suất chiếu nào trong ngày này.</div>
          <div class="showtime-list" v-else>
            <button 
              v-for="st in showtimes" 
              :key="st.id"
              class="btn-showtime"
              :class="{ active: selectedShowtime?.id === st.id }"
              @click="selectShowtime(st)"
            >
              {{ st.start_time }}<br/>
              <small>{{ st.room_name }}</small>
            </button>
          </div>
        </div>

        <button 
          class="btn-next" 
          :disabled="!selectedShowtime"
          @click="goToStep(2)"
        >
          Tiếp tục: Chọn Ghế ➔
        </button>
      </div>

      <!-- Màn 2: Chọn Ghế -->
      <div v-if="step === 2" class="pos-step-box">
        <div class="step-header">
          <button class="btn-back" @click="goToStep(1)">⬅ Quay lại</button>
          <h2 class="step-title">2. Chọn Ghế ({{ selectedShowtime?.room_name }})</h2>
        </div>
        
        <div class="seat-map-container" v-if="!loadingSeats">
          <SeatMap
            :seats="mappedSeats"
            mode="client"
            :selectedSeatIds="selectedSeatIds"
            @seat-clicked="handleSeatClick"
          />
        </div>
        <div v-else class="loading-data">Đang tải sơ đồ ghế...</div>

        <button 
          class="btn-next" 
          :disabled="selectedSeats.length === 0"
          @click="goToStep(3)"
        >
          Tiếp tục: Bắp Nước ➔
        </button>
      </div>

      <!-- Màn 3: Bắp Nước -->
      <div v-if="step === 3" class="pos-step-box">
        <div class="step-header">
          <button class="btn-back" @click="goToStep(2)">⬅ Quay lại</button>
          <h2 class="step-title">3. Bắp & Nước (Tùy chọn)</h2>
        </div>

        <div class="combo-list">
          <div v-for="combo in combos" :key="combo.id" class="combo-item">
            <div class="combo-info">
              <h4>{{ combo.name }}</h4>
              <p>{{ combo.description }}</p>
              <span class="combo-price">{{ formatPrice(combo.price) }}đ</span>
            </div>
            <div class="combo-controls">
              <button @click="updateCombo(combo, -1)">-</button>
              <span>{{ getComboQty(combo.id) }}</span>
              <button @click="updateCombo(combo, 1)">+</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CỘT PHẢI: HÓA ĐƠN & THANH TOÁN -->
    <div class="pos-right-panel">
      <div class="invoice-box glass-panel">
        <h2 class="invoice-title">HÓA ĐƠN TẠI QUẦY</h2>
        
        <div class="invoice-details" v-if="selectedMovie">
          <p><strong>Phim:</strong> {{ selectedMovie.title }}</p>
          <p v-if="selectedShowtime"><strong>Suất chiếu:</strong> {{ selectedShowtime.start_time }} ({{ selectedShowtime.date }})</p>
          <p v-if="selectedShowtime"><strong>Phòng:</strong> {{ selectedShowtime.room_name }}</p>
          
          <div class="invoice-divider"></div>
          
          <p><strong>Ghế:</strong> {{ selectedSeats.map(s => s.row + s.number).join(', ') || 'Chưa chọn' }}</p>
          <p class="txt-right">{{ formatPrice(subtotalSeats) }}đ</p>

          <template v-if="selectedCombos.length > 0">
            <div class="invoice-divider"></div>
            <p><strong>Bắp nước:</strong></p>
            <p v-for="c in selectedCombos" :key="c.combo.id" class="combo-line">
              <span>{{ c.quantity }}x {{ c.combo.name }}</span>
              <span>{{ formatPrice(c.quantity * c.combo.price) }}đ</span>
            </p>
          </template>
        </div>
        <div v-else class="empty-invoice">
          Vui lòng chọn phim để bắt đầu.
        </div>

        <div class="invoice-total">
          <span>TỔNG CỘNG:</span>
          <span class="total-price">{{ formatPrice(totalAmount) }}đ</span>
        </div>

        <button 
          class="btn-checkout" 
          :disabled="selectedSeats.length === 0 || isProcessing"
          @click="handleCheckout"
        >
          {{ isProcessing ? 'ĐANG XỬ LÝ...' : '💵 THU TIỀN MẶT & IN VÉ' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import Swal from 'sweetalert2';
import api from '../../api/axios';
import SeatMap from '../../components/SeatMap.vue';

const step = ref(1);
const movies = ref([]);
const showtimes = ref([]);
const combos = ref([]);
const rawSeats = ref([]);
const seatPrices = ref({ standard: 75000, vip: 95000, couple: 140000 });

// State
const selectedMovie = ref(null);
const selectedShowtime = ref(null);
const selectedSeats = ref([]);
const selectedCombos = ref([]); // { combo, quantity }
const loadingSeats = ref(false);
const isProcessing = ref(false);

const today = new Date().toISOString().split('T')[0];
const selectedDate = ref(today);

const formatPrice = (val) => {
  return new Intl.NumberFormat('vi-VN').format(val || 0);
};

// --- DATA FETCHING ---
onMounted(async () => {
  fetchMovies();
  fetchCombos();
});

const fetchMovies = async () => {
  try {
    const res = await api.get('/movies');
    // Chỉ lấy phim đang chiếu
    movies.value = res.data.data.filter(movie => {
      if (!movie.status) return false;
      const s = movie.status.toLowerCase().replace(/[\s_]/g, '-');
      return s === 'now-showing' || s === 'showing' || s === 'đang-chiếu';
    });
  } catch (err) {
    console.error(err);
  }
};

const fetchCombos = async () => {
  try {
    const res = await api.get('/combos/active');
    combos.value = res.data.data;
  } catch (err) {
    console.error(err);
  }
};

const fetchShowtimes = async () => {
  if (!selectedMovie.value) return;
  try {
    const res = await api.get(`/showtimes/by-date?date=${selectedDate.value}`);
    showtimes.value = (res.data.data || []).filter(st => st.movie_id === selectedMovie.value.id);
  } catch (err) {
    console.error(err);
    showtimes.value = [];
  }
};

// --- ACTIONS ---
const selectMovie = async (movie) => {
  selectedMovie.value = movie;
  selectedShowtime.value = null;
  selectedSeats.value = [];
  selectedCombos.value = [];
  step.value = 1;
  
  try {
    // Lấy suất chiếu của phim này trong ngày đang chọn
    const res = await api.get(`/showtimes/by-date?date=${selectedDate.value}`);
    // Lọc ra các suất chiếu thuộc về phim đang chọn
    showtimes.value = (res.data.data || []).filter(st => st.movie_id === movie.id);
  } catch (err) {
    console.error(err);
    showtimes.value = [];
  }
};

const selectShowtime = async (st) => {
  selectedShowtime.value = st;
  selectedSeats.value = [];
  
  // Set prices from pricing_snapshot if available
  if (st.pricing_snapshot) {
    seatPrices.value = typeof st.pricing_snapshot === 'string' 
      ? JSON.parse(st.pricing_snapshot) 
      : st.pricing_snapshot;
  }
};

const goToStep = async (s) => {
  if (s === 2 && selectedShowtime.value) {
    loadingSeats.value = true;
    try {
      const res = await api.get(`/showtimes/${selectedShowtime.value.id}/seats`);
      rawSeats.value = res.data.data;
    } catch (err) {
      console.error(err);
    } finally {
      loadingSeats.value = false;
    }
  }
  step.value = s;
};

// --- SEAT LOGIC ---
const mappedSeats = computed(() => {
  return rawSeats.value.map(seat => ({
    id: seat.id,
    row: seat.row_name,
    number: seat.seat_number,
    type: seat.type || 'standard',
    is_booked: seat.status === 'sold' || seat.status === 'holding'
  }));
});

const selectedSeatIds = computed(() => selectedSeats.value.map(s => s.id));

const handleSeatClick = (seat) => {
  if (seat.is_booked) return;
  const index = selectedSeats.value.findIndex(s => s.id === seat.id);
  
  if (index > -1) {
    selectedSeats.value.splice(index, 1);
  } else {
    // Bấm thì thêm vào
    const price = seatPrices.value[seat.type] || seatPrices.value.standard;
    selectedSeats.value.push({ ...seat, price });
  }
};

// --- COMBO LOGIC ---
const getComboQty = (id) => {
  const c = selectedCombos.value.find(item => item.combo.id === id);
  return c ? c.quantity : 0;
};

const updateCombo = (combo, diff) => {
  const index = selectedCombos.value.findIndex(item => item.combo.id === combo.id);
  if (index > -1) {
    const newQty = selectedCombos.value[index].quantity + diff;
    if (newQty <= 0) {
      selectedCombos.value.splice(index, 1);
    } else {
      selectedCombos.value[index].quantity = newQty;
    }
  } else if (diff > 0) {
    selectedCombos.value.push({ combo, quantity: 1 });
  }
};

// --- CHECKOUT LOGIC ---
const subtotalSeats = computed(() => {
  return selectedSeats.value.reduce((total, seat) => total + (seat.price || 0), 0);
});

const subtotalCombos = computed(() => {
  return selectedCombos.value.reduce((total, item) => total + (item.combo.price * item.quantity), 0);
});

const totalAmount = computed(() => subtotalSeats.value + subtotalCombos.value);

const handleCheckout = async () => {
  if (selectedSeats.value.length === 0) return;

  isProcessing.value = true;
  try {
    const payload = {
      showtime_id: selectedShowtime.value.id,
      seat_ids: selectedSeats.value.map(s => s.id),
      combos: selectedCombos.value.map(c => ({
        id: c.combo.id,
        quantity: c.quantity
      })),
      payment_method: 'cash', // Hardcode tiền mặt cho POS
      total_amount: totalAmount.value
    };

    const res = await api.post('/bookings', payload);
    
    // Thành công
    Swal.fire({
      title: 'Thanh toán thành công!',
      html: `Mã vé: <strong>${res.data.booking_code || res.data.data?.booking_code}</strong><br/>Đã thu tiền mặt: ${formatPrice(totalAmount.value)}đ`,
      icon: 'success',
      confirmButtonText: 'In Vé & Mua Mới'
    }).then(() => {
      // Reset form
      selectedMovie.value = null;
      selectedShowtime.value = null;
      selectedSeats.value = [];
      selectedCombos.value = [];
      step.value = 1;
    });

  } catch (error) {
    Swal.fire('Lỗi!', error.response?.data?.message || 'Có lỗi xảy ra khi tạo đơn hàng.', 'error');
  } finally {
    isProcessing.value = false;
  }
};

</script>

<style scoped>
.pos-container {
  display: flex;
  gap: 20px;
  height: 100%;
}

.pos-left-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.pos-step-box {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.step-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.step-title {
  font-size: 20px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}

.btn-back {
  background: #f1f5f9;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.movie-list {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 16px;
  margin-top: 16px;
}

.movie-card {
  width: 120px;
  flex-shrink: 0;
  cursor: pointer;
  border: 2px solid transparent;
  border-radius: 8px;
  overflow: hidden;
  transition: 0.2s;
}

.movie-card.active {
  border-color: var(--accent-pink);
  box-shadow: 0 0 10px rgba(216, 45, 139, 0.3);
}

.movie-poster {
  width: 100%;
  height: 160px;
  object-fit: cover;
}

.movie-name {
  font-size: 13px;
  font-weight: 700;
  text-align: center;
  padding: 8px;
  margin: 0;
  background: #f8fafc;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.showtime-section {
  margin-top: 20px;
}

.showtime-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 12px;
}

.btn-showtime {
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  background: #fff;
  border-radius: 8px;
  cursor: pointer;
  text-align: center;
  font-weight: 700;
}

.btn-showtime.active {
  background: var(--accent-pink);
  color: #fff;
  border-color: var(--accent-pink);
}

.btn-next {
  margin-top: 24px;
  width: 100%;
  background: #1e293b;
  color: #fff;
  border: none;
  padding: 14px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
}

.btn-next:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.combo-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.combo-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
}

.combo-info h4 {
  margin: 0 0 4px 0;
}

.combo-info p {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #64748b;
}

.combo-price {
  font-weight: 700;
  color: var(--accent-pink);
}

.combo-controls {
  display: flex;
  align-items: center;
  gap: 12px;
}

.combo-controls button {
  width: 32px;
  height: 32px;
  border: 1px solid #cbd5e1;
  background: #fff;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
}

.pos-right-panel {
  width: 350px;
  flex-shrink: 0;
}

.invoice-box {
  padding: 24px;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.invoice-title {
  font-size: 18px;
  font-weight: 800;
  text-align: center;
  margin-bottom: 20px;
  color: var(--accent-pink);
  border-bottom: 2px dashed #e2e8f0;
  padding-bottom: 12px;
}

.invoice-details p {
  margin: 8px 0;
  font-size: 14px;
}

.invoice-divider {
  height: 1px;
  background: #e2e8f0;
  margin: 12px 0;
}

.txt-right {
  text-align: right;
  font-weight: 700;
}

.combo-line {
  display: flex;
  justify-content: space-between;
}

.invoice-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 24px;
  padding-top: 16px;
  border-top: 2px solid #1e293b;
  font-weight: 800;
  font-size: 18px;
}

.total-price {
  color: #e50914;
  font-size: 22px;
}

.btn-checkout {
  width: 100%;
  margin-top: 24px;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: #fff;
  border: none;
  padding: 16px;
  border-radius: 8px;
  font-weight: 800;
  font-size: 16px;
  cursor: pointer;
}

.btn-checkout:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.empty-invoice {
  text-align: center;
  color: #94a3b8;
  padding: 40px 0;
}
</style>
