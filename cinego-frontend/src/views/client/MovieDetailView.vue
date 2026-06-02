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
        <button 
          v-for="(day, index) in availableDays" 
          :key="index" 
          class="date-btn"
          :class="{ active: selectedDayIndex === index }"
          @click="selectedDayIndex = index"
        >
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
            <button 
              v-for="showtime in room.showtimes" 
              :key="showtime.id"
              class="showtime-btn"
              :class="{ active: selectedShowtime?.id === showtime.id }"
              @click="selectShowtime(showtime)"
            >
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
          <p>Suất chiếu đã chọn: <strong>{{ selectedShowtime.start_time }}</strong> - Phòng: <strong>{{ selectedShowtime.room_name }}</strong></p>
          <p>Ngày chiếu: <strong>{{ availableDays[selectedDayIndex].fullLabel }}</strong></p>
        </div>
        <button @click="proceedToSeatSelection" class="btn-proceed">
          Tiếp Tục Chọn Ghế
        </button>
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
.movie-detail-view {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.detail-header {
  padding: 40px;
  display: flex;
  gap: 40px;
  flex-wrap: wrap;
}

.poster-container {
  position: relative;
  width: 280px;
  flex-shrink: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
  border: 1px solid var(--border-glass);
}

.detail-poster {
  width: 100%;
  height: auto;
  display: block;
}

.detail-rating-badge {
  position: absolute;
  top: 16px;
  left: 16px;
  color: white;
  font-size: 14px;
  font-weight: 800;
  padding: 6px 12px;
  border-radius: var(--radius-sm);
  box-shadow: 0 4px 10px rgba(0,0,0,0.5);
}

.rating-g { background: #00cd6c; }
.rating-t13 { background: #ffaa00; }
.rating-t16 { background: #ff5500; }
.rating-t18 { background: #e60000; }

.info-container {
  flex: 1;
  min-width: 320px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.movie-title {
  font-size: 40px;
  font-weight: 800;
  line-height: 1.2;
}

.genres-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.detail-genre {
  background: var(--bg-tertiary);
  color: var(--accent-pink);
  border: 1px solid rgba(255, 0, 127, 0.15);
  font-size: 13px;
  font-weight: 600;
  padding: 4px 14px;
  border-radius: var(--radius-full);
}

.metadata-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 16px;
  padding: 20px;
  background: rgba(255,255,255,0.02);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-glass);
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.meta-label {
  color: var(--text-muted);
  font-size: 12px;
  text-transform: uppercase;
}

.meta-value {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 15px;
}

.movie-description h3 {
  font-size: 18px;
  margin-bottom: 8px;
  color: var(--text-primary);
}

.movie-description p {
  color: var(--text-secondary);
  font-size: 15px;
  line-height: 1.6;
}

.schedule-section {
  padding: 40px;
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.section-title {
  font-size: 24px;
  font-weight: 700;
}

.date-selector {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.date-btn {
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--border-glass);
  color: var(--text-secondary);
  padding: 12px 20px;
  border-radius: var(--radius-md);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-width: 100px;
  transition: var(--transition-smooth);
}

.date-btn:hover {
  border-color: var(--accent-violet);
  background: rgba(112,0,255,0.05);
}

.date-btn.active {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  color: white;
  border-color: transparent;
  box-shadow: var(--shadow-neon-pink);
}

.day-name {
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.day-date {
  font-size: 18px;
  font-weight: 800;
}

.showtimes-by-room {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.room-row {
  display: flex;
  padding: 24px;
  gap: 24px;
  flex-wrap: wrap;
  align-items: center;
}

.room-info {
  flex-shrink: 0;
  width: 220px;
}

.room-name {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 4px;
}

.room-type {
  color: var(--text-muted);
  font-size: 12px;
}

.showtimes-grid {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  flex: 1;
}

.showtime-btn {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-glass);
  color: var(--text-primary);
  padding: 10px 20px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  transition: var(--transition-smooth);
}

.showtime-btn:hover {
  border-color: var(--accent-pink);
  transform: translateY(-2px);
}

.showtime-btn.active {
  border-color: var(--accent-pink);
  background: rgba(255, 0, 127, 0.1);
  box-shadow: var(--shadow-neon-pink);
}

.time-label {
  font-size: 16px;
  font-weight: 700;
}

.seat-available {
  font-size: 10px;
  color: var(--text-muted);
}

.showtime-btn.active .seat-available {
  color: var(--accent-pink);
}

.no-showtimes {
  text-align: center;
  padding: 40px;
  color: var(--text-muted);
  font-size: 15px;
}

.schedule-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid var(--border-glass);
  padding-top: 24px;
  flex-wrap: wrap;
  gap: 20px;
}

.selected-summary p {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
}

.selected-summary strong {
  color: var(--text-primary);
}

.btn-proceed {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  color: white;
  padding: 14px 32px;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 700;
  cursor: pointer;
  box-shadow: var(--shadow-neon-pink);
  transition: var(--transition-bounce);
}

.btn-proceed:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 25px rgba(255, 0, 127, 0.5);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: var(--accent-pink);
  border-radius: 50%;
  animation: spin 1s infinite linear;
  margin-bottom: 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
