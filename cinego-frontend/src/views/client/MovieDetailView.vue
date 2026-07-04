<template>
  <div v-if="movie" class="movie-detail-view">
    <div class="detail-header glass-panel">
      <div class="poster-container">
        <img :src="movie.poster_url" :alt="movie.title" class="detail-poster" />
        <span class="detail-rating-badge" :class="getRatingClass(movie.rating)">
          {{ movie.rating }}
        </span>
      </div>

      <div class="info-container">
        <h1 class="movie-title">{{ movie.title }}</h1>

        <div class="genres-row">
          <span v-for="g in movie.genres" :key="g.id || g" class="detail-genre">
            {{ g.name || g }}
          </span>
        </div>

        <div class="metadata-grid">
          <div class="meta-item">
            <span class="meta-label">Thời lượng:</span>
            <span class="meta-value">{{ movie.duration }} phút</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Khởi chiếu:</span>
            <span class="meta-value">{{ formatDate(movie.release_date) }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Đạo diễn:</span>
            <span class="meta-value">John Doe</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Ngôn ngữ:</span>
            <span class="meta-value">Phụ đề tiếng Việt</span>
          </div>
        </div>

        <div class="movie-description">
          <h3>Nội Dung Phim</h3>
          <p>{{ movie.description || 'Bộ phim kể về một cuộc phiêu lưu kịch tính đầy bất ngờ, dẫn dắt người xem đi qua nhiều cung bậc cảm xúc với kỹ xảo hoành tráng và diễn xuất đỉnh cao từ dàn diễn viên hạng A.' }}</p>
        </div>
      </div>
    </div>

    <!-- Chọn lịch chiếu -->
    <section class="schedule-section glass-panel">
      <h2 class="section-title gradient-text-accent">Lịch Chiếu & Đặt Vé</h2>

      <!-- Chọn ngày -->
      <div class="date-selector">
        <button v-for="(day, index) in availableDays" :key="index" class="date-btn"
          :class="{ active: selectedDayIndex === index }" @click="selectedDayIndex = index">
          <span class="day-name">{{ day.weekday }}</span>
          <span class="day-date">{{ day.dateLabel }}</span>
        </button>
      </div>

      <div v-if="loadingShowtimes" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải lịch chiếu...</p>
      </div>

      <!-- Khung hiển thị Suất Chiếu theo Phòng -->
      <div v-else class="showtimes-by-room">
        <div v-for="room in showtimesByRoom" :key="room.roomId" class="room-row glass-panel">
          <div class="room-info">
            <h3 class="room-name">{{ room.roomName }}</h3>
            <span class="room-type">Phòng chiếu 2D & Âm thanh Dolby Atmos</span>
          </div>

          <div class="showtimes-grid">
            <button v-for="showtime in room.showtimes" :key="showtime.id" class="showtime-btn"
              :class="{ active: selectedShowtime?.id === showtime.id }" @click="selectShowtime(showtime)">
              <span class="time-label">
                {{ showtime.start_time }}
                <span v-if="showtime.is_sneak_show" class="sneak-badge-client" style="font-size: 10px; margin-left: 4px;">🔥 Sớm</span>
              </span>
              <span class="seat-available">Trống {{ showtime.available_seats || 85 }} ghế</span>
            </button>
          </div>
        </div>

        <div v-if="showtimesByRoom.length === 0" class="no-showtimes">
          Không có suất chiếu nào cho ngày đã chọn.
        </div>
      </div>

      <!-- Footer điều hướng -->
      <div class="schedule-footer" v-if="selectedShowtime">
        <div class="selected-summary">
          <p>Suất chiếu đã chọn: <strong>{{ selectedShowtime.start_time }}</strong> - Phòng: <strong>{{
            selectedShowtime.room_name }}</strong></p>
          <p>Ngày chiếu: <strong>{{ availableDays[selectedDayIndex].fullLabel }}</strong></p>
        </div>
        <button @click="proceedToSeatSelection" class="btn-proceed">
          Tiếp Tục Chọn Ghế
        </button>
      </div>
    </section>

    <section class="casts-section glass-panel">
      <h2 class="section-title gradient-text-accent">Diễn Viên Chính</h2>
      <div class="casts-grid">
        <div v-for="cast in (movie.casts || mockCasts)" :key="cast.id || cast.name" class="cast-card">
          <div class="cast-avatar-box">
            <img
              :src="cast.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              :alt="cast.name" class="cast-avatar" />
          </div>
          <div class="cast-info">
            <h4 class="cast-name">{{ cast.name }}</h4>
            <p class="cast-character">trong vai {{ cast.character || 'Character' }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="reviews-section glass-panel">
      <h2 class="section-title gradient-text-accent">Đánh Giá Từ Mọt Phim</h2>

      <div class="review-form-box">
        <h4 class="form-title">Để lại đánh giá của bạn</h4>
        <div class="rating-input-row">
          <span>Đánh giá của bạn:</span>
          <div class="stars-picker">
            <span v-for="star in 10" :key="star" class="star-pick" :class="{ active: myReview.rating >= star }"
              @click="myReview.rating = star">★</span>
          </div>
        </div>
        <div class="comment-input-group">
          <textarea v-model="myReview.comment" placeholder="Chia sẻ cảm nghĩ của bạn về bộ phim này..."
            class="review-textarea"></textarea>
          <button @click="submitReview" class="btn-submit-review">Gửi Review</button>
        </div>
      </div>

      <div class="reviews-list">
        <div v-for="review in (movie.reviews || mockReviews)" :key="review.id" class="review-item-card">
          <div class="review-user-header">
            <div class="user-meta">
              <span class="user-avatar-text">{{ review.user_name.charAt(0).toUpperCase() }}</span>
              <div>
                <h5 class="user-name">{{ review.user_name }}</h5>
                <span class="review-date">{{ formatDate(review.created_at) || 'Vừa xong' }}</span>
              </div>
            </div>
            <div class="user-stars">
              <span v-for="s in review.rating" :key="'fill-' + s" class="star-filled">★</span>
              <span v-for="s in (5 - review.rating)" :key="'empty-' + s" class="star-empty">★</span>
            </div>
          </div>
          <p class="review-content">{{ review.comment }}</p>
        </div>

        <div v-if="(movie.reviews && movie.reviews.length === 0) && mockReviews.length === 0" class="no-reviews">
          Chưa có đánh giá nào. Hãy là người đầu tiên review bộ phim này!
        </div>
      </div>
    </section>
  </div>
  <div v-else class="loading-state">
    <div class="spinner"></div>
    <p>Đang tải chi tiết phim...</p>
  </div>


</template>



<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBookingStore } from '../../stores/booking';
import api from '../../api/axios';

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();

const movie = ref(null);
const loadingShowtimes = ref(false);
const selectedDayIndex = ref(0);
const selectedShowtime = ref(null);
const showtimesByRoom = ref([]);

const availableDays = ref([]);

// Khởi tạo ngày hôm nay nếu DB chưa có lịch
const defaultDays = () => {
  const days = [];
  const weekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];

  for (let i = 0; i < 5; i++) {
    const d = new Date();
    d.setDate(d.getDate() + i);
    days.push({
      dateStr: d.toISOString().split('T')[0],
      weekday: i === 0 ? 'Hôm nay' : weekdays[d.getDay()],
      dateLabel: `${d.getDate()}/${d.getMonth() + 1}`,
      fullLabel: d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
    });
  }
  return days;
};

// State phục vụ việc gửi bình luận / review lên DB
const myReview = ref({
  rating: 10, // CineGo hỗ trợ thang điểm 10 ngôi sao
  comment: ''
});

// Dữ liệu mồi cục bộ cho thành phần phụ không ảnh hưởng luồng mua vé (Casts/Reviews) nếu DB trống
const mockCasts = ref([
  { id: 1, name: 'Diễn viên chính', character: 'Vai diễn xuất sắc', avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80' }
]);

const mockReviews = ref([
  { id: 1, user_name: 'Khách hàng CineGo', rating: 5, comment: 'Hệ thống rạp chiếu phim hiện đại, âm thanh bùng nổ!', created_at: new Date().toISOString() }
]);

// 🔥 HÀM LẤY CHI TIẾT PHIM THUẦN TỪ DATABASE
const fetchMovieDetail = async () => {
  const movieId = route.params.id;
  try {
    const response = await api.get(`/movies/${movieId}`);
    // Chấp nhận cấu trúc bọc dữ liệu chuẩn của API
    const resData = response.data?.data || response.data;
    
    if (resData) {
      movie.value = resData;
      bookingStore.selectMovie(movie.value);
      console.log('=== ĐÃ TẢI CHI TIẾT PHIM TỪ DB ===', movie.value);
    } else {
      console.error('Không tìm thấy dữ liệu bộ phim này trong DB.');
    }
  } catch (err) {
    console.error('Lỗi API lấy chi tiết phim từ cơ sở dữ liệu:', err);
    // Nếu trong Pinia store trang chủ đã nạp sẵn phim này thì lôi ra hiển thị tiếp
    if (bookingStore.selectedMovie && bookingStore.selectedMovie.id == movieId) {
      movie.value = bookingStore.selectedMovie;
    }
  }
};

// 🔥 HÀM LẤY CÁC NGÀY CÓ SUẤT CHIẾU TRONG TƯƠNG LAI
const fetchAvailableDates = async () => {
  if (!movie.value) return;
  try {
    const response = await api.get(`/movies/${movie.value.id}/available-dates`);
    const dates = response.data?.data || [];
    
    if (dates.length > 0) {
      const weekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
      const todayStr = new Date().toISOString().split('T')[0];
      
      availableDays.value = dates.map(dateStr => {
        const d = new Date(dateStr);
        return {
          dateStr: dateStr,
          weekday: dateStr === todayStr ? 'Hôm nay' : weekdays[d.getDay()],
          dateLabel: `${d.getDate()}/${d.getMonth() + 1}`,
          fullLabel: d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
        };
      });
    } else {
      availableDays.value = defaultDays(); // Fallback nếu phim chưa có lịch chiếu
    }
  } catch (err) {
    console.error('Lỗi lấy ngày chiếu:', err);
    availableDays.value = defaultDays();
  }
};

// 🔥 HÀM LẤY SUẤT CHIẾU THEO PHÒNG THUẦN TỪ DATABASE
const fetchShowtimes = async () => {
  if (!movie.value || availableDays.value.length === 0) return;
  loadingShowtimes.value = true;
  selectedShowtime.value = null;

  const dateStr = availableDays.value[selectedDayIndex.value].dateStr;
  try {
    // Gọi route public: /api/movies/{id}/showtimes?date=YYYY-MM-DD
    const response = await api.get(`/movies/${movie.value.id}/showtimes`, {
      params: { date: dateStr }
    });
    
    const resData = response.data?.data || response.data;
    showtimesByRoom.value = Array.isArray(resData) ? resData : [];
    console.log('=== ĐÃ TẢI LỊCH CHIẾU TỪ DB ===', showtimesByRoom.value);
  } catch (err) {
    console.error('Lỗi khi lấy danh sách suất chiếu từ DB:', err);
    showtimesByRoom.value = []; // Trả về mảng rỗng nếu DB chưa được thiết lập suất chiếu
  } finally {
    loadingShowtimes.value = false;
  }
};

// 🔥 HÀM GỬI REVIEW LƯU TRỰC TIẾP VÀO DATABASE LARAVEL
const submitReview = async () => {
  if (!myReview.value.comment.trim()) {
    alert('Vui lòng nhập nội dung đánh giá trước khi gửi!');
    return;
  }
  
  try {
    const dataPost = {
      movie_id: movie.value.id,
      rating: myReview.value.rating,
      comment: myReview.value.comment
    };
    
    // Gửi request lên API lưu vào bảng reviews
    const response = await api.post(`/movies/${movie.value.id}/reviews`, dataPost);
    const savedReview = response.data?.data || response.data;
    
    if (!movie.value.reviews) {
      movie.value.reviews = [];
    }
    
    // Thêm đánh giá vừa tạo lên đầu danh sách hiển thị công khai
    movie.value.reviews.unshift(savedReview);
    myReview.value.comment = ''; // Dọn dẹp ô nhập liệu
    alert('Đăng bài đánh giá thành công lên CineGo!');
  } catch (err) {
    console.error('Lỗi khi lưu review vào Database:', err);
    alert('Không thể gửi đánh giá, vui lòng kiểm tra trạng thái đăng nhập hệ thống!');
  }
};

const getRatingClass = (rating) => {
  if (!rating) return 'rating-g';
  const r = rating.toUpperCase();
  if (r.includes('18') || r === 'R') return 'rating-t18';
  if (r.includes('16')) return 'rating-t16';
  if (r.includes('13') || r === 'PG-13') return 'rating-t13';
  return 'rating-g';
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const selectShowtime = (showtime) => {
  selectedShowtime.value = showtime;
};

// Theo dõi thay đổi thanh ngày chiếu để gọi lại suất chiếu tương ứng của DB
watch(selectedDayIndex, () => {
  fetchShowtimes();
});

onMounted(async () => {
  await fetchMovieDetail();
  await fetchAvailableDates();
  await fetchShowtimes();
});

// Chuyển tiếp sang màn hình chọn ghế ngồi thực tế
const proceedToSeatSelection = () => {
  if (selectedShowtime.value) {
    const formattedShowtime = {
      ...selectedShowtime.value,
      date: availableDays.value[selectedDayIndex.value].dateStr,
      dateLabel: availableDays.value[selectedDayIndex.value].fullLabel
    };
    bookingStore.selectShowtime(formattedShowtime);
    router.push('/booking/seats');
  }
};
</script>


<style scoped>
@import '../../assets/css/pages/movie-detail.css';
</style>
