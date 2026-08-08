<template>
  <div class="home-view">
    <!-- HERO SLIDER BANNER -->
    <header class="hero-slider">
      <div v-for="(banner, idx) in banners" :key="banner.id" class="slide"
        :class="{ active: activeSlideIndex === idx }">

        <!-- Nền mờ từ poster phim -->
        <div class="slide-bg" :style="{ backgroundImage: `url(${banner.poster_url})` }"></div>
        <!-- Lớp phủ gradient từ trái sang phải -->
        <div class="slide-overlay"></div>

        <!-- Nội dung bên trong -->
        <div class="slide-inner">
          <!-- Bên trái: Thông tin phim -->
          <div class="slide-content">
            <span class="slide-badge glow-text-pink">SIÊU PHẨM HOT TUẦN NÀY</span>
            <h1 class="slide-title">{{ banner.title }}</h1>

            <div class="slide-meta">
              <span class="rating-badge-slide" :class="getRatingClass(banner.rating)">{{ banner.rating }}</span>
              <span class="meta-item-slide">⏱️ {{ banner.duration }} phút</span>
              <span class="meta-item-slide">📁 {{ banner.genres.join(', ') }}</span>
            </div>

            <p class="banner-description">
              {{ banner.description }}
            </p>
            <router-link :to="`/movie/${banner.id}`" class="read-more-btn">
              ... xem thêm
            </router-link>

            <div class="slide-actions">
              <button @click="goToDetail(banner.id)" class="btn-slide-book">ĐẶT VÉ NGAY</button>
              <router-link to="/mua-ve" class="btn-slide-quick">Mua Vé Nhanh</router-link>
            </div>
          </div>

          <!-- Bên phải: Poster phim sắc nét -->
          <div class="slide-poster-side">
            <img :src="banner.poster_url" :alt="banner.title" class="slide-poster-img" />
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
              <img :src="getPosterUrl(movie.poster_url)" :alt="movie.title" class="carousel-poster"
                @click="goToDetail(movie.id)" />

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
                {{movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Hành động, Viễn tưởng'}}
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
                <img :src="getPosterUrl(movie.poster_url)" :alt="movie.title" class="poster-light"
                  @click="goToDetail(movie.id)" />

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
                  {{movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Sắp chiếu'}}
                </p>

                <p v-if="movie.duration" class="duration-light">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" width="13" height="13"
                    style="display: inline-block; vertical-align: middle; margin-right: 3px;">
                    <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
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
          <div v-for="movie in paginatedMovies" :key="'filter-' + movie.id" class="cg-filter-movie-card">
            <div class="cg-filter-poster-box">
              <img :src="getPosterUrl(movie.poster_url)" :alt="movie.title" class="cg-filter-movie-poster"
                @click="goToDetail(movie.id)" />
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
                {{movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Hành động'}}
              </p>
            </div>
          </div>
        </div>

        <!-- Pagination Controls -->
        <div class="reviews-pager" v-if="totalPages > 1 && !filterLoading">
          <button @click="prevPage" :disabled="currentPage === 1">Trang trước</button>
          <span>Trang {{ currentPage }} / {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages">Trang sau</button>
        </div>
      </div>
    </section>

    <section class="home-featured-comments-section">
      <div class="home-featured-comments-container">
        <h2 class="home-featured-comments-title">Bình luận tiêu biểu</h2>
        <div class="home-featured-comments-grid">
          <div v-for="review in featuredComments" :key="review.id" class="review-card glass-panel">
            <!-- Movie Trailer Preview Area -->
            <div class="movie-preview-box" @click="goToDetailWithReview(review.movieId, review.id)"
              style="cursor: pointer;">
              <img :src="review.moviePoster" :alt="review.movieTitle" class="movie-backdrop-img" />
              <div class="overlay-gradient"></div>

              <!-- Play Button -->
              <button class="btn-play-preview" title="Xem trailer review" @click.stop="openTrailer(review.trailerUrl)">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3" />
                </svg>
              </button>

              <span class="movie-rating-pill">⭐ {{ review.rating }}/5</span>
              <span v-if="review.duration" class="movie-duration-pill">{{ review.duration }} phút</span>
            </div>

            <!-- Card Info -->
            <div class="review-card-body" @click="goToDetailWithReview(review.movieId, review.id)"
              style="cursor: pointer;">
              <h3 class="movie-title">{{ review.movieTitle }}</h3>

              <div class="user-comment-wrap">
                <div class="user-meta">
                  <div class="user-meta-header" style="display: flex; align-items: center; gap: 12px; width: 100%;">
                    <span class="user-avatar" :style="{ background: review.avatarColor }">{{ review.userInitials
                      }}</span>
                    <div class="user-info-text">
                      <h4 class="user-name">{{ review.userName }}</h4>
                      <small class="comment-time">{{ review.timeAgo }}</small>
                    </div>
                  </div>
                  <!-- Verification Badge -->
                  <span class="verified-buyer-badge"
                    style="width: fit-content; margin-top: 8px; align-self: flex-start;">✓ Đã mua vé qua CineGo</span>
                </div>
                <p class="user-comment-text">"{{ review.comment }}"</p>
              </div>
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

const currentPage = ref(1);
const itemsPerPage = 8; // Hoặc 10, 12 tùy thiết kế

const paginatedMovies = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredMovies.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => {
  return Math.ceil(filteredMovies.value.length / itemsPerPage);
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

const loading = ref(false);
const filterLoading = ref(false);
const genreList = ref([
  { id: 1, name: 'Hành Động' },
  { id: 2, name: 'Hoạt Hình' },
  { id: 3, name: 'Khoa Học Viễn Tưởng' },
  { id: 4, name: 'Kinh Dị' },
  { id: 5, name: 'Phiêu Lưu' }
]);

const featuredComments = ref([]);

const fetchFeaturedComments = async () => {
  try {
    const res = await api.get('/reviews/featured');
    const list = res.data?.data || [];
    // filter 5 stars
    const fiveStar = list.filter(r => Number(r.rating) === 5);
    featuredComments.value = fiveStar.slice(0, 6).map((r, i) => ({
      id: r.id,
      movieId: r.movie?.id,
      movieTitle: r.movie?.title || 'Phim CineGo',
      moviePoster: getPosterUrl(r.movie?.poster_url),
      duration: r.movie?.duration || '',
      genres: r.movie?.genres?.map(g => g.name) || [],
      rating: Number(r.rating || 0).toFixed(1),
      userName: r.user?.name || 'Khách CineGo',
      userInitials: getInitials(r.user?.name),
      avatarColor: ['linear-gradient(135deg, #e50914, #9b000e)', 'linear-gradient(135deg, #7c4dff, #512da8)', 'linear-gradient(135deg, #00bcd4, #00838f)', 'linear-gradient(135deg, #ff9800, #e65100)'][i % 4],
      timeAgo: timeAgo(r.created_at),
      comment: r.comment || '',
      trailerUrl: r.movie?.trailer_url,
    }));
  } catch (e) {
    console.error('Lỗi khi lấy bình luận tiêu biểu:', e);
  }
};


const filters = ref({
  genre_id: '',
  country: '',
  year: '',
  keyword: ''
});

const banners = ref([]);

const fetchBanners = async () => {
  try {
    const res = await api.get('/banners/public');
    const data = res.data.data;
    if (data && data.length > 0) {
      banners.value = data.map(b => ({
        id: b.movie?.id || b.id,
        title: b.movie?.title || 'CineGo',
        poster_url: getPosterUrl(b.movie?.poster_url),
        rating: b.movie?.rating || 'G',
        duration: b.movie?.duration || 120,
        genres: b.movie?.genres?.map(g => g.name) || [],
        description: b.movie?.description || ''
      }));
    } else {
      banners.value = [
        {
          id: 4,
          title: 'Star Wars: Mandalorian',
          poster_url: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80',
          rating: 'T13',
          duration: 145,
          genres: ['Khoa Học Viễn Tưởng', 'Phiêu Lưu'],
          description: 'Hành trình vượt qua các tinh vân xa xôi của thợ săn tiền thưởng Mandalorian.'
        }
      ];
    }
  } catch (e) {
    console.error('Lỗi lấy banners:', e);
  }
};

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
    // Chuyển về chữ thường để so sánh
    const s = movie.status.toLowerCase();
    return s === 'showing' ||
      s === 'now-showing' ||
      s === 'now_showing' ||
      s === 'đang-chiếu' || // Thêm trường hợp này
      s === 'đang chiếu';   // Và trường hợp này
  });
});

// 🔥 BỘ LỌC PHIM SẮP CHIẾU - Lấy từ dữ liệu gốc của DB lên
const upcomingMovies = computed(() => {
  return movies.value.filter(movie => {
    if (!movie.status) return false;

    // Chuẩn hóa trạng thái về dạng chữ thường và thay thế khoảng trắng/gạch dưới bằng gạch ngang
    const s = movie.status.toLowerCase().replace(/[\s_]/g, '-');

    // Thêm các từ khóa tiếng Việt vào điều kiện lọc
    return s === 'upcoming' ||
      s === 'coming-soon' ||
      s === 'coming-soon' ||
      s === 'sắp-chiếu' ||
      s === 'sap-chieu';
  });
});

const getPosterUrl = (url) => {
  if (!url) return 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=100&q=80';
  if (url.startsWith('http')) return url;
  if (url.startsWith('blob:')) return url;

  // Trỏ về port 8000 của Laravel
  const cleanPath = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${cleanPath}`;
};

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

const goToDetailWithReview = (movieId, reviewId) => {
  if (movieId) {
    router.push({ path: `/movie/${movieId}`, query: { reviewId: reviewId } });
  }
};

const fetchFilteredMovies = async () => {
  filterLoading.value = true;
  try {
    const response = await api.get('/movies/search', { params: filters.value });
    const resData = response.data?.data || response.data;
    filteredMovies.value = resData || [];
    currentPage.value = 1; // Reset to page 1 on new search
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

const getInitials = (name) => {
  if (!name) return 'U';
  const parts = name.trim().split(' ').filter(Boolean);
  if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
  return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
};

onMounted(async () => {
  await fetchBanners();
  fetchFeaturedComments();
  fetchMovies();
  slideInterval = setInterval(nextSlide, 5000);
});

const timeAgo = (dt) => {
  if (!dt) return '';
  const diff = (Date.now() - new Date(dt).getTime()) / 1000;
  if (diff < 3600) return Math.max(1, Math.floor(diff / 60)) + ' phút trước';
  if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
  return Math.floor(diff / 86400) + ' ngày trước';
};

onUnmounted(() => {
  if (slideInterval) clearInterval(slideInterval);
});

</script>


<style scoped>
@import '../../assets/css/pages/home-view.css';

.home-featured-comments-section {
  padding: 48px 0 28px;
  background: radial-gradient(circle at top left, rgba(96, 165, 250, 0.16), transparent 34%),
    radial-gradient(circle at top right, rgba(236, 72, 153, 0.14), transparent 28%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 1) 100%);
}

.home-featured-comments-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.home-featured-comments-title {
  font-size: 30px;
  font-weight: 900;
  color: #dc2626;
  margin-bottom: 22px;
  letter-spacing: -0.04em;
  text-shadow: 0 4px 18px rgba(220, 38, 38, 0.12);
}

.home-featured-comments-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.home-featured-comment-card {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(252, 232, 255, 0.92));
  border: 1px solid rgba(168, 85, 247, 0.24);
  border-radius: 32px;
  box-shadow: 0 20px 55px rgba(59, 130, 246, 0.14);
  padding: 28px;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.home-featured-comment-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 28px 78px rgba(59, 130, 246, 0.22);
}

.comment-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 16px;
}

.comment-user-block {
  display: flex;
  align-items: center;
  gap: 14px;
}

.comment-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #ec4899 0%, #f97316 100%);
  color: #ffffff;
  font-weight: 800;
  font-size: 14px;
  box-shadow: 0 12px 28px rgba(236, 72, 153, 0.18);
}

.comment-movie {
  font-size: 15px;
  font-weight: 800;
  color: #111827;
  margin: 0 0 6px;
}

.comment-user {
  font-size: 13px;
  color: #7c3aed;
  margin: 0;
}

.comment-rating {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 56px;
  height: 36px;
  border-radius: 999px;
  background: linear-gradient(135deg, #ec4899 0%, #f59e0b 100%);
  color: #fff;
  font-weight: 800;
  font-size: 13px;
}

.comment-text {
  color: #334155;
  font-size: 15px;
  line-height: 1.9;
  margin: 0;
}

@media (max-width: 1024px) {
  .home-featured-comments-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .home-featured-comments-grid {
    grid-template-columns: 1fr;
  }
}
.banner-description {
  display: -webkit-box;
  -webkit-line-clamp: 3; /* Giới hạn tối đa 3 dòng */
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 6px;
  line-height: 1.5;
  color: #cbd5e1;
}

.read-more-btn {
  display: inline-block;
  color: #f43f5e; /* Màu hồng/đỏ nổi bật */
  font-weight: 600;
  font-size: 14px;
  text-decoration: none;
  margin-bottom: 16px;
  transition: color 0.2s ease;
}

.read-more-btn:hover {
  color: #e11d48;
  text-decoration: underline;
}
</style>