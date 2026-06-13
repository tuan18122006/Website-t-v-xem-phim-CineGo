<template>
  <div class="top-movies-container">
    <!-- HERO HEADER -->
    <div class="top-movies-hero">
      <div class="hero-glow"></div>
      <div class="hero-content">
        <span class="hero-tag">TRENDING & TOP RATED</span>
        <h1 class="hero-title gradient-text-accent">Bảng Xếp Hạng Phim CineGo</h1>
        <p class="hero-desc">
          Cập nhật liên tục xu hướng xem phim và xếp hạng các bộ phim ăn khách nhất, được chấm điểm cao nhất bởi người xem tại hệ thống rạp CineGo.
        </p>

        <!-- Category selector -->
        <div class="category-tabs">
          <button 
            class="tab-btn" 
            :class="{ active: activeFilter === 'trending' }"
            @click="activeFilter = 'trending'"
          >
            🔥 Đang càn quét phòng vé
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: activeFilter === 'alltime' }"
            @click="activeFilter = 'alltime'"
          >
            🏆 Phim hay nhất mọi thời đại
          </button>
        </div>
      </div>
    </div>

    <!-- MAIN LISTING -->
    <div class="top-movies-list-wrapper">
      <div class="movies-rank-list">
        <div v-for="(movie, index) in filteredMovies" :key="movie.id" class="rank-card glass-panel">
          <!-- Rank Number Tag -->
          <div class="rank-number-box" :class="'rank-' + (index + 1)">
            <span class="rank-num-val">#{{ index + 1 }}</span>
            <span v-if="index === 0" class="rank-crown">👑 Top 1</span>
          </div>

          <!-- Movie Poster -->
          <div class="movie-poster-box">
            <img :src="movie.poster" :alt="movie.title" class="poster-img" />
          </div>

          <!-- Movie Details -->
          <div class="movie-meta-details">
            <div class="title-row">
              <h2 class="movie-title">{{ movie.title }}</h2>
              <span class="movie-age-badge" :class="movie.ratingClass">{{ movie.rating }}</span>
            </div>
            
            <p class="movie-genres">{{ movie.genres.join(' • ') }}</p>
            <p class="movie-description">{{ movie.description }}</p>

            <div class="metrics-row">
              <div class="metric-item">
                <span class="metric-lbl">Điểm IMDb:</span>
                <strong class="metric-val text-gold">⭐ {{ movie.imdb }}</strong>
              </div>
              <div class="metric-item">
                <span class="metric-lbl">Thời lượng:</span>
                <strong class="metric-val">{{ movie.duration }} phút</strong>
              </div>
              <div class="metric-item">
                <span class="metric-lbl">Khởi chiếu:</span>
                <strong class="metric-val">{{ formatDate(movie.release_date) }}</strong>
              </div>
            </div>
          </div>

          <!-- CTA Booking Button -->
          <div class="rank-card-cta">
            <router-link to="/mua-ve" class="btn-book-now">
              <span>Mua vé ngay</span>
              <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2.5" fill="none">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 5"></polyline>
              </svg>
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const activeFilter = ref('trending');

const trendingMovies = ref([
  {
    id: 101,
    title: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
    genres: ['Hành Động', 'Viễn Tưởng', 'Kỳ Ảo'],
    rating: 'T13',
    ratingClass: 'age-t13',
    description: 'Doctor Strange du hành vào không gian đa vũ trụ phức tạp để bảo vệ thế giới khỏi những hiểm nguy khôn lường mang tính hủy diệt vũ trụ.',
    imdb: '8.5',
    duration: '126',
    release_date: '2026-05-15',
    poster: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 102,
    title: 'Avatar: Dòng Chảy Của Nước',
    genres: ['Kỳ Ảo', 'Viễn Tưởng', 'Hành Động'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Jake Sully và Neytiri phải rời bỏ tổ ấm và khám phá các vùng đất mới của đại dương Pandora khi mối đe dọa vũ trang quay trở lại tàn phá.',
    imdb: '8.3',
    duration: '192',
    release_date: '2026-06-01',
    poster: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 103,
    title: 'Kẻ Kiến Tạo (The Creator)',
    genres: ['Hành Động', 'Drama', 'Viễn Tưởng'],
    rating: 'T16',
    ratingClass: 'age-t16',
    description: 'Giữa cuộc chiến khốc liệt của nhân loại và trí tuệ nhân tạo, một cựu đặc vụ được giao nhiệm vụ ám sát một kiến trúc sư công nghệ bí ẩn.',
    imdb: '7.9',
    duration: '133',
    release_date: '2026-05-20',
    poster: 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=400&q=80'
  }
]);

const allTimeMovies = ref([
  {
    id: 201,
    title: 'Avatar: Dòng Chảy Của Nước',
    genres: ['Kỳ Ảo', 'Viễn Tưởng', 'Hành Động'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Jake Sully và Neytiri phải rời bỏ tổ ấm và khám phá các vùng đất mới của đại dương Pandora khi mối đe dọa vũ trang quay trở lại tàn phá.',
    imdb: '9.2',
    duration: '192',
    release_date: '2026-06-01',
    poster: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 202,
    title: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
    genres: ['Hành Động', 'Viễn Tưởng', 'Kỳ Ảo'],
    rating: 'T13',
    ratingClass: 'age-t13',
    description: 'Doctor Strange du hành vào không gian đa vũ trụ phức tạp để bảo vệ thế giới khỏi những hiểm nguy khôn lường mang tính hủy diệt vũ trụ.',
    imdb: '8.8',
    duration: '126',
    release_date: '2026-05-15',
    poster: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 203,
    title: 'Kẻ Kiến Tạo (The Creator)',
    genres: ['Hành Động', 'Drama', 'Viễn Tưởng'],
    rating: 'T16',
    ratingClass: 'age-t16',
    description: 'Giữa cuộc chiến khốc liệt của nhân loại và trí tuệ nhân tạo, một cựu đặc vụ được giao nhiệm vụ ám sát một kiến trúc sư công nghệ bí ẩn.',
    imdb: '8.1',
    duration: '133',
    release_date: '2026-05-20',
    poster: 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=400&q=80'
  }
]);

const filteredMovies = computed(() => {
  return activeFilter.value === 'trending' ? trendingMovies.value : allTimeMovies.value;
});

const formatDate = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  return isNaN(d) ? '—' : d.toLocaleDateString('vi-VN');
};
</script>

<style scoped>
.top-movies-container {
  width: 100%;
  min-height: 100vh;
  background-color: #ffffff;
  padding-bottom: 60px;
}

/* HERO HEADER */
.top-movies-hero {
  position: relative;
  background: linear-gradient(135deg, #1f0105 0%, #000000 100%);
  padding: 80px 24px;
  border-radius: 24px;
  overflow: hidden;
  margin-bottom: 40px;
  text-align: center;
  border: 1px solid rgba(229, 9, 20, 0.1);
}

.hero-glow {
  position: absolute;
  top: -50%;
  left: 20%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(155, 0, 14, 0.3) 0%, transparent 70%);
  filter: blur(60px);
  pointer-events: none;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
}

.hero-tag {
  font-size: 11px;
  font-weight: 800;
  color: #e50914;
  letter-spacing: 2px;
  display: block;
  margin-bottom: 12px;
}

.hero-title {
  font-size: 38px;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 16px;
  letter-spacing: -1px;
}

.hero-desc {
  font-size: 16px;
  color: #cbd5e1;
  line-height: 1.6;
  margin-bottom: 36px;
}

/* TABS SELECTION */
.category-tabs {
  display: inline-flex;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 6px;
  border-radius: 14px;
  backdrop-filter: blur(8px);
}

.tab-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-weight: 700;
  font-size: 13.5px;
  padding: 12px 24px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.tab-btn:hover {
  color: #ffffff;
}

.tab-btn.active {
  background: #e50914;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3);
}

/* RANK CARD LISTING */
.top-movies-list-wrapper {
  max-width: 1000px;
  margin: 0 auto;
  padding: 0 16px;
}

.movies-rank-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.rank-card {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 20px;
  padding: 24px;
  display: flex;
  align-items: center;
  gap: 24px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
  transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.rank-card:hover {
  transform: translateY(-2px);
  border-color: rgba(229, 9, 20, 0.15);
  box-shadow: 0 8px 30px rgba(229, 9, 20, 0.06);
}

/* Rank Number styling */
.rank-number-box {
  width: 80px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-right: 1px solid #f1f5f9;
  padding-right: 20px;
}

.rank-num-val {
  font-size: 32px;
  font-weight: 900;
  color: #94a3b8;
  font-style: italic;
}

.rank-1 .rank-num-val {
  background: linear-gradient(135deg, #ffd700, #ff8c00);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.rank-2 .rank-num-val {
  background: linear-gradient(135deg, #c0c0c0, #708090);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.rank-3 .rank-num-val {
  background: linear-gradient(135deg, #cd7f32, #8b4513);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.rank-crown {
  font-size: 10px;
  font-weight: 800;
  background: rgba(255, 140, 0, 0.1);
  color: #ff8c00;
  padding: 2px 6px;
  border-radius: 4px;
  margin-top: 4px;
  text-transform: uppercase;
}

/* Poster */
.movie-poster-box {
  width: 100px;
  height: 140px;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  flex-shrink: 0;
}

.poster-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Meta info */
.movie-meta-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 6px;
}

.movie-title {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.movie-age-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  color: #ffffff;
}

.age-t13 { background-color: #3b82f6; }
.age-t16 { background-color: #ef4444; }
.age-pg13 { background-color: #10b981; }

.movie-genres {
  font-size: 12.5px;
  font-weight: 600;
  color: #e50914;
  margin: 0 0 10px 0;
}

.movie-description {
  font-size: 13.5px;
  color: #64748b;
  margin: 0 0 14px 0;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.metrics-row {
  display: flex;
  gap: 24px;
  font-size: 12px;
}

.metric-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.metric-lbl {
  color: #94a3b8;
  font-weight: 500;
}

.metric-val {
  color: #334155;
  font-weight: 700;
}

.text-gold {
  color: #b2902b !important;
}

/* CTA BOOKING */
.rank-card-cta {
  flex-shrink: 0;
}

.btn-book-now {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #e50914;
  color: #ffffff;
  font-weight: 700;
  font-size: 13px;
  padding: 14px 22px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.15);
  transition: all 0.2s;
  cursor: pointer;
}

.btn-book-now:hover {
  background: #ff121f;
  transform: translateX(2px);
  box-shadow: 0 6px 18px rgba(229, 9, 20, 0.25);
}
</style>
