<template>
  <div class="home-view">
    <!-- DYNAMIC HERO SLIDER SECTION -->
    <header class="hero-slider">
      <div 
        v-for="(banner, idx) in banners" 
        :key="banner.id"
        class="slide"
        :class="{ active: activeSlideIndex === idx }"
        :style="{ backgroundImage: `linear-gradient(to right, rgba(18, 5, 8, 0.95) 35%, rgba(18, 5, 8, 0.55) 65%, rgba(18, 5, 8, 0.15) 100%), url(${banner.bg_url})` }"
      >
        <div class="slide-content">
          <span class="slide-badge glow-text-pink">SIÊU PHẨM HOT TUẦN NÀY</span>
          <h1 class="slide-title">{{ banner.title }}</h1>
          
          <div class="slide-meta">
            <span class="rating-badge-slide" :class="getRatingClass(banner.rating)">{{ banner.rating }}</span>
            <span class="meta-item-slide">⏱️ {{ banner.duration }} phút</span>
            <span class="meta-item-slide">📁 {{ banner.genres.join(', ') }}</span>
          </div>

          <p class="slide-desc">{{ banner.description }}</p>
          
          <div class="slide-actions">
            <button @click="goToMovie(banner.id)" class="btn-slide-book">ĐẶT VÉ NGAY</button>
            <router-link to="/mua-ve" class="btn-slide-quick">Mua Vé Nhanh</router-link>
          </div>
        </div>
      </div>

      <!-- Arrow Controls -->
      <button class="arrow-btn prev-arrow" @click="prevSlide">❮</button>
      <button class="arrow-btn next-arrow" @click="nextSlide">❯</button>

      <!-- Dot Controls -->
      <div class="slider-dots">
        <span 
          v-for="(banner, idx) in banners" 
          :key="'dot-' + banner.id"
          class="dot"
          :class="{ active: activeSlideIndex === idx }"
          @click="activeSlideIndex = idx"
        ></span>
      </div>
    </header>

    <!-- SECTION 1: PHIM ĐANG CHIẾU (Black background with Red seating styling) -->
    <section class="now-showing-section">
      <div class="now-showing-container">
        <div class="section-header-dark">
          <h2 class="section-title-dark">Phim đang chiếu</h2>
        </div>
        
        <div v-if="loading" class="loading-state-dark">
          <div class="spinner-light"></div>
          <p>Đang quét danh sách phim tại cụm rạp...</p>
        </div>

        <div v-else class="movies-carousel">
          <div 
            v-for="(movie, index) in activeMovies" 
            :key="movie.id" 
            class="movie-carousel-card"
            @click="bookMovie(movie)"
          >
            <div class="poster-container">
              <!-- Large Ranking Overlay (1, 2, 3...) -->
              <span class="rank-number">{{ index + 1 }}</span>
              <img :src="movie.poster_url" :alt="movie.title" class="carousel-poster" />
              
              <!-- Play Button overlay -->
              <div class="play-overlay">
                <div class="play-icon-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </div>
              </div>
              
              <span class="age-badge" :class="getRatingClass(movie.rating)">
                {{ movie.rating }}
              </span>
            </div>
            
            <div class="movie-meta-info">
              <h3 class="movie-carousel-title">{{ movie.title }}</h3>
              <p class="movie-carousel-genres">
                {{ movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Hành động, Viễn tưởng' }}
              </p>
              <div class="rating-row">
                <span class="star-rating">★ {{ getStarRating(movie.id) }}</span>
                <span class="duration">{{ movie.duration }} phút</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 2: PHIM SẮP CHIẾU (White Background) -->
    <section class="upcoming-section">
      <div class="upcoming-container">
        <div class="section-header-light">
          <h2 class="section-title-light">Phim sắp chiếu</h2>
        </div>

        <div class="movies-grid-light">
          <div 
            v-for="movie in upcomingMovies" 
            :key="movie.id" 
            class="movie-card-light"
          >
            <div class="poster-container-light">
              <img :src="movie.poster_url" :alt="movie.title" class="poster-light" />
              
              <!-- Play Button overlay -->
              <div class="play-overlay">
                <div class="play-icon-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </div>
              </div>
              
              <span class="age-badge-light">{{ movie.rating }}</span>
            </div>
            <div class="info-light">
              <h3 class="title-light">{{ movie.title }}</h3>
              <p class="genres-light">{{ movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Sắp chiếu' }}</p>
              <p class="release-date">Khởi chiếu: {{ formatDate(movie.release_date) }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useBookingStore } from '../../stores/booking';
import api from '../../api/axios';

const router = useRouter();
const bookingStore = useBookingStore();

const activeSlideIndex = ref(0);
let slideInterval = null;

// Premium high-res banners list matching real movie database entries & visuals
const banners = ref([
  {
    id: 4,
    title: 'Star Wars: Mandalorian',
    bg_url: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1600&q=80',
    rating: 'T13',
    duration: 145,
    genres: ['Khoa Học Viễn Tưởng', 'Phiêu Lưu'],
    description: 'Hành trình vượt qua các tinh vân xa xôi của thợ săn tiền thưởng Mandalorian và cậu bé Grogu đáng yêu trước các mối đe dọa mới từ tàn dư Đế Chế.'
  },
  {
    id: 1,
    title: 'Doraemon Movie 45: Nobita và Bản Giao Hưởng Địa Cầu',
    bg_url: 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=1600&q=80',
    rating: 'G',
    duration: 110,
    genres: ['Hoạt Hình', 'Giả Tưởng', 'Âm Nhạc'],
    description: 'Nobita và những người bạn quen biết một cô gái bí ẩn và cùng tham gia vào cuộc hành trình giải cứu thế giới bằng sức mạnh kỳ diệu của âm nhạc.'
  },
  {
    id: 2,
    title: 'Ma Xó: Bản Thuyết Minh Đặc Biệt',
    bg_url: 'https://images.unsplash.com/photo-1505635552518-3448ff116af3?auto=format&fit=crop&w=1600&q=80',
    rating: 'T18',
    duration: 98,
    genres: ['Kinh Dị', 'Tâm Linh', 'Kịch Tính'],
    description: 'Bí ẩn đằng sau hũ tro cốt nguyền rủa trong ngôi làng cổ hẻo lánh bắt đầu phát tác, đe dọa sinh mạng của bất kỳ ai tò mò tìm kiếm sự thật.'
  }
]);

const nextSlide = () => {
  activeSlideIndex.value = (activeSlideIndex.value + 1) % banners.value.length;
};

const prevSlide = () => {
  activeSlideIndex.value = (activeSlideIndex.value - 1 + banners.value.length) % banners.value.length;
};

const goToMovie = (id) => {
  const foundMovie = movies.value.find(m => m.id === id);
  if (foundMovie) {
    bookingStore.selectMovie(foundMovie);
    router.push(`/movie/${id}`);
  } else {
    // Fallback if movies list not yet fetched or matches local mock
    const fallbackMovie = {
      id: id,
      title: id === 4 ? 'Star Wars: Mandalorian' : (id === 1 ? 'Doraemon Movie 45: Nobita...' : 'Ma Xó'),
      rating: id === 4 ? 'T13' : (id === 1 ? 'G' : 'T18'),
      duration: id === 4 ? 145 : (id === 1 ? 110 : 98),
      poster_url: id === 4 ? 'https://images.unsplash.com/photo-1478720143022-10d0002856a3?auto=format&fit=crop&w=400&q=80' : (id === 1 ? 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=400&q=80' : 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80'),
      genres: id === 4 ? ['Khoa Học Viễn Tưởng', 'Phiêu Lưu'] : (id === 1 ? ['Hoạt Hình', 'Giả Tưởng'] : ['Kinh Dị', 'Tâm Linh'])
    };
    bookingStore.selectMovie(fallbackMovie);
    router.push(`/movie/${id}`);
  }
};

const startSlideTimer = () => {
  stopSlideTimer();
  slideInterval = setInterval(nextSlide, 5000);
};

const stopSlideTimer = () => {
  if (slideInterval) {
    clearInterval(slideInterval);
    slideInterval = null;
  }
};

const movies = ref([]);
const loading = ref(false);

// Separate displaying movies and upcoming movies
const activeMovies = computed(() => {
  return movies.value.filter(movie => movie.status === 'showing').slice(0, 5); // Display top 5
});

const upcomingMovies = computed(() => {
  return movies.value.filter(movie => movie.status === 'upcoming');
});

const getRatingClass = (rating) => {
  if (!rating) return 'rating-g';
  const r = rating.toUpperCase();
  if (r.includes('18') || r === 'R') return 'rating-t18';
  if (r.includes('16')) return 'rating-t16';
  if (r.includes('13') || r === 'PG-13') return 'rating-t13';
  return 'rating-g';
};

const getStarRating = (id) => {
  // Return realistic star ratings for movies
  const ratings = { 1: '9.0', 2: '9.0', 3: '7.5', 4: '9.0', 5: '7.5' };
  return ratings[id] || '8.0';
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const bookMovie = (movie) => {
  bookingStore.selectMovie(movie);
  router.push(`/movie/${movie.id}`);
};

const fetchMovies = async () => {
  loading.value = true;
  try {
    const response = await api.get('/movies');
    movies.value = response.data;
  } catch (err) {
    console.error('Fetch movies error, fallback to mock data:', err);
    // Mock standard list corresponding to screenshots
    movies.value = [
      {
        id: 1,
        title: 'Doraemon Movie 45: Nobita...',
        poster_url: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=400&q=80',
        rating: 'G',
        duration: 110,
        release_date: '2026-05-24',
        status: 'showing',
        genres: ['Hoạt Hình', 'Giả Tưởng']
      },
      {
        id: 2,
        title: 'Ma Xó',
        poster_url: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80',
        rating: 'T18',
        duration: 98,
        release_date: '2026-05-28',
        status: 'showing',
        genres: ['Kinh Dị', 'Tâm Linh']
      },
      {
        id: 3,
        title: 'Ngôi Đền Kỳ Quái 5',
        poster_url: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80',
        rating: 'T16',
        duration: 115,
        release_date: '2026-05-29',
        status: 'showing',
        genres: ['Hài', 'Kinh Dị']
      },
      {
        id: 4,
        title: 'Star Wars: Mandalorian',
        poster_url: 'https://images.unsplash.com/photo-1478720143022-10d0002856a3?auto=format&fit=crop&w=400&q=80',
        rating: 'T13',
        duration: 145,
        release_date: '2026-06-01',
        status: 'showing',
        genres: ['Khoa Học Viễn Tưởng', 'Phiêu Lưu']
      },
      {
        id: 5,
        title: 'Ốc Mượn Hồn',
        poster_url: 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?auto=format&fit=crop&w=400&q=80',
        rating: 'T16',
        duration: 105,
        release_date: '2026-05-30',
        status: 'showing',
        genres: ['Bí Ẩn', 'Tâm Lý']
      },
      {
        id: 6,
        title: 'Giải Cứu Binh Nhì',
        poster_url: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80',
        rating: 'T16',
        duration: 120,
        release_date: '2026-06-15',
        status: 'upcoming',
        genres: ['Hành Động', 'Chiến Tranh']
      },
      {
        id: 7,
        title: 'BTS: World Tour Arirang',
        poster_url: 'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?auto=format&fit=crop&w=400&q=80',
        rating: 'G',
        duration: 130,
        release_date: '2026-06-20',
        status: 'upcoming',
        genres: ['Âm Nhạc', 'Tài Liệu']
      }
    ];
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchMovies();
  startSlideTimer();
});

onUnmounted(() => {
  stopSlideTimer();
});
</script>

<style scoped>
.home-view {
  display: flex;
  flex-direction: column;
  background-color: #ffffff;
  color: var(--text-primary);
}

/* DYNAMIC HERO SLIDER */
.hero-slider {
  position: relative;
  width: 100%;
  height: 520px;
  overflow: hidden;
  background-color: #0b0507;
}

.slide {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-size: cover;
  background-position: center center;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
  display: flex;
  align-items: center;
  z-index: 1;
}

.slide.active {
  opacity: 1;
  visibility: visible;
  z-index: 2;
}

.slide-content {
  max-width: 650px;
  margin-left: 8%;
  padding: 30px;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  gap: 16px;
  text-align: left;
  z-index: 3;
}

@media (max-width: 768px) {
  .hero-slider {
    height: 480px;
  }
  .slide-content {
    margin-left: 4%;
    padding: 20px;
  }
  .slide-title {
    font-size: 32px !important;
  }
  .slide-desc {
    font-size: 14px !important;
  }
}

.slide-badge {
  color: var(--accent-pink);
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 2px;
  display: inline-block;
}

.slide-title {
  font-size: 42px;
  font-weight: 800;
  line-height: 1.25;
  color: #ffffff;
  text-shadow: 0 3px 15px rgba(0, 0, 0, 0.7);
  margin-bottom: 2px;
}

.slide-meta {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 13px;
}

.rating-badge-slide {
  background: var(--accent-pink);
  color: #ffffff;
  padding: 3px 8px;
  font-weight: 800;
  border-radius: 4px;
  font-size: 11px;
}

.meta-item-slide {
  color: #e2e8f0;
  display: flex;
  align-items: center;
  gap: 5px;
  text-shadow: 0 1px 5px rgba(0, 0, 0, 0.5);
}

.slide-desc {
  color: #e2e8f0;
  font-size: 15px;
  line-height: 1.6;
  max-width: 580px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
  margin-bottom: 8px;
}

.slide-actions {
  display: flex;
  gap: 15px;
}

.btn-slide-book {
  background: var(--accent-pink);
  color: #ffffff;
  border: none;
  padding: 12px 28px;
  font-weight: 700;
  font-size: 14px;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.4);
  transition: var(--transition-bounce);
}

.btn-slide-book:hover {
  transform: translateY(-2px);
  background: var(--accent-violet);
  box-shadow: 0 6px 20px rgba(155, 0, 14, 0.6);
}

.btn-slide-quick {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 12px 28px;
  font-weight: 700;
  font-size: 14px;
  border-radius: 8px;
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  transition: var(--transition-smooth);
}

.btn-slide-quick:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: #ffffff;
}

/* Arrow Navigation */
.arrow-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(18, 5, 8, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #ffffff;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  transition: var(--transition-smooth);
}

.arrow-btn:hover {
  background: var(--accent-pink);
  border-color: var(--accent-pink);
  transform: translateY(-50%) scale(1.1);
  box-shadow: 0 0 15px rgba(229, 9, 20, 0.4);
}

.prev-arrow {
  left: 24px;
}

.next-arrow {
  right: 24px;
}

/* Dots Indicator */
.slider-dots {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 10px;
  z-index: 10;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.35);
  cursor: pointer;
  transition: var(--transition-smooth);
}

.dot.active {
  width: 28px;
  border-radius: 5px;
  background: var(--accent-pink);
  box-shadow: 0 0 10px var(--accent-pink-glow);
}

/* NOW SHOWING SECTION (Dark Cinema Seating theme) */
.now-showing-section {
  background-color: #0d091a;
  background-image: radial-gradient(circle at bottom, #2b112d, #0d091a 70%);
  color: #ffffff;
  padding: 60px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.now-showing-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.section-header-dark {
  margin-bottom: 36px;
}

.section-title-dark {
  font-size: 32px;
  font-weight: 800;
  color: #ffffff;
  text-align: center;
  letter-spacing: -0.01em;
}

.movies-carousel {
  display: flex;
  gap: 24px;
  overflow-x: auto;
  padding: 20px 0;
}

.movie-carousel-card {
  width: 210px;
  flex-shrink: 0;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 12px;
  transition: var(--transition-smooth);
}

.movie-carousel-card:hover {
  transform: translateY(-5px);
}

.movie-carousel-card .poster-container {
  position: relative;
  width: 100%;
  padding-top: 145%;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.carousel-poster {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Overlay Ranking (1, 2, 3...) */
.rank-number {
  position: absolute;
  bottom: -15px;
  left: 5px;
  font-size: 90px;
  font-weight: 900;
  line-height: 1;
  color: #ffffff;
  font-family: var(--font-display);
  text-shadow: 4px 4px 0px rgba(0, 0, 0, 0.4), 0 0 10px rgba(255, 255, 255, 0.2);
  z-index: 2;
  user-select: none;
  opacity: 0.9;
}

/* Play Icon Overlay */
.play-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: var(--transition-smooth);
  z-index: 1;
}

.movie-carousel-card:hover .play-overlay, .movie-card-light:hover .play-overlay {
  opacity: 1;
}

.play-icon-btn {
  background: var(--accent-pink);
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-left: 3px;
  box-shadow: 0 4px 15px rgba(216, 45, 139, 0.6);
  transform: scale(0.9);
  transition: var(--transition-bounce);
}

.movie-carousel-card:hover .play-icon-btn, .movie-card-light:hover .play-icon-btn {
  transform: scale(1);
}

.age-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  font-size: 11px;
  font-weight: 800;
  color: white;
  padding: 3px 8px;
  border-radius: 4px;
  z-index: 2;
}

.movie-meta-info {
  padding-left: 5px;
}

.movie-carousel-title {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.movie-carousel-genres {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  margin-bottom: 6px;
}

.rating-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.star-rating {
  color: #ffb400;
  font-size: 13px;
  font-weight: 800;
}

.movie-meta-info .duration {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.4);
}

/* UPCOMING SECTION (White Background) */
.upcoming-section {
  background-color: #ffffff;
  padding: 60px 0;
}

.upcoming-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.section-header-light {
  margin-bottom: 36px;
  text-align: center;
}

.section-title-light {
  font-size: 32px;
  font-weight: 800;
  color: var(--accent-pink);
  position: relative;
  display: inline-block;
}

.movies-grid-light {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 30px;
}

.movie-card-light {
  background: #ffffff;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
  cursor: pointer;
  transition: var(--transition-smooth);
}

.movie-card-light:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
  border-color: rgba(216, 45, 139, 0.1);
}

.poster-container-light {
  position: relative;
  width: 100%;
  padding-top: 140%;
}

.poster-light {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.age-badge-light {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #00cd6c;
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 4px;
}

.info-light {
  padding: 16px;
}

.title-light {
  font-size: 16px;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.genres-light {
  font-size: 12px;
  color: #718096;
  margin-bottom: 10px;
}

.release-date {
  font-size: 11px;
  font-weight: 700;
  color: var(--accent-pink);
}



/* Loading animations */
.loading-state-dark {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px;
  color: rgba(255, 255, 255, 0.6);
}

.spinner-light {
  width: 36px;
  height: 36px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: var(--accent-pink);
  border-radius: 50%;
  animation: spin 1s infinite linear;
  margin-bottom: 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
