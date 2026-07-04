<template>
  <div class="seat-selection-view" v-if="bookingStore.selectedShowtime">
    <div class="selection-grid-container glass-panel">
      <div class="seats-map-wrapper w-100 my-4">
        <SeatMap
          :seats="mappedSeats"
          mode="client"
          :selectedSeatIds="selectedSeatIds"
          @seat-clicked="handleSeatMapClick"
        />
      </div>

      <div class="seats-legend">
        <div class="legend-item">
          <span class="legend-box seat-standard"></span> Standard ({{ priceK(seatPrices.standard) }})
        </div>
        <div class="legend-item">
          <span class="legend-box seat-vip"></span> VIP ({{ priceK(seatPrices.vip) }})
        </div>
        <div class="legend-item">
          <span class="legend-box seat-couple"></span> Couple ({{ priceK(seatPrices.couple) }})
        </div>
        <div class="legend-item">
          <span class="legend-box seat-selected"></span> Đang chọn
        </div>
        <div class="legend-item">
          <span class="legend-box seat-sold"></span> Đã bán / Đang giữ
        </div>
      </div>
    </div>

    <div class="booking-sidebar glass-panel">
      <h2 class="sidebar-title gradient-text-accent">Thông Tin Vé</h2>

      <div class="summary-details">
        <div class="summary-row">
          <span class="sum-label">Phim:</span>
          <span class="sum-value">{{ bookingStore.selectedMovie?.title }}</span>
        </div>
        <div class="summary-row">
          <span class="sum-label">Lịch chiếu:</span>
          <span class="sum-value"
            >{{ bookingStore.selectedShowtime?.start_time }} |
            {{ bookingStore.selectedShowtime?.date }}</span
          >
        </div>
        <div class="summary-row">
          <span class="sum-label">Phòng chiếu:</span>
          <span class="sum-value">{{
            bookingStore.selectedShowtime?.room_name
          }}</span>
        </div>
        <div class="summary-row border-top">
          <span class="sum-label">Ghế chọn:</span>
          <span class="sum-value seat-names">
            {{
              bookingStore.selectedSeats
                .map((s) => `${s.row}${s.number}`)
                .join(", ") || "Chưa chọn ghế"
            }}
          </span>
        </div>
      </div>

      <div
        v-if="
          bookingStore.holdExpiresAt && bookingStore.selectedSeats.length > 0
        "
        class="timer-card pulse-active"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="20"
          height="20"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          class="timer-icon"
        >
          <circle cx="12" cy="12" r="10"></circle>
          <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
        <div class="timer-text">
          <span class="timer-desc">Vui lòng hoàn tất trong</span>
          <span class="timer-countdown">{{ countdownText }}</span>
        </div>
      </div>

      <div class="price-summary">
        <div class="price-row">
          <span>Tạm tính ghế:</span>
          <span class="price-value">{{
            formatCurrency(bookingStore.subtotalSeats)
          }}</span>
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
    <router-link
      to="/"
      class="btn-primary"
      style="margin-top: 20px; display: inline-block"
      >Quay về Trang chủ</router-link
    >
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { useRouter } from "vue-router";
import Swal from 'sweetalert2';
import { useBookingStore } from "../../stores/booking";
import api from "../../api/axios";

// IMPORT THÀNH PHẦN THEO ĐÚNG YÊU CẦU CỦA TECH LEAD
import SeatMap from "../../components/SeatMap.vue";

const router = useRouter();
const bookingStore = useBookingStore();

const rawSeatsFromAPI = ref([]); // Nơi lưu mảng gốc tải về từ database
const seatPrices = ref({ standard: 75000, vip: 95000, couple: 140000 }); // Giá thật lấy từ cấu hình của suất chiếu
const countdownText = ref("10:00");
let timerInterval = null;

const formatCurrency = (val) => {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(val);
};

// Lấy giá theo loại ghế từ bảng giá THẬT của suất chiếu (admin cấu hình)
const getSeatPrice = (type) => {
  return seatPrices.value[type] ?? seatPrices.value.standard ?? 0;
};

// Định dạng gọn cho chú thích: 75000 -> "75k"
const priceK = (val) => `${Math.round((val || 0) / 1000)}k`;

// ÁNH XẠ DỮ LIỆU ĐẦU RA (COMPUTED): Chuyển đổi dữ liệu thô sang 5 trường Tech Lead yêu cầu
const mappedSeats = computed(() => {
  return rawSeatsFromAPI.value.map((seat) => {
    return {
      id: seat.id, // 1. ID duy nhất của ghế
      row: seat.row_name, // 2. Tên hàng ghế ('A','B'...)
      number: seat.seat_number, // 3. Số thứ tự cột (1,2,3...)
      type: seat.type || "standard", // 4. Khớp enum 4 loại từ DB trả về
      is_booked: seat.status === "sold" || seat.status === "holding", // 5. Cấm click nếu đã bán/giữ
    };
  });
});

// Trích xuất mảng ID các ghế đang chọn từ Store để đồng bộ hiệu ứng sáng của Ma Trận Ghế 3D
const selectedSeatIds = computed(() => {
  return bookingStore.selectedSeats.map((s) => s.id);
});

// HỨNG SỰ KIỆN TỪ SEATMAP: Xử lý kích hoạt khi bấm chọn ghế trên giao diện 3D
const handleSeatMapClick = async (seat) => {
  if (!localStorage.getItem('cinego_token')) {
    Swal.fire({
      title: 'Thông báo',
      text: 'Vui lòng đăng nhập trước khi thực hiện đặt vé!',
      icon: 'warning',
      confirmButtonText: 'Đăng nhập',
      confirmButtonColor: '#e50914'
    }).then(() => {
      router.push("/login");
    });
    return;
  }

  const price = getSeatPrice(seat.type);
  const seatObj = {
    id: seat.id,
    row: seat.row,
    number: seat.number,
    type: seat.type,
    price: price,
  };

  const isAlreadySelected = bookingStore.selectedSeats.some(
    (s) => s.id === seat.id,
  );

  try {
    if (!isAlreadySelected) {
      // OPTIMISTIC UPDATE: Đẩy ghế vào Store quản lý đơn hàng NGAY LẬP TỨC để UI phản hồi ngay (0 độ trễ)
      bookingStore.toggleSeat(seatObj);

      // Nếu là ghế đầu tiên khởi tạo bộ đếm 10 phút
      if (bookingStore.selectedSeats.length === 1) {
        bookingStore.setHoldExpiry(10);
        startTimer();
      }

      // Gọi API giữ ghế tạm thời ở background
      await api.post("/seat-holds", {
        showtime_id: bookingStore.selectedShowtime.id,
        seat_id: seat.id,
      });
    } else {
      // OPTIMISTIC UPDATE: Bỏ chọn ghế ngay lập tức trên UI
      bookingStore.toggleSeat(seatObj);

      // Nếu hủy toàn bộ mảng ghế đang chọn -> Ngắt bộ đếm
      if (bookingStore.selectedSeats.length === 0) {
        bookingStore.holdExpiresAt = null;
        stopTimer();
      }

      // Gọi API hủy giữ ghế ở background
      await api.post("/seat-holds/release", {
        showtime_id: bookingStore.selectedShowtime.id,
        seat_id: seat.id,
      });
    }
  } catch (error) {
    // ROLBACK OPTIMISTIC UPDATE: Khôi phục lại trạng thái ban đầu do gọi API thất bại
    bookingStore.toggleSeat(seatObj);
    if (bookingStore.selectedSeats.length === 0) {
      bookingStore.holdExpiresAt = null;
      stopTimer();
    }

    const errorMsg = error.response?.data?.message || "Có lỗi xảy ra khi xử lý chọn ghế!";
    Swal.fire({
      title: 'Lỗi chọn ghế',
      text: errorMsg,
      icon: 'error',
      confirmButtonColor: '#e50914'
    });
    // Tải lại sơ đồ ghế để cập nhật trạng thái mới nhất từ server
    fetchSeatStatus();
  }
};

const updateTimer = () => {
  if (bookingStore.holdExpiresAt) {
    const diff = bookingStore.holdExpiresAt - Date.now();
    if (diff <= 0) {
      countdownText.value = "00:00";
      stopTimer();
      bookingStore.clearBooking();
      Swal.fire({
        title: 'Hết thời gian',
        text: 'Hết thời gian giữ ghế. Vui lòng đặt vé lại!',
        icon: 'warning',
        confirmButtonColor: '#e50914'
      }).then(() => {
        router.push("/");
      });
    } else {
      const minutes = Math.floor(diff / 60000);
      const seconds = Math.floor((diff % 60000) / 1000);
      countdownText.value = `${minutes.toString().padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
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

// Gọi dữ liệu thô của cấu hình phòng chiếu và trạng thái từ backend
const fetchSeatStatus = async () => {
  try {
    const response = await api.get(
      `/showtimes/${bookingStore.selectedShowtime.id}/seats`,
    );
    const data = response.data;
    if (Array.isArray(data)) {
      // Dạng cũ: chỉ là mảng ghế
      rawSeatsFromAPI.value = data;
    } else {
      // Dạng mới: { seats, prices }
      rawSeatsFromAPI.value = data.seats || [];
      if (data.prices) {
        seatPrices.value = { ...seatPrices.value, ...data.prices };
      }
    }
  } catch (err) {
    console.warn("Fetch seats API error, using fallback mock data structures:");

    // Tạo mảng dữ liệu thô giả lập cấu trúc DB (9 hàng x 12 cột) để map mượt mà nếu API chưa chạy
    const mockData = [];
    const rowsList = ["A", "B", "C", "D", "E", "F", "G", "H", "J"];
    let currentId = 1;

    rowsList.forEach((row) => {
      for (let col = 1; col <= 12; col++) {
        // Giả lập trạng thái đã bán hoặc giữ ngẫu nhiên cho một vài ghế
        let initialStatus = "available";
        if (["A-5", "F-7", "J-4", "C-2", "G-10"].includes(`${row}-${col}`)) {
          initialStatus = "sold";
        }

        mockData.push({
          id: currentId++,
          row_name: row,
          seat_number: col,
          status: initialStatus,
          is_aisle: col === 3 || col === 10, // Đánh dấu thử nghiệm lối đi để kích hoạt 'hidden'
        });
      }
    });
    rawSeatsFromAPI.value = mockData;
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

const validateSeatSelection = () => {
  if (bookingStore.selectedSeats.length > 8) {
    Swal.fire({
      title: 'Giới hạn số lượng',
      text: 'Bạn chỉ được chọn tối đa 8 ghế trong một lần giao dịch để đảm bảo công bằng.',
      icon: 'info',
      confirmButtonColor: '#e50914'
    });
    return false;
  }

  const rows = {};
  mappedSeats.value.forEach(seat => {
    if (!rows[seat.row]) rows[seat.row] = [];
    rows[seat.row].push(seat);
  });

  for (const rowKey in rows) {
    const rowSeats = rows[rowKey].sort((a, b) => parseInt(a.number) - parseInt(b.number));
    
    const hasSelected = rowSeats.some(s => selectedSeatIds.value.includes(s.id));
    if (!hasSelected) continue;

    for (let i = 0; i < rowSeats.length; i++) {
      const currentSeat = rowSeats[i];
      const isSelected = selectedSeatIds.value.includes(currentSeat.id);
      
      if (!currentSeat.is_booked && !isSelected) {
        const leftNeighbor = i > 0 ? rowSeats[i - 1] : null;
        const rightNeighbor = i < rowSeats.length - 1 ? rowSeats[i + 1] : null;

        const isLeftEmpty = leftNeighbor && !leftNeighbor.is_booked && !selectedSeatIds.value.includes(leftNeighbor.id);
        const isRightEmpty = rightNeighbor && !rightNeighbor.is_booked && !selectedSeatIds.value.includes(rightNeighbor.id);

        const isIsolated = !isLeftEmpty && !isRightEmpty;

        if (isIsolated) {
          const leftCaused = leftNeighbor && selectedSeatIds.value.includes(leftNeighbor.id);
          const rightCaused = rightNeighbor && selectedSeatIds.value.includes(rightNeighbor.id);

          if (leftCaused || rightCaused) {
            Swal.fire({
              title: 'Quy định rạp',
              text: 'Không được để trống 1 ghế đơn lẻ ở giữa các ghế đã chọn hoặc ở sát rìa hàng ghế.',
              icon: 'warning',
              confirmButtonColor: '#e50914'
            });
            return false;
          }
        }
      }
    }
  }

  return true;
};

const proceedToPayment = () => {
  if (bookingStore.selectedSeats.length > 0) {
    if (validateSeatSelection()) {
      router.push("/booking/payment");
    }
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

.seat-standard {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
.seat-vip {
  background: rgba(112, 0, 255, 0.2);
  border: 1px solid rgba(112, 0, 255, 0.5);
}
.seat-couple {
  background: rgba(255, 0, 127, 0.2);
  border: 1px solid rgba(255, 0, 127, 0.5);
}
.seat-selected {
  background: var(--accent-mint);
}
.seat-sold {
  background: rgba(255, 255, 255, 0.03);
  opacity: 0.3;
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
  100% {
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
