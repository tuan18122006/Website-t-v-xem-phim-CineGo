<template>
  <div class="top-movies-container">
    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="header-content">
        <span class="hero-tag">TRENDING & TOP RATED</span>
        <h1 class="page-title gradient-text-accent">Bảng Xếp Hạng Phim CineGo</h1>
        <p class="page-desc">
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
    <div class="top-movies-content" v-if="filteredMovies.length > 0">
      
      <!-- PHÂN KHU 1: HERO (TOP 1) -->
      <div class="hero-top1" v-if="top1Movie">
        <div class="hero-backdrop" :style="{ backgroundImage: `url(${top1Movie.backdrop || top1Movie.poster})` }">
          <div class="hero-overlay"></div>
        </div>
        <div class="hero-top1-content">
          <div class="hero-badge">
            <span class="crown-icon">👑</span> #1 TOP RATED
          </div>
          <div class="hero-info-grid">
            <div class="hero-poster">
              <img :src="top1Movie.poster" :alt="top1Movie.title" />
            </div>
            <div class="hero-details">
              <h2 class="hero-movie-title">{{ top1Movie.title }}</h2>
              <div class="hero-meta">
                <span class="movie-age-badge" :class="top1Movie.ratingClass">{{ top1Movie.rating }}</span>
                <span class="movie-genres">{{ top1Movie.genres.join(' • ') }}</span>
                <span class="movie-duration">⏳ {{ top1Movie.duration }} phút</span>
                <span class="movie-imdb text-gold">⭐ {{ top1Movie.imdb }}</span>
              </div>
              <p class="hero-movie-desc">{{ top1Movie.description }}</p>
              <div class="hero-actions">
                <router-link to="/mua-ve" class="btn-book-now hero-btn pulse">
                  <span>Mua vé ngay</span>
                  <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.5" fill="none">
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                    <polyline points="12 5 19 12 12 5"></polyline>
                  </svg>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- PHÂN KHU 2: PODIUM (TOP 2 & 3) -->
      <div class="podium-grid" v-if="top23Movies.length > 0">
        <div v-for="(movie, index) in top23Movies" :key="movie.id" class="podium-card glass-panel" :class="'podium-rank-' + (index + 2)">
          <div class="podium-badge">
            <span class="medal-icon">{{ index === 0 ? '🥈' : '🥉' }}</span> 
            #{{ index + 2 }}
          </div>
          <div class="podium-content">
            <img :src="movie.poster" :alt="movie.title" class="podium-poster" />
            <div class="podium-info">
              <h3 class="podium-title">{{ movie.title }}</h3>
              <div class="podium-meta">
                <span class="movie-age-badge small" :class="movie.ratingClass">{{ movie.rating }}</span>
                <span class="text-gold">⭐ {{ movie.imdb }}</span>
              </div>
              <p class="podium-genres">{{ movie.genres.join(', ') }}</p>
              <router-link to="/mua-ve" class="btn-book-now small-btn">Mua vé</router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- PHÂN KHU 3: GRID LƯỚI (TOP 4 TRỞ ĐI) -->
      <div class="top-movies-grid" v-if="top4PlusMovies.length > 0">
        <div v-for="(movie, index) in top4PlusMovies" :key="movie.id" class="grid-movie-card">
          <div class="grid-poster-wrap">
            <div class="grid-rank-badge">#{{ index + 4 }}</div>
            <img :src="movie.poster" :alt="movie.title" class="grid-poster" />
            <div class="grid-hover-overlay">
              <span class="text-gold mb-2">⭐ {{ movie.imdb }}</span>
              <router-link to="/mua-ve" class="btn-book-now hover-btn">Mua vé</router-link>
            </div>
          </div>
          <div class="grid-movie-info">
            <h4 class="grid-title">{{ movie.title }}</h4>
            <p class="grid-genres">{{ movie.genres.join(', ') }}</p>
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
    poster: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80',
    backdrop: 'https://images.unsplash.com/photo-1626814026160-2237a95fc5a0?auto=format&fit=crop&w=1200&q=80'
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
  },
  {
    id: 104,
    title: 'Spider-Man: No Way Home',
    genres: ['Hành Động', 'Phiêu Lưu', 'Kỳ Ảo'],
    rating: 'T13',
    ratingClass: 'age-t13',
    description: 'Danh tính của Spider-Man đã bị tiết lộ. Giờ đây anh phải tìm đến Doctor Strange để nhờ giúp đỡ, dẫn đến những hậu quả đa vũ trụ khôn lường.',
    imdb: '8.2',
    duration: '148',
    release_date: '2026-06-10',
    poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 105,
    title: 'The Batman',
    genres: ['Hành Động', 'Tội Phạm', 'Bí Ẩn'],
    rating: 'T16',
    ratingClass: 'age-t16',
    description: 'Batman điều tra một loạt vụ án mạng do Riddler gây ra, dần khám phá ra những bí mật thâm độc giấu kín ở thành phố Gotham.',
    imdb: '7.8',
    duration: '176',
    release_date: '2026-06-12',
    poster: 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 106,
    title: 'Dune: Hành Tinh Cát',
    genres: ['Viễn Tưởng', 'Phiêu Lưu', 'Drama'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Hành trình của Paul Atreides trên hành tinh hoang mạc Arrakis, nơi chứa đựng loại hương liệu quý giá nhất vũ trụ và những âm mưu chính trị tàn bạo.',
    imdb: '8.0',
    duration: '155',
    release_date: '2026-06-15',
    poster: 'https://images.unsplash.com/photo-1629851722649-74d300057ea7?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 107,
    title: 'Top Gun: Maverick',
    genres: ['Hành Động', 'Drama'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Sau hơn ba mươi năm phục vụ với tư cách là một trong những phi công hàng đầu của Hải quân, Pete "Maverick" Mitchell phải đối mặt với bóng ma quá khứ.',
    imdb: '8.3',
    duration: '130',
    release_date: '2026-06-20',
    poster: 'https://images.unsplash.com/photo-1599839619722-39751411ea63?auto=format&fit=crop&w=400&q=80'
  }
]);

const allTimeMovies = ref([
  {
    id: 201,
    title: 'Titanic',
    genres: ['Lãng Mạn', 'Drama'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Câu chuyện tình bi kịch và lãng mạn giữa Jack và Rose trên chuyến tàu huyền thoại Titanic.',
    imdb: '7.9',
    duration: '194',
    release_date: '1997-12-19',
    poster: 'https://images.unsplash.com/photo-1582239474718-d716499878a8?auto=format&fit=crop&w=400&q=80',
    backdrop: 'https://images.unsplash.com/photo-1582239474718-d716499878a8?auto=format&fit=crop&w=1200&q=80'
  },
  {
    id: 202,
    title: 'Avengers: Endgame',
    genres: ['Hành Động', 'Viễn Tưởng', 'Kỳ Ảo'],
    rating: 'T13',
    ratingClass: 'age-t13',
    description: 'Sau sự kiện tàn khốc của Infinity War, các Avengers còn sống sót hợp lực để đảo ngược hành động của Thanos.',
    imdb: '8.4',
    duration: '181',
    release_date: '2019-04-26',
    poster: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 203,
    title: 'Avatar',
    genres: ['Kỳ Ảo', 'Viễn Tưởng', 'Hành Động'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Một cựu lính thủy đánh bộ bị liệt được phái đến mặt trăng Pandora với một nhiệm vụ độc đáo và trở nên giằng xé giữa việc làm theo mệnh lệnh hay bảo vệ thế giới mới của mình.',
    imdb: '7.9',
    duration: '162',
    release_date: '2009-12-18',
    poster: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 204,
    title: 'Ký Sinh Trùng (Parasite)',
    genres: ['Thriller', 'Drama', 'Hài Kịch'],
    rating: 'T18',
    ratingClass: 'age-t18',
    description: 'Lòng tham và sự phân biệt giai cấp đe dọa mối quan hệ cộng sinh mới được hình thành giữa gia đình Park giàu có và gia đình Kim nghèo khó.',
    imdb: '8.5',
    duration: '132',
    release_date: '2019-05-30',
    poster: 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 205,
    title: 'The Dark Knight',
    genres: ['Hành Động', 'Tội Phạm', 'Drama'],
    rating: 'T16',
    ratingClass: 'age-t16',
    description: 'Khi mối đe dọa được gọi là Joker tàn phá và gây hỗn loạn cho người dân Gotham, Batman phải chấp nhận một trong những bài kiểm tra tâm lý và thể chất lớn nhất.',
    imdb: '9.0',
    duration: '152',
    release_date: '2008-07-18',
    poster: 'https://images.unsplash.com/photo-1509347528160-9a9e33742cdb?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 206,
    title: 'Inception',
    genres: ['Hành Động', 'Viễn Tưởng', 'Thriller'],
    rating: 'T13',
    ratingClass: 'age-t13',
    description: 'Một kẻ trộm chuyên đánh cắp bí mật của công ty thông qua việc sử dụng công nghệ chia sẻ giấc mơ được giao một nhiệm vụ đảo ngược: cấy ghép một ý tưởng vào tâm trí của một CEO.',
    imdb: '8.8',
    duration: '148',
    release_date: '2010-07-16',
    poster: 'https://images.unsplash.com/photo-1629851722649-74d300057ea7?auto=format&fit=crop&w=400&q=80'
  },
  {
    id: 207,
    title: 'Interstellar',
    genres: ['Viễn Tưởng', 'Phiêu Lưu', 'Drama'],
    rating: 'PG-13',
    ratingClass: 'age-pg13',
    description: 'Một nhóm các nhà thám hiểm du hành qua một lỗ sâu trong không gian với nỗ lực đảm bảo sự sống còn của nhân loại.',
    imdb: '8.7',
    duration: '169',
    release_date: '2014-11-07',
    poster: 'https://images.unsplash.com/photo-1635805737707-575885ab0820?auto=format&fit=crop&w=400&q=80'
  }
]);

const filteredMovies = computed(() => {
  return activeFilter.value === 'trending' ? trendingMovies.value : allTimeMovies.value;
});

const top1Movie = computed(() => filteredMovies.value[0]);
const top23Movies = computed(() => filteredMovies.value.slice(1, 3));
const top4PlusMovies = computed(() => filteredMovies.value.slice(3));

const formatDate = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  return isNaN(d) ? '—' : d.toLocaleDateString('vi-VN');
};
</script>

<style scoped>
/* ========== PAGE HEADER ========== */
.page-header {
  background: linear-gradient(135deg, #111111 0%, #000000 100%);
  padding: 60px 24px;
  text-align: center;
  border-bottom: 1px solid rgba(229, 9, 20, 0.1);
  margin-bottom: 40px;
}
.header-content { max-width: 800px; margin: 0 auto; }
.hero-tag { font-size: 11px; font-weight: 800; color: #e50914; letter-spacing: 2px; display: block; margin-bottom: 12px; }
.page-title { font-size: 38px; font-weight: 800; color: #ffffff; margin-bottom: 16px; letter-spacing: -1px; }
.page-desc { font-size: 16px; color: #cbd5e1; line-height: 1.6; margin-bottom: 30px; }

/* TABS */
.category-tabs { display: inline-flex; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); padding: 6px; border-radius: 14px; }
.tab-btn { background: transparent; border: none; color: #94a3b8; font-weight: 700; font-size: 13.5px; padding: 12px 24px; border-radius: 10px; cursor: pointer; transition: all 0.25s ease; }
.tab-btn:hover { color: #ffffff; }
.tab-btn.active { background: #e50914; color: #ffffff; box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3); }

/* MAIN CONTENT CONTAINER */
.top-movies-content { max-width: 1200px; margin: 0 auto; padding: 0 20px 80px; }

/* ========== HERO TOP 1 ========== */
.hero-top1 { position: relative; border-radius: 24px; overflow: hidden; margin-bottom: 40px; background-color: #000; box-shadow: 0 20px 50px rgba(0,0,0,0.5); min-height: 450px; display: flex; align-items: center; }
.hero-backdrop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center top; opacity: 0.5; filter: blur(8px); transform: scale(1.05); }
.hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to right, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.7) 50%, rgba(0,0,0,0.4) 100%); }
.hero-top1-content { position: relative; z-index: 10; padding: 50px; width: 100%; }
.hero-badge { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #ffd700, #ffa500); color: #000; font-weight: 900; padding: 8px 16px; border-radius: 30px; font-size: 14px; letter-spacing: 1px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4); }
.crown-icon { font-size: 18px; }
.hero-info-grid { display: flex; gap: 40px; align-items: center; }
.hero-poster { width: 220px; height: 330px; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.8); flex-shrink: 0; border: 2px solid rgba(255,255,255,0.1); }
.hero-poster img { width: 100%; height: 100%; object-fit: cover; }
.hero-details { flex: 1; color: white; }
.hero-movie-title { font-size: 42px; font-weight: 900; margin: 0 0 15px 0; line-height: 1.1; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
.hero-meta { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; font-size: 14px; font-weight: 600; flex-wrap: wrap; }
.movie-age-badge { padding: 4px 8px; border-radius: 6px; color: white; font-size: 12px; }
.age-t13 { background-color: #3b82f6; }
.age-t16 { background-color: #ef4444; }
.age-pg13 { background-color: #10b981; }
.age-t18 { background-color: #b91c1c; }
.movie-genres { color: #fca5a5; }
.movie-duration { color: #cbd5e1; }
.text-gold { color: #fbbf24 !important; font-weight: 800; }
.hero-movie-desc { font-size: 16px; line-height: 1.6; color: #94a3b8; max-width: 600px; margin-bottom: 30px; }
.btn-book-now { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #e50914; color: #ffffff; font-weight: 700; border-radius: 12px; transition: all 0.3s ease; cursor: pointer; text-decoration: none; border: none; }
.hero-btn { font-size: 16px; padding: 16px 32px; box-shadow: 0 8px 25px rgba(229, 9, 20, 0.4); }
.hero-btn:hover { background: #f00; transform: translateY(-3px); box-shadow: 0 12px 30px rgba(229, 9, 20, 0.6); }

/* Pulse Animation */
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(229, 9, 20, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(229, 9, 20, 0); } 100% { box-shadow: 0 0 0 0 rgba(229, 9, 20, 0); } }
.pulse { animation: pulse 2s infinite; }

/* ========== PODIUM TOP 2 & 3 ========== */
.podium-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
.podium-card { position: relative; display: flex; align-items: center; gap: 24px; padding: 24px; border-radius: 20px; background: white; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.05); overflow: hidden; transition: transform 0.3s; }
.podium-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
.podium-badge { position: absolute; top: 0; right: 0; padding: 8px 20px; border-bottom-left-radius: 20px; font-weight: 900; font-size: 16px; color: white; display: flex; align-items: center; gap: 6px; }
.podium-rank-2 .podium-badge { background: linear-gradient(135deg, #c0c0c0, #708090); }
.podium-rank-3 .podium-badge { background: linear-gradient(135deg, #cd7f32, #8b4513); }
.podium-content { display: flex; gap: 24px; width: 100%; align-items: center; }
.podium-poster { width: 120px; height: 170px; border-radius: 12px; object-fit: cover; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
.podium-info { flex: 1; display: flex; flex-direction: column; align-items: flex-start; }
.podium-title { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 10px 0; }
.podium-meta { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.podium-genres { font-size: 13px; color: #64748b; margin: 0 0 15px 0; }
.small-btn { padding: 10px 20px; font-size: 13px; border-radius: 8px; }
.small-btn:hover { background: #f00; box-shadow: 0 4px 10px rgba(229, 9, 20, 0.3); }

/* ========== GRID LƯỚI TOP 4+ ========== */
.top-movies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
.grid-movie-card { display: flex; flex-direction: column; transition: transform 0.3s; }
.grid-movie-card:hover { transform: translateY(-5px); }
.grid-poster-wrap { position: relative; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 20px rgba(0,0,0,0.1); aspect-ratio: 2/3; margin-bottom: 12px; }
.grid-poster { width: 100%; height: 100%; object-fit: cover; }
.grid-rank-badge { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.8); color: white; font-weight: 900; padding: 4px 10px; border-radius: 8px; font-size: 14px; z-index: 2; border: 1px solid rgba(255,255,255,0.2); }
.grid-hover-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
.grid-poster-wrap:hover .grid-hover-overlay { opacity: 1; }
.hover-btn { padding: 8px 20px; font-size: 13px; border-radius: 8px; background: #e50914; margin-top: 10px; }
.hover-btn:hover { background: #f00; transform: scale(1.05); }
.grid-movie-info { padding: 0 4px; }
.grid-title { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.grid-genres { font-size: 12px; color: #64748b; margin: 0; }
.mb-2 { margin-bottom: 8px; }

/* Responsive */
@media (max-width: 900px) {
  .hero-info-grid { flex-direction: column; text-align: center; gap: 20px; }
  .hero-details { display: flex; flex-direction: column; align-items: center; }
  .hero-meta { justify-content: center; }
  .podium-grid { grid-template-columns: 1fr; }
  .podium-card { padding-top: 40px; }
}
</style>
