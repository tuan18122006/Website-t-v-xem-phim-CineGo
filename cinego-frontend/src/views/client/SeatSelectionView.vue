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
        </div>
        <div class="legend-item">
        </div>
        <div class="legend-item">
        </div>
        <div class="legend-item">
        </div>
        <div class="legend-item">
        </div>
      </div>
    </div>

    <div class="booking-sidebar glass-panel">
      <h2 class="sidebar-title gradient-text-accent">Thông Tin Vé</h2>

      <div v-if="bookingStore.selectedMovie?.poster_url" class="movie-poster-box">
        <img :src="getPosterUrl(bookingStore.selectedMovie.poster_url)" :alt="bookingStore.selectedMovie.title" class="movie-poster-img" />
      </div>

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
        :disabled="bookingStore.selectedSeats.length === 0 || processingSeatCount > 0"
        class="btn-checkout"
      >
        <span v-if="processingSeatCount > 0">Đang xử lý...</span>
        <span v-else>Tiếp Tục</span>
      </button>
      <button 
    type="button" 
    @click="cancelBooking" 
    class="btn-cancel-checkout"
  >
    Hủy Đặt Vé
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
import { ref, onMounted, onUnmounted, computed, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import Swal from 'sweetalert2';
import { useBookingStore } from "../../stores/booking";
import api from "../../api/axios";
import echo from "../../api/echo";

import SeatMap from "../../components/SeatMap.vue";

const router = useRouter();
const route = useRoute();
const bookingStore = useBookingStore();

const rawSeatsFromAPI = ref([]); 
const seatPrices = ref({ standard: 75000, vip: 95000, couple: 140000 }); 
const countdownText = ref("03:00");
const featuredComments = ref([]);
const processingSeatCount = ref(0);

const getPosterUrl = (url) => {
  if (!url) return 'https://via.placeholder.com/300x450/1a1a1a/e50914?text=CineGo';
  if (url.startsWith('http')) return url;
  const cleanPath = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${cleanPath}`;
};
let timerInterval = null;

const cancelBooking = () => {
    Swal.fire({
        title: 'Xác nhận hủy',
        text: "Bạn có chắc chắn muốn hủy quá trình đặt vé này không?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e30613',
        cancelButtonColor: '#6f6a63',
        confirmButtonText: 'Đồng ý hủy',
        cancelButtonText: 'Tiếp tục đặt'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const movieId = bookingStore.selectedMovie?.id;
            const showtimeId = bookingStore.selectedShowtime?.id;

            if (showtimeId && bookingStore.selectedSeats.length > 0) {
                await Promise.allSettled(
                    bookingStore.selectedSeats.map((seat) =>
                        api.post("/seat-holds/release", {
                            showtime_id: showtimeId,
                            seat_id: seat.id,
                        })
                    )
                );
            }

            stopTimer();
            bookingStore.clearBooking();

            router.push(movieId ? `/movies/${movieId}` : "/");
        }
    });
};

const formatCurrency = (val) => {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(val);
};

const getSeatPrice = (type) => {
  return seatPrices.value[type] ?? seatPrices.value.standard ?? 0;
};

const priceK = (val) => `${Math.round((val || 0) / 1000)}k`;

const shuffleArray = (arr) => {
  const copy = [...arr];
  for (let i = copy.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [copy[i], copy[j]] = [copy[j], copy[i]];
  }
  return copy;
};

const mappedSeats = computed(() => {
  return rawSeatsFromAPI.value.map((seat) => {
    // Ghế do chính mình giữ: hiện như "available" để có thể click, store sẽ tô màu vàng
    const isMyHold = seat.is_held_by_me === true;
    return {
      id: seat.id, 
      row: seat.row_name, 
      number: seat.seat_number, 
      type: seat.type || "standard", 
      status: isMyHold ? 'available' : seat.status,
      is_booked: !isMyHold && (seat.status === "sold" || seat.status === "broken" || seat.status === "holding"), 
    };
  });
});

const selectedSeatIds = computed(() => {
  return bookingStore.selectedSeats.map((s) => s.id);
});

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

  processingSeatCount.value++;

  try {
    if (!isAlreadySelected) {
      bookingStore.toggleSeat(seatObj);

      const response = await api.post("/seat-holds", {
        showtime_id: bookingStore.selectedShowtime.id,
        seat_id: seat.id,
      });

      if (bookingStore.selectedSeats.length === 1 && response.data.expires_at) {
        bookingStore.holdExpiresAt = Date.parse(response.data.expires_at);
        localStorage.setItem('cinego_holdExpiresAt', Date.parse(response.data.expires_at));
        startTimer();
      }

      const heldSeat = rawSeatsFromAPI.value.find((s) => s.id === seat.id);
      if (heldSeat) heldSeat.status = "holding";
    } else {
      bookingStore.toggleSeat(seatObj);

      if (bookingStore.selectedSeats.length === 0) {
        bookingStore.holdExpiresAt = null;
        stopTimer();
      }

      await api.post("/seat-holds/release", {
        showtime_id: bookingStore.selectedShowtime.id,
        seat_id: seat.id,
      });

      const releasedSeat = rawSeatsFromAPI.value.find((s) => s.id === seat.id);
      if (releasedSeat) releasedSeat.status = "available";
    }
  } catch (error) {
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
    fetchSeatStatus();
  } finally {
    processingSeatCount.value--;
  }
};

const updateFeaturedComments = () => {
  const movieTitle = bookingStore.selectedMovie?.title || '';
  const commentsByMovie = {
    'Doctor Strange: Đa Vũ Trụ Hỗn Loạn': [
      {
        id: 'ds-1',
        movieTitle: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
        userName: 'Nguyễn Thùy Linh',
        timeAgo: '1 giờ trước',
        rating: 5,
        comment: 'Cảnh đa vũ trụ cực kỳ mãn nhãn. Rất thích cách kể chuyện và kỹ xảo trong phim này.'
      },
      {
        id: 'ds-2',
        movieTitle: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
        userName: 'Lê Hoàng',
        timeAgo: '3 giờ trước',
        rating: 4,
        comment: 'Âm nhạc và diễn xuất quá tuyệt. Phiên bản này xem rạp là đúng bài.'
      },
      {
        id: 'ds-3',
        movieTitle: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
        userName: 'Trần Thị Mai',
        timeAgo: '6 giờ trước',
        rating: 5,
        comment: 'Đa vũ trụ phức tạp nhưng hấp dẫn. Cảnh hành động quá ngầu.'
      },
      {
        id: 'ds-4',
        movieTitle: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
        userName: 'Phạm Văn Quân',
        timeAgo: '1 ngày trước',
        rating: 4,
        comment: 'Phim rất đáng xem. Mình hơi choáng với nhiều twist nhưng vẫn ấn tượng.'
      },
      {
        id: 'ds-5',
        movieTitle: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
        userName: 'Đỗ Minh Hằng',
        timeAgo: '1 ngày trước',
        rating: 5,
        comment: 'Nội dung đa chiều, diễn viên hóa thân xuất sắc. Mình sẽ xem lại lần nữa.'
      }
    ],
    'Avatar: Dòng Chảy Của Nước': [
      {
        id: 'av-1',
        movieTitle: 'Avatar: Dòng Chảy Của Nước',
        userName: 'Nguyễn Thùy Linh',
        timeAgo: '2 giờ trước',
        rating: 5,
        comment: 'Cảnh dưới nước đẹp tới mức không thể rời mắt. Xem rạp thì càng mãn nhãn.'
      },
      {
        id: 'av-2',
        movieTitle: 'Avatar: Dòng Chảy Của Nước',
        userName: 'Lê Hoàng',
        timeAgo: '4 giờ trước',
        rating: 5,
        comment: 'Cách xử lý kỹ xảo và màu sắc quá đỉnh. Tối đi xem lại ngay!'
      },
      {
        id: 'av-3',
        movieTitle: 'Avatar: Dòng Chảy Của Nước',
        userName: 'Trần Thị Mai',
        timeAgo: '8 giờ trước',
        rating: 4,
        comment: 'Cốt truyện sâu sắc, cảm giác như được chìm vào thế giới Pandora.'
      },
      {
        id: 'av-4',
        movieTitle: 'Avatar: Dòng Chảy Của Nước',
        userName: 'Phạm Văn Quân',
        timeAgo: '1 ngày trước',
        rating: 5,
        comment: 'Âm thanh và hiệu ứng hoành tráng, rất xứng đáng với thời lượng dài.'
      },
      {
        id: 'av-5',
        movieTitle: 'Avatar: Dòng Chảy Của Nước',
        userName: 'Đỗ Minh Hằng',
        timeAgo: '1 ngày trước',
        rating: 5,
        comment: 'Một trải nghiệm giải trí mạnh mẽ, thích hợp đi xem cả gia đình.'
      }
    ],
    'Kẻ Kiến Tạo (The Creator)': [
      {
        id: 'tc-1',
        movieTitle: 'Kẻ Kiến Tạo (The Creator)',
        userName: 'Nguyễn Thùy Linh',
        timeAgo: '30 phút trước',
        rating: 5,
        comment: 'Tác phẩm rất ấn tượng với chủ đề AI nhân văn. Mình thấy xúc động và suy ngẫm lâu.'
      },
      {
        id: 'tc-2',
        movieTitle: 'Kẻ Kiến Tạo (The Creator)',
        userName: 'Lê Hoàng',
        timeAgo: '2 giờ trước',
        rating: 4,
        comment: 'Nhịp phim căng, nhiều pha hành động đỉnh. Cốt truyện khiến mình suy nghĩ rất nhiều.'
      },
      {
        id: 'tc-3',
        movieTitle: 'Kẻ Kiến Tạo (The Creator)',
        userName: 'Trần Thị Mai',
        timeAgo: '5 giờ trước',
        rating: 5,
        comment: 'Diễn viên nhí thể hiện rất tốt, cảm xúc truyền tới người xem rất tự nhiên.'
      },
      {
        id: 'tc-4',
        movieTitle: 'Kẻ Kiến Tạo (The Creator)',
        userName: 'Phạm Văn Quân',
        timeAgo: '1 ngày trước',
        rating: 4,
        comment: 'Phim nặng đề tài nhưng vẫn dễ theo dõi. Mình đánh giá cao phần kỹ xảo.'
      },
      {
        id: 'tc-5',
        movieTitle: 'Kẻ Kiến Tạo (The Creator)',
        userName: 'Đỗ Minh Hằng',
        timeAgo: '1 ngày trước',
        rating: 5,
        comment: 'Rất đáng xem cho những ai muốn xem phim vừa hành động vừa triết lý.'
      }
    ],
  };

  const pickedComments = shuffleArray(commentsByMovie[movieTitle] || []).slice(0, 5);
  featuredComments.value = pickedComments.length > 0 ? pickedComments : [
    {
      id: 'default-1',
      movieTitle: movieTitle || 'Bộ phim CineGo',
      userName: 'CineGo User',
      timeAgo: 'vừa xong',
      rating: 5,
      comment: 'Cảm ơn bạn đã chọn CineGo. Các bình luận nổi bật sẽ xuất hiện ở đây khi bạn chọn phim.'
    }
  ];
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
const fetchSeatStatus = async () => {
  try {
    const response = await api.get(
      `/showtimes/${bookingStore.selectedShowtime.id}/seats`,
    );
    const data = response.data;
    if (Array.isArray(data)) {
      rawSeatsFromAPI.value = data;
    } else {
      rawSeatsFromAPI.value = data.seats || [];
      if (data.prices) {
        seatPrices.value = { ...seatPrices.value, ...data.prices };
      }
    }

    // --- RESTORE: khôi phục ghế đang giữ nếu user quay lại trang ---
    const myHeldSeats = rawSeatsFromAPI.value.filter(s => s.is_held_by_me === true);
    if (myHeldSeats.length > 0) {
      // Chỉ khôi phục nếu store đang trống (tránh ghi đè nếu đã có ghế trong store rồi)
      if (bookingStore.selectedSeats.length === 0) {
        myHeldSeats.forEach(seat => {
          const seatObj = {
            id: seat.id,
            row: seat.row_name,
            number: seat.seat_number,
            type: seat.type || 'standard',
            price: seatPrices.value[seat.type] ?? seatPrices.value.standard ?? 0,
          };
          bookingStore.toggleSeat(seatObj);
        });
      }

      // Khôi phục timer theo thời gian hết hạn thực tế từ server
      const earliestExpiry = myHeldSeats
        .map(s => new Date(s.hold_expires_at).getTime())
        .filter(t => !isNaN(t))
        .sort()[0];

      if (earliestExpiry && earliestExpiry > Date.now()) {
        bookingStore.holdExpiresAt = earliestExpiry;
        startTimer();
      }
    }
    // --- END RESTORE ---

  } catch (err) {
    console.warn("Fetch seats API error, using fallback mock data structures:");

    const mockData = [];
    const rowsList = ["A", "B", "C", "D", "E", "F", "G", "H", "J"];
    let currentId = 1;

    rowsList.forEach((row) => {
      for (let col = 1; col <= 12; col++) {
        let initialStatus = "available";
        if (["A-5", "F-7", "J-4", "C-2", "G-10"].includes(`${row}-${col}`)) {
          initialStatus = "sold";
        }

        mockData.push({
          id: currentId++,
          row_name: row,
          seat_number: col,
          status: initialStatus,
          is_aisle: col === 3 || col === 10, 
        });
      }
    });
    rawSeatsFromAPI.value = mockData;
  }
};

onMounted(() => {
  document.title = 'Mua vé - ' + (bookingStore.selectedMovie?.title || 'CineGo');
  fetchSeatStatus();
  updateFeaturedComments();
  if (bookingStore.holdExpiresAt) {
    startTimer();
  }

  if (bookingStore.selectedShowtime?.id) {
    echo.channel(`showtime.${bookingStore.selectedShowtime.id}`)
      .listen('SeatLocked', (e) => {
        const seat = rawSeatsFromAPI.value.find(s => s.id === e.seatId);
        if (seat) seat.status = e.status || 'holding';
        
        // Cảnh báo và đá ghế ra khỏi giỏ nếu admin khóa ghế mà khách đang chọn
        const isSelected = bookingStore.selectedSeats.find(s => s.id === e.seatId);
        if (isSelected && (e.status === 'broken' || e.status === 'locked')) {
           bookingStore.removeSeat(isSelected);
           Swal.fire({
             title: 'Ghế gặp sự cố!',
              text: `Ghế ${isSelected.row}${isSelected.number} bạn đang chọn vừa được hệ thống khóa do bảo trì. Vui lòng chọn ghế khác!`,
             icon: 'warning',
             confirmButtonColor: '#e50914'
           });
        }
      })
      .listen('SeatUnlocked', (e) => {
        const seat = rawSeatsFromAPI.value.find(s => s.id === e.seatId);
        if (seat) seat.status = 'available';
      });
  }
});

watch(
  () => bookingStore.selectedMovie,
  () => {
    updateFeaturedComments();
  },
  { immediate: true }
);

onUnmounted(() => {
  stopTimer();
  if (bookingStore.selectedShowtime?.id) {
    echo.leaveChannel(`showtime.${bookingStore.selectedShowtime.id}`);
  }
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

  if (bookingStore.selectedSeats.length < 2) {
    return true;
  }

  const rows = {};
  mappedSeats.value.forEach(seat => {
    // Loại bỏ 'couple' khỏi mảng check để ghế đôi không bị áp dụng luật cấm để trống 1 ghế
    if (!['standard', 'vip'].includes(seat.type)) return;

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
              title: 'Quy định hệ thống',
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

const proceedToPayment = async () => {
  if (bookingStore.selectedSeats.length === 0) return;
  if (!validateSeatSelection()) return;

  try {
    const pendingCheck = await api.get("/payments/check-pending", {
      params: { showtime_id: bookingStore.selectedShowtime.id },
    });

    if (pendingCheck.data?.has_pending) {
      const old = pendingCheck.data;
      const seatList = (old.seats || []).join(", ");
      const showtimeInfo = old.showtime_info ? ` (${old.showtime_info})` : "";

      const { isConfirmed } = await Swal.fire({
        title: "Bạn có đơn thanh toán lại chưa hoàn tất",
        html: `Đơn <b>${old.booking_code}</b>${showtimeInfo} (ghế: <b>${seatList}</b>) vẫn đang chờ thanh toán.<br/><br/>Tạo đơn mới sẽ <b>hủy đơn cũ</b>. Bạn có chắc muốn tiếp tục?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Đồng ý, hủy đơn cũ",
        cancelButtonText: "Quay lại",
        confirmButtonColor: "#e50914",
        reverseButtons: true,
      });

      if (!isConfirmed) {
        return;
      }

      await api.post("/payments/cancel-pending", {
        showtime_id: bookingStore.selectedShowtime.id,
        keep_seat_ids: bookingStore.selectedSeats.map((s) => s.id),
      });
    }

    const response = await api.post("/seat-holds/confirm", {
      showtime_id: bookingStore.selectedShowtime.id,
      seat_ids: bookingStore.selectedSeats.map((s) => s.id),
    });

    bookingStore.holdExpiresAt = Date.parse(response.data.expires_at);
    localStorage.setItem('cinego_holdExpiresAt', Date.parse(response.data.expires_at));
    startTimer();

    if (route.query.mode === 'pos') {
      document.title = 'Mua vé - ' + (bookingStore.selectedMovie?.title || 'CineGo');
      router.push({ path: '/staff/pos/checkout' });
    } else {
      document.title = 'Mua vé - ' + (bookingStore.selectedMovie?.title || 'CineGo');
      router.push("/booking/payment");
    }
  } catch (error) {
    const errorMsg =
      error.response?.data?.message ||
      "Thời gian giữ ghế đã hết. Vui lòng chọn lại ghế!";
    Swal.fire({
      title: "Hết thời gian giữ ghế",
      text: errorMsg,
      icon: "warning",
      confirmButtonColor: "#e50914",
    }).then(() => {
      fetchSeatStatus();
    });
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
  background: linear-gradient(145deg, #4b5563, #374151);
  border: 1px solid #6b7280;
}
.seat-vip {
  background: linear-gradient(145deg, #ef4444, #b91c1c);
  border: 1px solid #f87171;
}
.seat-couple {
  background: linear-gradient(145deg, #ec4899, #be185d);
  border: 1px solid #f472b6;
}
.seat-selected {
  background: linear-gradient(145deg, #10b981, #059669);
}
.seat-sold {
  background: #1f2937;
  opacity: 0.6;
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

.movie-poster-box {
  width: 100%;
  max-width: 220px;
  margin: 0 auto 16px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
.movie-poster-img {
  width: 100%;
  height: auto;
  max-height: 280px;
  display: block;
  object-fit: cover;
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

.featured-comments-box {
  margin-top: 20px;
  padding: 18px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(15, 23, 42, 0.08);
  box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
}

.featured-comments-title {
  font-size: 18px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 16px;
}

.featured-comments-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.featured-comment-card {
  padding: 16px;
  border-radius: 20px;
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.featured-comment-meta {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  align-items: flex-start;
}

.featured-comment-movie {
  font-size: 14px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.featured-comment-user {
  font-size: 12px;
  color: #6b7280;
}

.featured-comment-rating {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 56px;
  height: 30px;
  padding: 0 12px;
  border-radius: 999px;
  background: linear-gradient(135deg, #fd5a6c 0%, #ff947f 100%);
  color: #ffffff;
  font-weight: 700;
  font-size: 13px;
}

.featured-comment-text {
  margin: 0;
  color: #374151;
  font-size: 14px;
  line-height: 1.7;
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
.checkout-actions {
    display: flex;
    flex-direction: column; 
    gap: 12px;             
    margin-top: 15px;
    width: 100%;
}

.btn-cancel-checkout {
    width: 100%;
    padding: 12px;
    background-color: transparent; 
    color: #6c757d;               
    border: 1px solid #6c757d;     
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel-checkout:hover {
    background-color: #f8f9fa;     
    color: #dc3545;                
    border-color: #dc3545;         
}
</style>
