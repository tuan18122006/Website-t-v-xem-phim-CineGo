<template>
  <div class="momo-schedule-view">
    <!-- Header: Giống MoMo giới thiệu lịch chiếu -->
    <header class="schedule-header">
      <div class="header-container">
        <div class="header-content">
          <h1>Lịch chiếu phim CineGo</h1>
          <p class="subtitle">Khám phá thế giới điện ảnh tại rạp chiếu phim hàng đầu, cùng với thông tin lịch chiếu mới nhất và những bộ phim đang hot nhất hiện nay.</p>
          <ul class="header-features">
            <li><span class="check-icon">✓</span> Lịch chiếu luôn <strong>cập nhật sớm nhất</strong></li>
            <li><span class="check-icon">✓</span> Suất chiếu <strong>đầy đủ</strong> các phòng</li>
            <li><span class="check-icon">✓</span> Đặt vé lịch chiếu phim <strong>siêu nhanh</strong></li>
          </ul>
        </div>
        <div class="header-image-wrapper">
          <!-- Có thể thay bằng ảnh banner tĩnh mượt mà -->
          <div class="promo-box">
            <h3>Lịch chiếu phim trên CineGo</h3>
            <p>Ghế đẹp, giá hời, vào rạp <span>không chờ đợi</span></p>
          </div>
        </div>
      </div>
    </header>

    <!-- Body: Date Selector + Movies List -->
    <section class="schedule-body">
      <div class="schedule-container">
        <h2 class="section-title">Lịch chiếu phim</h2>
        
        <div class="main-schedule-box">
          <!-- Thanh Chọn Ngày -->
          <div class="date-selector-wrapper">
            <div class="date-selector">
              <button 
                v-for="day in availableDays" 
                :key="day.dateStr"
                class="date-tab"
                :class="{ active: selectedDate === day.dateStr }"
                @click="selectDate(day.dateStr)"
              >
                <span class="day-name">{{ day.weekday }}</span>
                <span class="day-date">{{ day.dateLabel }}</span>
              </button>
            </div>
          </div>

          <!-- Trạng Thái Tải -->
          <div v-if="loading" class="loading-state">
            <div class="spinner-accent"></div>
            <p>Đang lấy thông tin lịch chiếu...</p>
          </div>

          <!-- Trạng Thái Trống -->
          <div v-else-if="movies.length === 0" class="empty-state">
            <p>Không có suất chiếu nào được lên lịch trong ngày này.</p>
          </div>

          <!-- Danh Sách Phim -->
          <div v-else class="movie-list">
            <div v-for="movie in movies" :key="movie.movie_id" class="movie-item">
              <div class="movie-poster-box" @click="goToDetail(movie.movie_id)">
                <img :src="movie.poster_url" :alt="movie.title" class="movie-poster" />
              </div>
              
              <div class="movie-info">
                <div class="movie-title-row">
                  <span class="age-badge" :class="getRatingClass(movie.rating)">{{ movie.rating || 'G' }}</span>
                  <h3 class="movie-title" @click="goToDetail(movie.movie_id)">{{ movie.title }}</h3>
                </div>
                <p class="movie-genres">{{ movie.genres ? movie.genres.join(', ') : 'Chưa cập nhật' }}</p>
                
                <!-- Cấu trúc Suất chiếu -->
                <div class="showtimes-group">
                  <p class="showtimes-label">Chọn suất chiếu:</p>
                  <div class="showtimes-grid">
                    <button 
                      v-for="st in movie.showtimes" 
                      :key="st.id" 
                      class="showtime-btn"
                      @click="bookTicket(movie, st)"
                    >
                      <div class="st-time">{{ st.start_time }} <span class="st-end">~ {{ st.end_time }}</span></div>
                      <div class="st-format">
                        {{ st.format }} • {{ st.translation }}
                        <span v-if="st.is_sneak_show" class="sneak-badge-client">🔥 Suất chiếu sớm</span>
                      </div>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useBookingStore } from '../../stores/booking';
import api from '../../api/axios';

const router = useRouter();
const bookingStore = useBookingStore();

const loading = ref(false);
const movies = ref([]);
const availableDays = ref([]);
const selectedDate = ref('');

// Tạo danh sách 7 ngày
const generateDays = () => {
  const days = [];
  const weekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
  
  for (let i = 0; i < 7; i++) {
    const d = new Date();
    d.setDate(d.getDate() + i);
      days.push({
        dateStr: d.toISOString().split('T')[0],
        weekday: i === 0 ? 'Hôm nay' : weekdays[d.getDay()],
        dateLabel: d.getDate(),
        fullLabel: d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
      });
  }
  return days;
};

const selectDate = (dateStr) => {
  if (selectedDate.value === dateStr) return;
  selectedDate.value = dateStr;
  fetchShowtimes();
};

const fetchShowtimes = async () => {
  if (!selectedDate.value) return;
  
  loading.value = true;
  try {
    const response = await api.get(`/showtimes/by-date`, {
      params: { date: selectedDate.value }
    });
    movies.value = response.data.data || [];
  } catch (err) {
    console.error('Lỗi khi lấy lịch chiếu:', err);
    movies.value = [];
  } finally {
    loading.value = false;
  }
};

const goToDetail = (id) => {
  router.push(`/movie/${id}`);
};

const bookTicket = (movie, showtime) => {
  // Chuẩn bị dữ liệu movie
  const movieData = {
    id: movie.movie_id,
    title: movie.title,
    poster_url: movie.poster_url,
    rating: movie.rating
  };

  // Chuẩn bị dữ liệu showtime (cần có field date và dateLabel cho SeatSelection)
  const dayIndex = availableDays.value.findIndex(d => d.dateStr === selectedDate.value);
  const selectedDay = dayIndex !== -1 ? availableDays.value[dayIndex] : availableDays.value[0];
  
  const showtimeData = {
    ...showtime,
    date: selectedDate.value,
    dateLabel: selectedDay.fullLabel || `${selectedDay.weekday}, ${selectedDay.dateLabel}`
  };

  bookingStore.selectMovie(movieData);
  bookingStore.selectShowtime(showtimeData);
  router.push('/booking/seats');
};

const getRatingClass = (rating) => {
  if (!rating) return 'rating-g';
  const r = rating.toUpperCase();
  if (r.includes('18')) return 'rating-t18';
  if (r.includes('16')) return 'rating-t16';
  if (r.includes('13')) return 'rating-t13';
  return 'rating-g';
};

onMounted(() => {
  availableDays.value = generateDays();
  if (availableDays.value.length > 0) {
    selectedDate.value = availableDays.value[0].dateStr;
    fetchShowtimes();
  }
});
</script>

<style scoped>
.momo-schedule-view {
  background-color: #f5f5fa; /* Màu nền xám nhạt như MoMo */
  min-height: 100vh;
  padding-bottom: 60px;
}

/* HEADER STYLE */
.schedule-header {
  background-color: #fdf1f7; /* Hồng rất nhạt */
  padding: 40px 0;
  border-bottom: 1px solid #fce4ee;
}

.header-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 40px;
}

.header-content {
  flex: 1;
}

.header-content h1 {
  font-size: 28px;
  color: var(--accent-pink);
  margin-bottom: 15px;
  font-weight: 800;
}

.subtitle {
  color: #4b5563;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 20px;
  max-width: 450px;
}

.header-features {
  list-style: none;
  padding: 0;
  margin: 0;
}

.header-features li {
  margin-bottom: 10px;
  font-size: 14px;
  color: #4b5563;
  display: flex;
  align-items: center;
}

.check-icon {
  color: var(--accent-pink);
  margin-right: 8px;
  font-weight: bold;
}

.header-features strong {
  color: #1f2937;
  margin-left: 4px;
}

.header-image-wrapper {
  flex: 1;
  display: flex;
  justify-content: flex-end;
}

.promo-box {
  background: #fff0f6;
  border: 1px dashed var(--accent-pink);
  padding: 30px;
  border-radius: 12px;
  text-align: center;
}

.promo-box h3 {
  color: #1f2937;
  font-size: 20px;
  margin-bottom: 8px;
}

.promo-box span {
  color: var(--accent-pink);
  font-style: italic;
  font-weight: bold;
}

/* BODY STYLE */
.schedule-body {
  padding: 30px 0;
}

.schedule-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 20px;
}

.section-title {
  text-align: center;
  font-size: 24px;
  color: var(--accent-pink);
  margin-bottom: 20px;
  font-weight: 800;
}

.main-schedule-box {
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

/* DATE SELECTOR */
.date-selector-wrapper {
  border-bottom: 1px solid #e5e7eb;
  background-color: #ffffff;
}

.date-selector {
  display: flex;
  overflow-x: auto;
  scrollbar-width: none;
}

.date-selector::-webkit-scrollbar {
  display: none;
}

.date-tab {
  flex: 1;
  min-width: 80px;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: all 0.2s ease;
  border-right: 1px solid #f3f4f6;
}

.date-tab:last-child {
  border-right: none;
}

.date-tab:hover {
  background-color: #f9fafb;
}

.date-tab.active {
  border-bottom-color: var(--accent-pink);
  background-color: #fff0f6;
}

.day-name {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.date-tab.active .day-name {
  color: var(--accent-pink);
  font-weight: 600;
}

.day-date {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.date-tab.active .day-date {
  color: var(--accent-pink);
}

/* MOVIE LIST */
.movie-list {
  display: flex;
  flex-direction: column;
}

.movie-item {
  display: flex;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  gap: 20px;
}

.movie-item:last-child {
  border-bottom: none;
}

.movie-poster-box {
  width: 120px;
  flex-shrink: 0;
  cursor: pointer;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.movie-poster {
  width: 100%;
  height: auto;
  display: block;
  transition: transform 0.3s ease;
}

.movie-poster-box:hover .movie-poster {
  transform: scale(1.05);
}

.movie-info {
  flex: 1;
}

.movie-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
}

.age-badge {
  font-size: 11px;
  font-weight: 700;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
}

.rating-g { background-color: #2ecc71; }
.rating-t13 { background-color: #e67e22; }
.rating-t16 { background-color: #d35400; }
.rating-t18 { background-color: var(--accent-pink); }

.movie-title {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
  cursor: pointer;
  transition: color 0.2s ease;
}

.movie-title:hover {
  color: var(--accent-pink);
}

.movie-genres {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 15px;
}

.showtimes-group {
  background-color: #f9fafb;
  padding: 15px;
  border-radius: 8px;
  border: 1px solid #f3f4f6;
}

.showtimes-label {
  font-size: 13px;
  font-weight: 600;
  color: #4b5563;
  margin-bottom: 10px;
}

.showtimes-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.showtime-btn {
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 8px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 100px;
}

.showtime-btn:hover {
  border-color: var(--accent-pink);
  background-color: #fff0f6;
}

.st-time {
  font-size: 16px;
  font-weight: 700;
  color: var(--accent-pink);
  margin-bottom: 4px;
}

.st-end {
  font-size: 12px;
  color: #6b7280;
  font-weight: 400;
}

.st-format {
  font-size: 11px;
  color: #4b5563;
}

/* STATE STYLES */
.loading-state, .empty-state {
  padding: 60px 0;
  text-align: center;
  color: #6b7280;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.spinner-accent {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top-color: var(--accent-pink);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 15px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .header-container {
    flex-direction: column;
  }
  .header-image-wrapper {
    display: none;
  }
  .movie-item {
    flex-direction: column;
  }
  .movie-poster-box {
    width: 100px;
    margin: 0 auto;
  }
  .movie-title-row {
    justify-content: center;
  }
  .movie-genres {
    text-align: center;
  }
}
</style>
