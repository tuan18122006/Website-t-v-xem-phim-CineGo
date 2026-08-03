<template>
  <div class="pos-container">
    <div class="pos-header">
      <h1>POS Đặt Vé Tại Quầy</h1>
      <div class="search-bar">
        <input v-model="searchQuery" type="text" placeholder="Tìm kiếm phim..." />
      </div>
    </div>
    
    <div v-if="loading" class="loading">Đang tải danh sách phim...</div>
    <div v-else-if="filteredMovies.length === 0" class="no-results">Không tìm thấy phim phù hợp.</div>
    
    <div v-else class="movies-grid">
      <div v-for="movie in filteredMovies" :key="movie.id" class="movie-card" @click="selectMovie(movie.id)">
        <div class="poster-wrapper">
          <img :src="movie.poster_url" :alt="movie.title" />
          <div class="status-badge" :class="movie.status">{{ movie.status === 'now_showing' ? 'Đang chiếu' : 'Sắp chiếu' }}</div>
        </div>
        <div class="movie-info">
          <h3>{{ movie.title }}</h3>
          <p>{{ movie.duration }} phút | {{ movie.age_rating }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';

const router = useRouter();
const movies = ref([]);
const loading = ref(true);
const searchQuery = ref('');

const fetchMovies = async () => {
  try {
    const res = await api.get('/movies');
    movies.value = res.data.data.filter(m => m.status === 'now_showing' || m.status === 'coming_soon');
  } catch (error) {
    console.error('Error fetching movies:', error);
  } finally {
    loading.value = false;
  }
};

const filteredMovies = computed(() => {
  if (!searchQuery.value) return movies.value;
  const q = searchQuery.value.toLowerCase();
  return movies.value.filter(m => m.title.toLowerCase().includes(q));
});

const selectMovie = (id) => {
  router.push({ path: `/movie/${id}`, query: { mode: 'pos' } });
};

onMounted(() => {
  fetchMovies();
});
</script>

<style scoped>
.pos-container {
  padding: 30px;
  max-width: 1200px;
  margin: 0 auto;
  background: #f8fafc;
  min-height: calc(100vh - 80px);
}
.pos-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
}
.pos-header h1 {
  font-size: 24px;
  color: #1e293b;
  font-weight: 700;
  border-left: 4px solid #10b981;
  padding-left: 12px;
}
.search-bar input {
  padding: 10px 16px;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  width: 300px;
  font-size: 14px;
}
.movies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}
.movie-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  cursor: pointer;
  transition: transform 0.2s;
}
.movie-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  border: 1px solid #10b981;
}
.poster-wrapper {
  position: relative;
  height: 300px;
}
.poster-wrapper img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.status-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  color: white;
}
.status-badge.now_showing { background: #10b981; }
.status-badge.coming_soon { background: #f59e0b; }
.movie-info {
  padding: 12px;
}
.movie-info h3 {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #0f172a;
}
.movie-info p {
  font-size: 12px;
  color: #64748b;
}
</style>
