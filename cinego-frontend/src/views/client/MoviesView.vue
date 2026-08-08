<template>
  <div class="movies-view">
    <div class="cg-filter-container">
      <!-- HEADER & CHUYỂN TAB TRẠNG THÁI PHIM -->
      <div class="movies-header-block">
        <h1 class="movies-main-title">
          {{ currentTab === 'now_showing' ? ' Phim Đang Chiếu' : ' Phim Sắp Chiếu' }}
        </h1>
        
        <div class="status-tab-group">
          <button 
            class="tab-btn" 
            :class="{ active: currentTab === 'now_showing' }"
            @click="switchTab('now_showing')"
          >
            Phim đang chiếu
          </button>
          <button 
            class="tab-btn" 
            :class="{ active: currentTab === 'coming_soon' }"
            @click="switchTab('coming_soon')"
          >
            Phim sắp chiếu
          </button>
        </div>
      </div>

      <!-- THANH TÌM KIẾM VÀ BỘ LỌC -->
      <div class="cg-filter-bar-header">
        <div class="cg-filter-controls">
          <select v-model="filters.genre_id" @change="handleFilterChange" class="cg-filter-select">
            <option value="">Tất cả thể loại</option>
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
            <input 
              type="text" 
              v-model="filters.keyword" 
              @input="debounceSearch" 
              placeholder="Tìm tên phim..." 
              class="cg-filter-search-input" 
            />
            <span class="cg-search-icon">🔍</span>
          </div>
        </div>
      </div>

      <!-- TRẠNG THÁI LOADING -->
      <div v-if="loading" class="cg-filter-loading-state">
        <div class="cg-spinner-accent"></div>
        <p>Đang tải danh sách phim...</p>
      </div>

      <!-- KHÔNG CÓ DỮ LIỆU -->
      <div v-else-if="paginatedMovies.length === 0" class="empty-state">
        <p>Không tìm thấy phim phù hợp.</p>
      </div>

      <!-- DANH SÁCH PHIM LƯỚI GRID -->
      <div v-else class="cg-filter-movies-grid">
        <div v-for="movie in paginatedMovies" :key="movie.id" class="cg-filter-movie-card">
          <div class="cg-filter-poster-box">
            <img 
              :src="getPosterUrl(movie.poster_url)" 
              :alt="movie.title" 
              class="cg-filter-movie-poster" 
              @click="goToDetail(movie.id)" 
            />
            <div class="cg-filter-play-overlay" @click="goToDetail(movie.id)">
              <div class="cg-filter-play-icon-btn" @click.stop="openTrailer(movie.trailer_url)">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#ffffff">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
              </div>
            </div>
            <span class="cg-filter-age-badge" :class="getRatingClass(movie.rating)">
              {{ movie.rating || 'G' }}
            </span>
          </div>

          <div class="cg-filter-movie-info">
            <h3 class="cg-filter-movie-title" @click="goToDetail(movie.id)">{{ movie.title }}</h3>
            <p class="cg-filter-movie-genres">
              {{ movie.genres ? movie.genres.map(g => g.name || g).join(', ') : 'Chiếu rạp' }}
            </p>
            <p v-if="movie.duration" class="movie-duration-text">⏱️ {{ movie.duration }} phút</p>
          </div>
        </div>
      </div>
      
      <!-- PHÂN TRANG -->
      <div class="reviews-pager" v-if="totalPages > 1 && !loading">
        <button @click="prevPage" :disabled="currentPage === 1">Trang trước</button>
        <span>Trang {{ currentPage }} / {{ totalPages }}</span>
        <button @click="nextPage" :disabled="currentPage === totalPages">Trang sau</button>
      </div>
    </div>

    <!-- MODAL POPUP TRAILER -->
    <div v-if="isTrailerOpen" class="trailer-modal-backdrop" @click.self="closeTrailer">
      <div class="trailer-modal-content">
        <button class="trailer-close-btn" @click="closeTrailer">✕</button>
        <div class="video-responsive-container">
          <iframe 
            v-if="embedTrailerUrl" 
            :src="embedTrailerUrl" 
            frameborder="0" 
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
            allowfullscreen>
          </iframe>
          <div v-else class="no-trailer-msg">Không có trailer khả dụng cho phim này.</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../api/axios';
const route = useRoute();
const router = useRouter();

const movies = ref([]);
const filteredMovies = ref([]);
const loading = ref(false);
const currentTab = ref('now_showing');

const currentPage = ref(1);
const itemsPerPage = 8;

const isTrailerOpen = ref(false);
const currentTrailerUrl = ref('');

const filters = ref({
  genre_id: '',
  country: '',
  year: '',
  keyword: ''
});

const genreList = ref([
  { id: 1, name: 'Hành Động' },
  { id: 2, name: 'Hoạt Hình' },
  { id: 3, name: 'Khoa Học Viễn Tưởng' },
  { id: 4, name: 'Kinh Dị' },
  { id: 5, name: 'Phiêu Lưu' }
]);

const activeTabMovies = computed(() => {
  return movies.value.filter(movie => {
    if (!movie.status) return false;
    const s = movie.status.toLowerCase().replace(/[\s_]/g, '-');
    if (currentTab.value === 'now_showing') {
      return s === 'showing' || s === 'now-showing' || s === 'đang-chiếu' || s === 'now';
    } else {
      return s === 'upcoming' || s === 'coming-soon' || s === 'sắp-chiếu' || s === 'sap-chieu';
    }
  });
});

const applyLocalFilters = () => {
  let result = activeTabMovies.value;

  if (filters.value.genre_id) {
    result = result.filter(m => m.genres && m.genres.some(g => (g.id || g) == filters.value.genre_id));
  }
  if (filters.value.keyword) {
    const kw = filters.value.keyword.toLowerCase();
    result = result.filter(m => m.title && m.title.toLowerCase().includes(kw));
  }

  filteredMovies.value = result;
  currentPage.value = 1;
};

const paginatedMovies = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredMovies.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => {
  return Math.ceil(filteredMovies.value.length / itemsPerPage) || 1;
});

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };

const switchTab = (tabType) => {
  router.push({ path: '/phim', query: { type: tabType } });
};

const fetchMovies = async () => {
  loading.value = true;
  try {
    const response = await api.get('/movies');
    const apiData = response.data?.data || response.data;
    movies.value = apiData || [];
    applyLocalFilters();
  } catch (error) {
    console.error('Lỗi khi tải danh sách phim:', error);
    movies.value = [];
    filteredMovies.value = [];
  } finally {
    loading.value = false;
  }
};

watch(
  () => route.query.type,
  (newType) => {
    currentTab.value = newType === 'coming_soon' ? 'coming_soon' : 'now_showing';
    applyLocalFilters();
  },
  { immediate: true }
);

const handleFilterChange = () => {
  applyLocalFilters();
};

let searchTimeout = null;
const debounceSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyLocalFilters();
  }, 350);
};

const getPosterUrl = (url) => {
  if (!url) return 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80';
  if (url.startsWith('http') || url.startsWith('blob:')) return url;
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

const goToDetail = (id) => {
  router.push(`/movie/${id}`);
};

const embedTrailerUrl = computed(() => {
  if (!currentTrailerUrl.value) return '';
  const match = currentTrailerUrl.value.match(/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/);
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

onMounted(() => {
  fetchMovies();
});
</script>

<!-- Nhập CSS chung ở style không scoped -->
<style scoped >
@import "../../assets/css/pages/home-view.css";
</style>

<!-- CSS riêng biệt của component -->
<style scoped>
.movies-view {
  padding: 40px 0;
  min-height: 80vh;
}

.movies-header-block {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  flex-wrap: wrap;
  gap: 16px;
}

.movies-main-title {
  font-size: 28px;
  font-weight: 800;
  color: #1e293b;
}

.status-tab-group {
  display: flex;
  background-color: #f1f5f9;
  padding: 4px;
  border-radius: 12px;
}

.tab-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  background: transparent;
  color: #64748b;
  transition: all 0.2s ease;
}

.tab-btn.active {
  background-color: #ffffff;
  color: #dc2626;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.movie-duration-text {
  font-size: 12px;
  color: #64748b;
  margin-top: 6px;
}

.empty-state {
  text-align: center;
  padding: 60px 0;
  font-weight: 600;
  color: #94a3b8;
}
</style>