<template>
  <div class="top-movies-container page-container">
    <div class="page-header text-center">
      <h1 class="page-title gradient-text-accent">
        <span class="fire-icon">🔥</span> BẢNG XẾP HẠNG CINEGO <span class="fire-icon">🔥</span>
      </h1>
      <p class="page-subtitle">Cập nhật liên tục dựa trên lượt vé bán ra và đánh giá thực tế từ mọt phim.</p>
    </div>

    <div class="tabs-container glass-panel">
      <button 
        class="tab-btn" 
        :class="{ active: currentTab === 'trending' }"
        @click="currentTab = 'trending'"
      >
        <span class="icon">🎟️</span> Top Bán Chạy (Doanh Thu)
      </button>
      <button 
        class="tab-btn" 
        :class="{ active: currentTab === 'rating' }"
        @click="currentTab = 'rating'"
      >
        <span class="icon">⭐</span> Top Đánh Giá Cao
      </button>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Đang tính toán bảng xếp hạng...</p>
    </div>

    <div v-else class="top-movies-list">
      <div 
        v-for="(movie, index) in sortedMovies" 
        :key="movie.id" 
        class="movie-rank-card glass-panel"
        :class="`top-${index + 1}`"
      >
        <div class="rank-number-box">
          <span class="rank-number">{{ index + 1 }}</span>
        </div>

        <div class="movie-poster-box">
          <img :src="movie.poster_url" :alt="movie.title" class="movie-poster" />
          <span class="age-badge" :class="getRatingClass(movie.rating_badge)">
            {{ movie.rating_badge || 'G' }}
          </span>
        </div>

        <div class="movie-info-box">
          <h3 class="movie-title glow-text-pink" @click="goToDetail(movie.id)">
            {{ movie.title }}
          </h3>
          <p class="movie-genres">{{ movie.genres?.join(', ') }}</p>
          
          <div class="movie-meta-row">
            <span><strong>Thời lượng:</strong> {{ movie.duration }} phút</span>
            <span><strong>Khởi chiếu:</strong> {{ formatDate(movie.release_date) }}</span>
          </div>
        </div>

        <div class="movie-stats-box">
          <div v-if="currentTab === 'trending'" class="stat-trending">
            <span class="stat-label">Vé đã bán</span>
            <span class="stat-value text-neon-cyan">
              {{ formatNumber(movie.tickets_sold) }} <small>vé</small>
            </span>
          </div>

          <div v-else class="stat-rating">
            <span class="stat-label">Đánh giá</span>
            <span class="stat-value text-neon-yellow">
              {{ movie.avg_rating?.toFixed(1) || '0.0' }}<small>/10</small>
            </span>
            <div class="stars-preview">
              <span v-for="s in Math.round(movie.avg_rating / 2)" :key="'f-'+s" class="star-f">★</span>
              <span v-for="s in (5 - Math.round(movie.avg_rating / 2))" :key="'e-'+s" class="star-e">★</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios'; // Axios instance đã cấu hình

const router = useRouter();
const currentTab = ref('trending'); // 'trending' hoặc 'rating'
const loading = ref(false);
const moviesList = ref([]);

// Mock data chuẩn cấu hình hiển thị top 10 phòng trường hợp API Backend chưa xong
const mockTopMovies = [
  { id: 1, title: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn', poster_url: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80', rating_badge: 'T13', duration: 126, release_date: '2026-05-15', genres: ['Hành Động', 'Viễn Tưởng'], tickets_sold: 14520, avg_rating: 9.2 },
  { id: 2, title: 'Avatar: Dòng Chảy Của Nước', poster_url: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80', rating_badge: 'PG-13', duration: 192, release_date: '2026-05-20', genres: ['Hành Động', 'Phiêu Lưu'], tickets_sold: 12800, avg_rating: 8.8 },
  { id: 3, title: 'Kẻ Kiến Tạo (The Creator)', poster_url: 'https://images.unsplash.com/photo-1534447677768-be436bb09401?auto=format&fit=crop&w=400&q=80', rating_badge: 'T16', duration: 133, release_date: '2026-06-01', genres: ['Hành Động', 'Viễn Tưởng'], tickets_sold: 9400, avg_rating: 7.5 }
];

// Hàm lấy dữ liệu bảng xếp hạng từ Laravel
const fetchLeaderboard = async () => {
  loading.value = true;
  try {
    // Thắng cấu hình Route Backend: Route::get('/movies/leaderboard', [MovieController::class, 'leaderboard']);
    const response = await api.get('/movies/leaderboard');
    moviesList.value = response.data;
  } catch (err) {
    console.warn('Lỗi gọi API Leaderboard, sử dụng dữ liệu Mock:', err);
    moviesList.value = mockTopMovies;
  } finally {
    loading.value = false;
  }
};

// Logic sắp xếp danh sách phim theo Tab hiện tại (Giới hạn tối đa Top 10)
const sortedMovies = computed(() => {
  const temp = [...moviesList.value];
  if (currentTab.value === 'trending') {
    // Sắp xếp giảm dần theo số lượng vé bán ra (booking_details count)
    return temp.sort((a, b) => b.tickets_sold - a.tickets_sold).slice(0, 10);
  } else {
    // Sắp xếp giảm dần theo trung bình cộng rating trong bảng reviews
    return temp.sort((a, b) => b.avg_rating - a.avg_rating).slice(0, 10);
  }
});

// Điều hướng xem chi tiết phim
const goToDetail = (id) => {
  router.push(`/movies/${id}`);
};

// Phân loại màu cho nhãn độ tuổi (Giữ nguyên cấu trúc đồng bộ hệ thống)
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
  return new Date(dateStr).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatNumber = (num) => {
  return num ? num.toLocaleString('vi-VN') : 0;
};

onMounted(() => {
  fetchLeaderboard();
});
</script>

<style scoped>
@import '../../assets/css/pages/top-movies.css';
</style>
