<template>
  <div class="quick-booking-view">
    <header class="booking-header glass-panel">
      <h1 class="glow-text-pink">Mua Vé Nhanh</h1>
      <p class="subtitle">Đặt vé xem phim trực tuyến siêu nhanh theo phong cách Moveek - Giữ ghế tức thì trong 10 phút!</p>
    </header>

    <!-- BƯỚC 1: CHỌN PHIM -->
    <section class="booking-step glass-panel">
      <div class="step-title">
        <span class="step-num">1</span>
        <h2>Chọn Phim</h2>
      </div>

      <div v-if="loadingMovies" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải phim...</p>
      </div>

      <div v-else class="movie-selector horizontal-scroll">
        <div 
          v-for="movie in movies" 
          :key="movie.id"
          class="movie-select-card"
          :class="{ active: selectedMovie?.id === movie.id }"
          @click="selectMovie(movie)"
        >
          <div class="poster-wrapper">
            <img :src="movie.poster_url" :alt="movie.title" />
            <span class="rating-badge" :class="getRatingClass(movie.rating)">{{ movie.rating }}</span>
          </div>
          <p class="title">{{ movie.title }}</p>
        </div>
      </div>
    </section>

    <!-- BƯỚC 2: CHỌN NGÀY CHIẾU -->
    <section class="booking-step glass-panel">
      <div class="step-title">
        <span class="step-num">2</span>
        <h2>Chọn Ngày Chiếu</h2>
      </div>

      <div class="date-selector-row">
        <button 
          v-for="(day, index) in availableDays" 
          :key="index"
          class="date-chip-btn"
          :class="{ active: selectedDayIndex === index }"
          @click="selectedDayIndex = index"
        >
          <span class="day-name">{{ day.weekday }}</span>
          <span class="day-date">{{ day.dateLabel }}</span>
        </button>
      </div>
    </section>

    <!-- BƯỚC 3: CHỌN SUẤT CHIẾU & ĐẶT VÉ -->
    <section class="booking-step glass-panel showtimes-step">
      <div class="step-title">
        <span class="step-num">3</span>
        <h2>Chọn Suất Chiếu & Đặt Vé</h2>
      </div>

      <div v-if="!selectedMovie" class="prompt-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="2.18" ry="2.18"></rect><line x1="7" y1="2" x2="7" y2="22"></line><line x1="17" y1="2" x2="17" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="2" y1="7" x2="7" y2="7"></line><line x1="2" y1="17" x2="7" y2="17"></line><line x1="17" y1="17" x2="22" y2="17"></line><line x1="17" y1="7" x2="22" y2="7"></line></svg>
        <p>Vui lòng chọn bộ phim ở bước 1 để hiển thị lịch chiếu.</p>
      </div>

      <div v-else-if="loadingShowtimes" class="loading-state">
        <div class="spinner"></div>
        <p>Đang quét các phòng chiếu và tải suất chiếu...</p>
      </div>

      <div v-else class="showtimes-container">
        <!-- Duyệt qua các phòng chiếu của rạp duy nhất -->
        <div 
          v-for="theater in virtualTheaters" 
          :key="theater.name"
          class="theater-row glass-panel"
        >
          <div class="theater-header">
            <div class="theater-badge">CINEGO</div>
            <h3 class="theater-name">{{ theater.name }}</h3>
          </div>

          <div class="showtimes-grid">
            <button 
              v-for="showtime in theater.showtimes" 
              :key="showtime.id"
              class="showtime-btn"
              @click="proceedToBooking(showtime)"
            >
              <div class="time-wrapper">
                <span class="time">{{ showtime.start_time }}</span>
                <span class="format-badge">{{ showtime.format || '2D' }}</span>
              </div>
              <div class="info-wrapper">
                <span class="translation">{{ showtime.translation || 'Phụ đề' }}</span>
                <span class="seats">Trống {{ showtime.available_seats }} ghế</span>
              </div>
            </button>
          </div>
        </div>

        <div v-if="virtualTheaters.length === 0" class="empty-state">
          <p>Rất tiếc! Không có suất chiếu nào của bộ phim <strong>{{ selectedMovie.title }}</strong> tại khu vực <strong>{{ selectedCity }}</strong> vào ngày <strong>{{ availableDays[selectedDayIndex].fullLabel }}</strong>.</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useBookingStore } from '../../stores/booking';
import api from '../../api/axios';

const router = useRouter();
const bookingStore = useBookingStore();

// States
const movies = ref([]);
const loadingMovies = ref(false);
const loadingShowtimes = ref(false);

const selectedMovie = ref(null);
const selectedDayIndex = ref(0);

const showtimesData = ref([]);

const availableDays = ref([]);

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

// Dynamic virtual theaters mapping from seeded DB rooms
const virtualTheaters = computed(() => {
  if (showtimesData.value.length === 0) return [];
  
  const result = [];
  showtimesData.value.forEach(room => {
    result.push({
      name: `Phòng chiếu: ${room.roomName}`,
      showtimes: room.showtimes
    });
  });

  return result;
});

const getRatingClass = (rating) => {
  if (!rating) return 'rating-g';
  const r = rating.toUpperCase();
  if (r.includes('18') || r === 'R') return 'rating-t18';
  if (r.includes('16')) return 'rating-t16';
  if (r.includes('13') || r === 'PG-13') return 'rating-t13';
  return 'rating-g';
};

const selectMovie = (movie) => {
  selectedMovie.value = movie;
};

const fetchMovies = async () => {
  loadingMovies.value = true;
  try {
    const response = await api.get('/movies');
    movies.value = response.data;
    if (movies.value.length > 0) {
      selectedMovie.value = movies.value[0]; // Auto-select first movie
    }
  } catch (err) {
    console.error('Fetch movies error:', err);
  } finally {
    loadingMovies.value = false;
  }
};

const fetchAvailableDates = async () => {
  if (!selectedMovie.value) return;
  try {
    const response = await api.get(`/movies/${selectedMovie.value.id}/available-dates`);
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
      selectedDayIndex.value = 0; // Reset ngày về ngày đầu tiên có lịch
    } else {
      availableDays.value = defaultDays();
    }
  } catch (err) {
    console.error('Fetch available dates error:', err);
    availableDays.value = defaultDays();
  }
};

const fetchShowtimes = async () => {
  if (!selectedMovie.value) return;
  loadingShowtimes.value = true;
  const dateStr = availableDays.value[selectedDayIndex.value].dateStr;
  try {
    const response = await api.get(`/movies/${selectedMovie.value.id}/showtimes?date=${dateStr}`);
    showtimesData.value = response.data;
  } catch (err) {
    console.error('Fetch showtimes error:', err);
    showtimesData.value = [];
  } finally {
    loadingShowtimes.value = false;
  }
};

// Watchers to update showtimes dynamically
watch(selectedMovie, async () => {
  await fetchAvailableDates();
  await fetchShowtimes();
});

watch(selectedDayIndex, () => {
  fetchShowtimes();
});

onMounted(async () => {
  await fetchMovies();
});

const proceedToBooking = (showtime) => {
  bookingStore.selectMovie(selectedMovie.value);
  
  const formattedShowtime = {
    ...showtime,
    date: availableDays.value[selectedDayIndex.value].dateStr,
    dateLabel: availableDays.value[selectedDayIndex.value].fullLabel
  };
  
  bookingStore.selectShowtime(formattedShowtime);
  router.push('/booking/seats');
};
</script>

<style scoped>
.quick-booking-view {
  display: flex;
  flex-direction: column;
  gap: 32px;
}

.booking-header {
  padding: 30px;
  text-align: center;
}

.booking-header h1 {
  font-size: 36px;
  font-weight: 800;
  margin-bottom: 8px;
}

.booking-header .subtitle {
  color: var(--text-secondary);
  font-size: 15px;
}

.booking-step {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.step-title {
  display: flex;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--border-glass);
  padding-bottom: 12px;
}

.step-num {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 14px;
  box-shadow: var(--shadow-neon-pink);
}

.step-title h2 {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
}

/* HORIZONTAL MOVIE SELECTOR */
.movie-selector {
  display: flex;
  gap: 20px;
  padding-bottom: 10px;
}

.movie-select-card {
  width: 140px;
  flex-shrink: 0;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.movie-select-card .poster-wrapper {
  position: relative;
  width: 100%;
  padding-top: 145%;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 2px solid transparent;
  transition: var(--transition-smooth);
}

.movie-select-card .poster-wrapper img {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: var(--transition-smooth);
}

.movie-select-card .title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-secondary);
  margin-top: 10px;
  text-align: center;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  height: 38px;
  line-height: 1.4;
  transition: var(--transition-smooth);
}

.movie-select-card:hover .poster-wrapper img {
  transform: scale(1.05);
}

.movie-select-card:hover .title {
  color: var(--text-primary);
}

.movie-select-card.active .poster-wrapper {
  border-color: var(--accent-pink);
  box-shadow: var(--shadow-neon-pink);
  transform: scale(1.02);
}

.movie-select-card.active .title {
  color: var(--accent-pink);
  text-shadow: 0 0 10px rgba(255, 0, 127, 0.3);
}

.rating-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  font-size: 10px;
  font-weight: 800;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
}

.rating-g { background: #00cd6c; }
.rating-t13 { background: #ffaa00; }
.rating-t16 { background: #ff5500; }
.rating-t18 { background: #e60000; }

/* FILTER AND DATE LAYOUT */
.two-column-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 32px;
}

.date-selector-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 10px;
}

.date-chip-btn {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-glass);
  color: var(--text-secondary);
  padding: 10px 20px;
  border-radius: var(--radius-md);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-width: 100px;
  transition: var(--transition-smooth);
}

.date-chip-btn:hover {
  border-color: var(--accent-pink);
  background: rgba(229, 9, 20, 0.04);
  color: var(--accent-pink);
}

.date-chip-btn.active {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  color: white !important;
  border-color: transparent;
  box-shadow: var(--shadow-neon-pink);
}

.date-chip-btn .day-name {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  opacity: 0.8;
}

.date-chip-btn.active .day-name {
  opacity: 1;
}

.date-chip-btn .day-date {
  font-size: 18px;
  font-weight: 800;
}

/* SHOWTIMES STAGE */
.prompt-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  color: var(--text-muted);
  text-align: center;
  gap: 16px;
}

.showtimes-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.theater-row {
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.theater-header {
  display: flex;
  align-items: center;
  gap: 12px;
}

.theater-badge {
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
  font-size: 11px;
  font-weight: 800;
  padding: 4px 10px;
  border-radius: 4px;
  text-transform: uppercase;
}

.theater-name {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
}

.showtimes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
}

.showtime-btn {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-glass);
  border-radius: var(--radius-sm);
  padding: 12px;
  cursor: pointer;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 8px;
  transition: var(--transition-smooth);
}

.showtime-btn:hover {
  border-color: var(--accent-pink);
  transform: translateY(-2px);
  box-shadow: var(--shadow-neon-pink);
}

.time-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.time-wrapper .time {
  font-size: 18px;
  font-weight: 800;
  color: white;
}

.format-badge {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid var(--border-glass);
  color: var(--text-secondary);
  font-size: 10px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 3px;
}

.info-wrapper {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: var(--text-muted);
}

.info-wrapper .translation {
  color: var(--accent-pink);
  font-weight: 600;
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
