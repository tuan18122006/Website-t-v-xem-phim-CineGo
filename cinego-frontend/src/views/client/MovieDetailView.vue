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
        <h1 class="movie-title glow-text-pink">{{ movie.title }}</h1>

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
              <span class="time-label">{{ showtime.start_time }}</span>
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

const availableDays = computed(() => {
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
});

const showtimesByRoom = ref([]);

// State phục vụ việc tạo review mới
const myReview = ref({
  rating: 5,
  comment: ''
});

// Dữ liệu giả lập Diễn viên khi API Laravel chưa kịp trả về dữ liệu quan hệ (Relationship)
const mockCasts = ref([
  { id: 1, name: 'Benedict Cumberbatch', character: 'Dr. Stephen Strange', avatar_url: 'https://images.jpg.photo/avatar/benedict.jpg' || 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80' },
  { id: 2, name: 'Elizabeth Olsen', character: 'Wanda Maximoff / Scarlet Witch', avatar_url: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80' },
  { id: 3, name: 'Xochitl Gomez', character: 'America Chavez', avatar_url: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=150&q=80' },
  { id: 4, name: 'Benedict Wong', character: 'Wong', avatar_url: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80' }
]);

// Dữ liệu giả lập Review người dùng
const mockReviews = ref([
  { id: 1, user_name: 'Phạm Thắng', rating: 5, comment: 'Kỹ xảo đỉnh cao xem ở phòng IMAX Dolby Atmos thực sự mãn nhãn, cốt truyện đa vũ trụ cuốn hút!', created_at: '2026-06-08' },
  { id: 2, user_name: 'Minh Anh', rating: 4, comment: 'Phim rất hay nhưng nhịp phim đoạn giữa hơi nhanh. Nhạc phim xuất sắc!', created_at: '2026-06-09' }
]);

// Hàm xử lý khi người dùng nhấn gửi Đánh giá
const submitReview = async () => {
  if (!myReview.value.comment.trim()) {
    alert('Vui lòng nhập nội dung đánh giá trước khi gửi!');
    return;
  }
  
  try {
    // Dữ liệu đóng gói gửi lên Laravel lưu vào bảng review
    const dataPost = {
      movie_id: movie.value.id,       // ứng với cột movie_id
      rating: myReview.value.rating,   // ứng với cột rating
      comment: myReview.value.comment  // ứng với cột comment
    };
    
    // Gửi request lên API Laravel
    const response = await api.post(`/movies/${movie.value.id}/reviews`, dataPost);
    
    // Nếu thành công, đẩy trực tiếp dữ liệu trả về vào mảng hiển thị công khai
    if (movie.value.reviews) {
      movie.value.reviews.unshift(response.data);
    }
    
    myReview.value.comment = ''; // Reset ô nhập
    alert('Đăng bài đánh giá thành công!');
  } catch (err) {
    console.error('Lỗi khi lưu review vào Database:', err);
    alert('Không thể gửi đánh giá, vui lòng kiểm tra lại đăng nhập!');
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

const fetchMovieDetail = async () => {
  const movieId = route.params.id;
  try {
    const response = await api.get(`/movies/${movieId}`);
    movie.value = response.data;
    bookingStore.selectMovie(movie.value);
  } catch (err) {
    console.error('Fetch movie detail error, fallback to store state or mock:', err);
    // Nếu store đã lưu thì lấy ra
    if (bookingStore.selectedMovie && bookingStore.selectedMovie.id == movieId) {
      movie.value = bookingStore.selectedMovie;
    } else {
      // Mock if completely new
      movie.value = {
        id: movieId,
        title: movieId == 1 ? 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn' : 'Avatar: Dòng Chảy Của Nước',
        poster_url: movieId == 1
          ? 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80'
          : 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80',
        rating: movieId == 1 ? 'T13' : 'PG-13',
        duration: movieId == 1 ? 126 : 192,
        release_date: '2026-05-15',
        genres: ['Hành Động', 'Viễn Tưởng', 'Kỳ Ảo']
      };
      bookingStore.selectMovie(movie.value);
    }
  }
};

const fetchShowtimes = async () => {
  if (!movie.value) return;
  loadingShowtimes.value = true;
  selectedShowtime.value = null;

  const dateStr = availableDays.value[selectedDayIndex.value].dateStr;
  try {
    const response = await api.get(`/movies/${movie.value.id}/showtimes?date=${dateStr}`);
    showtimesByRoom.value = response.data;
  } catch (err) {
    console.warn('Fetch showtimes API error, fallback to mock showtimes:');
    // Mock showtimes
    showtimesByRoom.value = [
      {
        roomId: 1,
        roomName: 'Phòng Chiếu LUXURY 01',
        showtimes: [
          { id: 101, start_time: '10:00', room_name: 'LUXURY 01', available_seats: 98, room_id: 1 },
          { id: 102, start_time: '13:30', room_name: 'LUXURY 01', available_seats: 82, room_id: 1 },
          { id: 103, start_time: '17:00', room_name: 'LUXURY 01', available_seats: 12, room_id: 1 },
          { id: 104, start_time: '20:30', room_name: 'LUXURY 01', available_seats: 44, room_id: 1 }
        ]
      },
      {
        roomId: 2,
        roomName: 'Phòng Chiếu IMAX 3D 02',
        showtimes: [
          { id: 201, start_time: '11:15', room_name: 'IMAX 02', available_seats: 110, room_id: 2 },
          { id: 202, start_time: '15:00', room_name: 'IMAX 02', available_seats: 75, room_id: 2 },
          { id: 203, start_time: '18:45', room_name: 'IMAX 02', available_seats: 3, room_id: 2 },
          { id: 204, start_time: '22:15', room_name: 'IMAX 02', available_seats: 92, room_id: 2 }
        ]
      }
    ];
  } finally {
    loadingShowtimes.value = false;
  }
};

watch(selectedDayIndex, () => {
  fetchShowtimes();
});

onMounted(async () => {
  await fetchMovieDetail();
  await fetchShowtimes();
});

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
