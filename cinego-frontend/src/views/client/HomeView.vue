<template>
  <div class="home-view">
    <!-- HERO SLIDER BANNER -->
    <header class="hero-slider">
      <div v-for="(banner, idx) in banners" :key="banner.id" class="slide" :class="{ active: activeSlideIndex === idx }"
        :style="{ backgroundImage: `linear-gradient(to right, rgba(15, 23, 42, 0.95) 35%, rgba(15, 23, 42, 0.6) 65%, rgba(15, 23, 42, 0.2) 100%), url(${banner.bg_url})` }">
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
            <button @click="goToDetail(banner.id)" class="btn-slide-book">ĐẶT VÉ NGAY</button>
            <router-link to="/mua-ve" class="btn-slide-quick">Mua Vé Nhanh</router-link>
          </div>
        </div>
      </div>

      <button class="arrow-btn prev-arrow" @click="prevSlide">❮</button>
      <button class="arrow-btn next-arrow" @click="nextSlide">❯</button>

      <div class="slider-dots">
        <span v-for="(banner, idx) in banners" :key="'dot-' + banner.id" class="dot"
          :class="{ active: activeSlideIndex === idx }" @click="activeSlideIndex = idx"></span>
      </div>
    </header>

    <!-- SECTION: PHIM ĐANG CHIẾU -->
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
          <div v-for="(movie, index) in activeMovies" :key="movie.id" class="movie-carousel-card">
            <div class="poster-container">
              <img :src="movie.poster_url" :alt="movie.title" class="carousel-poster" @click="goToDetail(movie.id)" />

              <div class="play-overlay" @click="goToDetail(movie.id)">
                <div class="play-icon-btn" @click.stop="openTrailer(movie.trailer_url)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#ffffff">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                  </svg>
                </div>
              </div>

              <span class="age-badge" :class="getRatingClass(movie.rating)">
                {{ movie.rating || 'G' }}
              </span>
            </div>

            <div class="movie-meta-info" @click="bookMovie(movie)">
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

    <!-- SECTION: PHIM SẮP CHIẾU -->
    <section class="upcoming-section">
      <div class="upcoming-container">
        <div class="section-header-light">
          <h2 class="section-title-light">Phim sắp chiếu</h2>
        </div>

        <div class="carousel-wrapper-light">
          <button class="arrow-btn-light prev-light" @click="scrollUpcoming('left')">❮</button>

          <div class="movies-carousel-light" ref="upcomingCarouselRef">
            <div v-for="movie in upcomingMovies" :key="'upcoming-' + movie.id" class="movie-card-light">
              <div class="poster-container-light">
                <img :src="movie.poster_url" :alt="movie.title" class="poster-light" @click="goToDetail(movie.id)" />

                <div class="play-overlay" @click="goToDetail(movie.id)">
                  <div class="play-icon-btn" @click.stop="openTrailer(movie.trailer_url)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#ffffff">
                      <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                  </div>
                </div>

                <span class="age-badge-light" :class="getRatingClass(movie.rating)">{{ movie.rating || 'G' }}</span>
              </div>

              <div class="info-light" @click="goToDetail(movie.id)">
                <h3 class="title-light">{{ movie.title }}</h3>
                <p class="genres-light">
                  {{ movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Sắp chiếu' }}
                </p>

                <p v-if="movie.duration" class="duration-light">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" width="13" height="13"
                    style="display: inline-block; vertical-align: middle; margin-right: 3px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                  </svg>
                  {{ movie.duration }} phút
                </p>

                <p class="release-date">Khởi chiếu: {{ formatDate(movie.release_date) }}</p>
              </div>
            </div>
          </div>

          <button class="arrow-btn-light next-light" @click="scrollUpcoming('right')">❯</button>
        </div>
      </div>
    </section>

    <!-- SECTION: TÌM KIẾM & BỘ LỌC BỘ TRUYỆN -->
    <section class="cg-search-filter-section">
      <div class="cg-filter-container">
        <div class="cg-filter-bar-header">
          <h2 class="cg-filter-main-title">Tìm phim chiếu rạp trên CineGo</h2>

          <div class="cg-filter-controls">
            <select v-model="filters.genre_id" @change="handleFilterChange" class="cg-filter-select">
              <option value="">Thể loại</option>
              <option v-for="genre in genreList" :key="genre.id" :value="genre.id">{{ genre.name }}</option>
            </select>

            <select v-model="filters.country" @change="handleFilterChange" class="cg-filter-select">
              <option value="">Quốc gia</option>
              <option value="Vietnam">Việt Nam</option>
              <option value="USA">Âu Mỹ</option>
              <option value="Korea">Hàn Quốc</option>
            </select>

            <select v-model="filters.year" @change="handleFilterChange" class="cg-filter-select">
              <option value="">Năm</option>
              <option value="2026">2026</option>
              <option value="2025">2025</option>
            </select>

            <div class="cg-search-input-wrapper">
              <input type="text" v-model="filters.keyword" @input="debounceSearch" placeholder="Tìm theo tên phim..."
                class="cg-filter-search-input" />
              <span class="cg-search-icon">🔍</span>
            </div>
          </div>
        </div>

        <div v-if="filterLoading" class="cg-filter-loading-state">
          <div class="cg-spinner-accent"></div>
          <p>Đang tìm phim...</p>
        </div>

        <div v-else class="cg-filter-movies-grid">
          <div v-for="movie in filteredMovies" :key="'filter-' + movie.id" class="cg-filter-movie-card">
            <div class="cg-filter-poster-box">
              <img :src="movie.poster_url" :alt="movie.title" class="cg-filter-movie-poster" @click="goToDetail(movie.id)" />
              <div class="cg-filter-play-overlay" @click="goToDetail(movie.id)">
                <div class="cg-filter-play-icon-btn" @click.stop="openTrailer(movie.trailer_url)">
                  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#ffffff">
                    <polygon points="5 3 19 12 5 21 5 3"></polygon>
                  </svg>
                </div>
              </div>
              <span class="cg-filter-age-badge" :class="getRatingClass(movie.rating)">{{ movie.rating || 'G' }}</span>
            </div>
            <div class="cg-filter-movie-info">
              <h3 class="cg-filter-movie-title" @click="goToDetail(movie.id)">{{ movie.title }}</h3>
              <p class="cg-filter-movie-genres">
                {{ movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Hành động' }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- TRAILER MODAL -->
    <div v-if="isTrailerOpen" class="trailer-modal-backdrop" @click.self="closeTrailer">
      <div class="trailer-modal-content">
        <button class="trailer-close-btn" @click="closeTrailer">✕</button>
        <div class="video-responsive-container">
          <iframe v-if="embedTrailerUrl" :src="embedTrailerUrl" frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen></iframe>
          <div class="no-trailer-msg">Không có dữ liệu Trailer cho phim này.</div>
        </div>
      </div>
    </div>
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
const isTrailerOpen = ref(false);
const currentTrailerUrl = ref('');

const movies = ref([]); 
const filteredMovies = ref([]);

const loading = ref(false);
const filterLoading = ref(false);
const genreList = ref([
  { id: 1, name: 'Hành Động' },
  { id: 2, name: 'Hoạt Hình' },
  { id: 3, name: 'Khoa Học Viễn Tưởng' },
  { id: 4, name: 'Kinh Dị' },
  { id: 5, name: 'Phiêu Lưu' }
]);

const filters = ref({
  genre_id: '',
  country: '',
  year: '',
  keyword: ''
});

// Cấu hình Banners hiển thị trên cùng (Bạn có thể sửa text/ảnh tùy ý)
const banners = ref([
  {
    id: 4,
    title: 'Star Wars: Mandalorian',
    bg_url: 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1600&q=80',
    rating: 'T13',
    duration: 145,
    genres: ['Khoa Học Viễn Tưởng', 'Phiêu Lưu'],
    description: 'Hành trình vượt qua các tinh vân xa xôi của thợ săn tiền thưởng Mandalorian.'
  }
]);

const nextSlide = () => {
  if (banners.value.length > 0) {
    activeSlideIndex.value = (activeSlideIndex.value + 1) % banners.value.length;
  }
};
const prevSlide = () => {
  if (banners.value.length > 0) {
    activeSlideIndex.value = (activeSlideIndex.value - 1 + banners.value.length) % banners.value.length;
  }
};

const embedTrailerUrl = computed(() => {
  if (!currentTrailerUrl.value) return '';
  const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
  const match = currentTrailerUrl.value.match(regExp);
  return (match && match[2].length === 11)
    ? `https://www.youtube.com/embed/${match[2]}?autoplay=1`
    : currentTrailerUrl.value;
});

const openTrailer = (url) => {
  if (url && url !== 'null' && url !== 'undefined') {
    currentTrailerUrl.value = url;
    isTrailerOpen.value = true;
  } else {
    alert('Phim hiện chưa có trailer chính thức!');
  }
};

const closeTrailer = () => {
  isTrailerOpen.value = false;
  currentTrailerUrl.value = '';
};

// 🔥 BỘ LỌC PHIM ĐANG CHIẾU - Lấy từ dữ liệu gốc của DB lên
const activeMovies = computed(() => {
  return movies.value.filter(movie => {
    if (!movie.status) return false;
    // Chuẩn hóa chữ thường, đổi gạch dưới thành gạch ngang để khớp chính xác cấu trúc filter
    const s = movie.status.toLowerCase().replace(/[\s_]/g, '-');
    return s === 'showing' || s === 'now-showing' || s === 'now_showing';
  });
});

// 🔥 BỘ LỌC PHIM SẮP CHIẾU - Lấy từ dữ liệu gốc của DB lên
const upcomingMovies = computed(() => {
  return movies.value.filter(movie => {
    if (!movie.status) return false;
    const s = movie.status.toLowerCase().replace(/[\s_]/g, '-');
    return s === 'upcoming' || s === 'coming-soon' || s === 'coming_soon';
  });
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
  const ratings = { 1: '9.0', 2: '8.8', 3: '7.5', 4: '9.2' };
  return ratings[id] || '8.5';
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

const goToDetail = (id) => {
  router.push(`/movie/${id}`);
};

const fetchFilteredMovies = async () => {
  filterLoading.value = true;
  try {
    const response = await api.get('/movies/search', { params: filters.value });
    const resData = response.data?.data || response.data;
    filteredMovies.value = resData || [];
  } catch (error) {
    console.error('Lỗi tìm kiếm từ DB:', error);
    filteredMovies.value = [];
  } finally {
    filterLoading.value = false;
  }
};

const handleFilterChange = () => {
  fetchFilteredMovies();
};

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchFilteredMovies();
  }, 400);
};

const fetchMovies = async () => {
  loading.value = true;
  try {
    const response = await api.get('/movies');
    const apiData = response.data?.data || response.data;
    
    if (apiData && apiData.length > 0) {
      movies.value = apiData.map(movie => ({
        ...movie,
        status: movie.status ? movie.status.toLowerCase().replace(/[\s_]/g, '-') : 'now-showing'
      }));
      console.log('=== KẾT NỐI DB THÀNH CÔNG ===', movies.value);
    } else {
      movies.value = [];
      console.warn('Kết nối DB thành công nhưng bảng movies hiện tại đang trống rỗng!');
    }
  } catch (err) {
    console.error('Lỗi nghiêm trọng: Không thể kéo dữ liệu từ Laragon Database!', err);
    movies.value = [];
  } finally {
    loading.value = false;
    fetchFilteredMovies();
  }
};

const upcomingCarouselRef = ref(null);
const scrollUpcoming = (direction) => {
  if (upcomingCarouselRef.value) {
    const scrollAmount = 500;
    if (direction === 'left') {
      upcomingCarouselRef.value.scrollLeft -= scrollAmount;
    } else {
      upcomingCarouselRef.value.scrollLeft += scrollAmount;
    }
  }
};

onMounted(() => {
  fetchMovies();
  slideInterval = setInterval(nextSlide, 5000);
});

onUnmounted(() => {
  if (slideInterval) clearInterval(slideInterval);
});

</script>


<style scoped>
@import '../../assets/css/pages/home-view.css';
</style>