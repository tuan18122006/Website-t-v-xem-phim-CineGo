<template>
  <div class="my-bookings-view">
    <div class="page-header glass-panel">
      <div>
        <h1>Lịch sử vé của bạn</h1>
        <p>Danh sách các vé đã đặt và khả năng đánh giá phim sau khi suất chiếu kết thúc.</p>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>Đang tải lịch sử vé...</p>
    </div>

    <div v-else>
      <div v-if="bookings.length === 0" class="empty-state">
        <p>Bạn chưa có vé nào trong lịch sử.</p>
        <router-link to="/mua-ve" class="btn-primary">Đặt vé ngay</router-link>
      </div>

      <div v-else class="bookings-list">
        <div v-for="booking in bookings" :key="booking.booking_id" class="booking-card glass-panel">
          <div class="booking-card-left">
            <img :src="booking.poster_url || defaultPoster" :alt="booking.movie_title" class="movie-poster" />
          </div>
          <div class="booking-card-right">
            <div class="booking-meta">
              <div>
                <h3 class="movie-title">{{ booking.movie_title }}</h3>
                <p class="movie-subtitle">Mã vé: <strong>{{ booking.booking_code }}</strong></p>
              </div>
              <div class="status-pill" :class="booking.payment_status">
                {{ booking.payment_status === 'paid' ? 'Đã thanh toán' : booking.payment_status }}
              </div>
            </div>

            <div class="booking-details-grid">
              <div>
                <span class="label">Phòng</span>
                <p>{{ booking.room_name || 'Không xác định' }}</p>
              </div>
              <div>
                <span class="label">Suất chiếu</span>
                <p>{{ booking.showtime_start || '—' }}</p>
              </div>
              <div>
                <span class="label">Kết thúc</span>
                <p>{{ booking.showtime_end || '—' }}</p>
              </div>
              <div>
                <span class="label">Trạng thái vé</span>
                <p>{{ booking.booking_status }}</p>
              </div>
            </div>

            <div class="booking-actions">
              <button
                v-if="booking.can_review"
                @click="goToReview(booking.movie_id)"
                class="btn-action btn-review"
              >
                Đánh giá
              </button>
              <button
                v-else
                class="btn-action btn-disabled"
                :disabled="true"
              >
                {{ booking.review_message }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';

const router = useRouter();
const isLoading = ref(true);
const bookings = ref([]);
const errorMessage = ref(null);

const defaultPoster = 'https://images.unsplash.com/photo-1524985069026-dd778a71c7b4?auto=format&fit=crop&w=500&q=80';

const loadBookings = async () => {
  isLoading.value = true;
  errorMessage.value = null;

  try {
    const response = await api.get('/bookings/history');
    bookings.value = response.data?.data || [];
  } catch (err) {
    console.error('Lỗi khi tải lịch sử vé:', err);
    errorMessage.value = err.response?.data?.message || 'Không thể tải lịch sử vé. Vui lòng thử lại sau.';
  } finally {
    isLoading.value = false;
  }
};

const goToReview = (movieId) => {
  if (!movieId) return;
  router.push({ name: 'movie-detail', params: { id: movieId }, query: { review: '1' } });
};

onMounted(() => {
  loadBookings();
});
</script>

<style scoped>
.my-bookings-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 20px 40px;
}

.page-header {
  padding: 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 28px;
}

.page-header h1 {
  margin: 0;
  font-size: 32px;
  font-weight: 800;
}

.page-header p {
  margin: 6px 0 0;
  color: var(--text-secondary);
}

.loading-state,
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 60px 0;
}

.spinner {
  width: 42px;
  height: 42px;
  border: 4px solid rgba(255,255,255,0.12);
  border-top-color: var(--accent-pink);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.bookings-list {
  display: grid;
  gap: 20px;
}

.booking-card {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 20px;
  padding: 22px;
}

.movie-poster {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 18px;
}

.booking-card-right {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.booking-meta {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  align-items: flex-start;
}

.movie-title {
  margin: 0;
  font-size: 22px;
  font-weight: 700;
}

.movie-subtitle {
  margin: 6px 0 0;
  color: var(--text-secondary);
}

.status-pill {
  padding: 10px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
}

.status-pill.paid {
  background: rgba(46, 204, 113, 0.12);
  color: #27ae60;
}

.booking-details-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin: 24px 0;
}

.label {
  display: block;
  color: var(--text-secondary);
  font-size: 13px;
  margin-bottom: 6px;
}

.booking-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.btn-action {
  padding: 12px 20px;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 700;
  transition: transform 0.15s ease;
}

.btn-action:hover {
  transform: translateY(-1px);
}

.btn-review {
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
}

.btn-disabled {
  background: rgba(255, 255, 255, 0.08);
  color: var(--text-secondary);
  cursor: not-allowed;
}

.btn-primary {
  display: inline-block;
  padding: 10px 20px;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
  border-radius: 12px;
  text-decoration: none;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
