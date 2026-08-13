<template>
  <div class="top-movies-container">
    <!-- PAGE HEADER -->
    <div class="page-header" v-if="article">
      <div class="header-content">
        <span class="hero-tag">TRENDING & TOP RATED</span>
        <h1 class="page-title gradient-text-accent">{{ article.title }}</h1>
        <p class="page-desc">
          {{ article.excerpt }}
        </p>
      </div>
    </div>

    <!-- MAIN LISTING -->
    <div class="top-movies-content" v-if="filteredMovies.length > 0">
      <div class="momo-movie-list">
        <div v-for="(movie, index) in filteredMovies" :key="movie.id" class="momo-movie-item">
          
          <div class="momo-poster-col" @click="openTrailer(movie)">
            <img :src="movie.poster" :alt="movie.title" class="momo-poster" />
            <div class="play-btn-overlay" v-if="movie.trailer_url">
              <svg viewBox="0 0 24 24" fill="white" width="40" height="40"><path d="M8 5v14l11-7z"/></svg>
            </div>
          </div>

          <div class="momo-info-col">
            <h2 class="momo-title">{{ movie.title }}</h2>
            
            <div class="momo-meta-row">
              <span class="momo-label">Thể loại :</span>
              <span class="momo-value">{{ movie.genres.join(', ') }}</span>
            </div>
            <div class="momo-meta-row">
              <span class="momo-label">Năm :</span>
              <span class="momo-value">{{ getYear(movie.release_date) }}</span>
            </div>
            <div class="momo-meta-row">
              <span class="momo-label">Thời gian :</span>
              <span class="momo-value">{{ movie.duration }} phút</span>
            </div>
            <div class="momo-meta-row momo-desc-row">
              <span class="momo-label">Nội dung phim :</span>
              <span class="momo-value">
                {{ movie.isExpanded ? movie.description : truncateText(movie.description, 150) }}
                <a href="#" class="momo-readmore" @click.prevent="movie.isExpanded = !movie.isExpanded" v-if="movie.description && movie.description.length > 150">
                  {{ movie.isExpanded ? ' Ẩn bớt' : '...Xem thêm' }}
                </a>
              </span>
            </div>
            <div class="momo-meta-row">
              <span class="momo-label">Quốc Gia :</span>
              <span class="momo-value">Đang cập nhật</span>
            </div>
          </div>

          <div class="momo-rank-col">
            <div class="momo-rank-square">{{ index + 1 }}</div>
          </div>

        </div>
      </div>
    </div>

    <!-- TRAILER MODAL -->
    <Teleport to="body">
      <div v-if="trailerModal.show" class="trailer-overlay" @click.self="closeTrailer">
        <div class="trailer-modal">
          <button class="trailer-close" @click="closeTrailer">×</button>
          
          <!-- YouTube embed -->
          <div class="trailer-video-wrap">
            <iframe
              v-if="trailerModal.embedUrl"
              :src="trailerModal.embedUrl"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              class="trailer-iframe"
            ></iframe>
            <div v-else class="no-trailer">
              <p>🎥 Phim này chưa có trailer.</p>
            </div>
          </div>

          <!-- Movie info below -->
          <div class="trailer-info">
            <img :src="trailerModal.movie?.poster" class="trailer-thumb" />
            <div class="trailer-meta">
              <div class="trailer-movie-title">
                {{ trailerModal.movie?.title }}
                <span class="trailer-genres" v-if="trailerModal.movie?.genres"> - {{ trailerModal.movie.genres.join(', ') }}</span>
              </div>
              <p class="trailer-desc">{{ truncateText(trailerModal.movie?.description, 200) }}</p>
              <button class="trailer-close-btn" @click="closeTrailer">Đóng</button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '../../api/axios';

const route = useRoute();
const router = useRouter();

const article = ref(null);
const filteredMovies = ref([]);
const loading = ref(true);

const fetchArticle = async () => {
  loading.value = true;
  try {
    const slug = route.params.slug;
    const res = await axios.get(`/articles/${slug}`);
    article.value = res.data;
    
    // Sort movies by rank and map them
    const movies = res.data.movies.sort((a, b) => a.pivot.rank - b.pivot.rank);
    filteredMovies.value = movies.map(m => {
      // map backend model to frontend structure
      return {
        id: m.id,
        title: m.title,
        genres: m.genres ? m.genres.split(',').map(g => g.trim()) : ['Đang cập nhật'],
        rating: m.age_rating,
        ratingClass: getRatingClass(m.age_rating),
        description: m.pivot.review_text || m.description || 'Chưa có mô tả',
        imdb: '8.0',
        duration: m.duration,
        release_date: m.release_date,
        trailer_url: m.trailer_url || null,
        poster: getPosterUrl(m.poster_url),
        backdrop: getPosterUrl(m.poster_url),
        isExpanded: false
      };
    });
  } catch (error) {
    console.error('Lỗi khi tải bài viết:', error);
    // Redirect back to listing if article not found
    router.push('/top-phim');
  } finally {
    loading.value = false;
  }
};

const getPosterUrl = (url) => {
  if (!url) return 'https://via.placeholder.com/400x600?text=No+Image';
  if (url.startsWith('http')) return url;
  if (url.startsWith('blob:')) return url;
  const cleanPath = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${cleanPath}`;
};

const getRatingClass = (rating) => {
  switch (rating) {
    case 'T13': return 'age-t13';
    case 'T16': return 'age-t16';
    case 'T18': return 'age-t18';
    case 'PG-13': return 'age-pg13';
    default: return 'age-t13';
  }
};

onMounted(() => {
  fetchArticle();
});

const top1Movie = computed(() => filteredMovies.value[0]);
const top23Movies = computed(() => filteredMovies.value.slice(1, 3));
const top4PlusMovies = computed(() => filteredMovies.value.slice(3));

const getYear = (dateString) => {
  if (!dateString) return 'Đang cập nhật';
  const d = new Date(dateString);
  return isNaN(d) ? 'Đang cập nhật' : d.getFullYear();
};

const formatDate = (val) => {
  if (!val) return '—';
  const d = new Date(val);
  return isNaN(d) ? '—' : d.toLocaleDateString('vi-VN');
};

const truncateText = (text, length) => {
  if (!text) return '';
  if (text.length <= length) return text;
  return text.substring(0, length);
};

// ===== TRAILER MODAL =====
const trailerModal = ref({ show: false, embedUrl: null, movie: null });

const getYouTubeEmbedUrl = (url) => {
  if (!url) return null;
  // Support: youtube.com/watch?v=ID or youtu.be/ID
  const match = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([\w-]+)/);
  if (!match) return null;
  return `https://www.youtube.com/embed/${match[1]}?autoplay=1&rel=0`;
};

const openTrailer = (movie) => {
  trailerModal.value = {
    show: true,
    embedUrl: getYouTubeEmbedUrl(movie.trailer_url),
    movie
  };
  document.body.style.overflow = 'hidden';
};

const closeTrailer = () => {
  trailerModal.value = { show: false, embedUrl: null, movie: null };
  document.body.style.overflow = '';
};
</script>

<style scoped>
/* ========== PAGE HEADER ========== */
.page-header {
  background: linear-gradient(135deg, #f8f0f0 0%, #fff5f5 100%);
  padding: 50px 24px;
  text-align: center;
  border-bottom: 2px solid rgba(229, 9, 20, 0.15);
  margin-bottom: 40px;
}
.header-content { max-width: 800px; margin: 0 auto; }
.hero-tag { font-size: 11px; font-weight: 800; color: #e50914; letter-spacing: 2px; display: block; margin-bottom: 12px; }
.page-title { font-size: 36px; font-weight: 800; color: #1a1a1a; margin-bottom: 16px; letter-spacing: -1px; }
.page-desc { font-size: 16px; color: #444; line-height: 1.6; margin-bottom: 20px; font-weight: 500; }

/* TABS */
.category-tabs { display: inline-flex; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.08); padding: 6px; border-radius: 14px; }
.tab-btn { background: transparent; border: none; color: #94a3b8; font-weight: 700; font-size: 13.5px; padding: 12px 24px; border-radius: 10px; cursor: pointer; transition: all 0.25s ease; }
.tab-btn:hover { color: #ffffff; }
.tab-btn.active { background: #e50914; color: #ffffff; box-shadow: 0 4px 12px rgba(229, 9, 20, 0.3); }

/* MAIN CONTENT CONTAINER */
.top-movies-content { max-width: 1200px; margin: 0 auto; padding: 0 20px 80px; }

/* MOMO MOVIE LIST */
.momo-movie-list {
  display: flex;
  flex-direction: column;
  gap: 30px;
  background-color: #ffffff;
  padding: 30px;
  border-radius: 12px;
}

.momo-movie-item {
  display: flex;
  gap: 20px;
  padding-bottom: 30px;
  border-bottom: 1px solid #eaeaea;
  position: relative;
}

.momo-movie-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.momo-poster-col {
  position: relative;
  width: 140px;
  height: 200px;
  flex-shrink: 0;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  cursor: pointer;
}

.momo-poster {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.momo-poster-col:hover .momo-poster {
  transform: scale(1.05);
}

.play-btn-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50px;
  height: 50px;
  background: rgba(0,0,0,0.5);
  border: 2px solid white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.momo-info-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  color: #333;
}

.momo-title {
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 10px 0;
  color: #000;
}

.momo-ratings {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.imdb-badge {
  background: #f5c518;
  color: black;
  font-weight: 900;
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
}

.imdb-score {
  font-weight: 700;
  font-size: 14px;
}

.momo-meta-row {
  display: flex;
  margin-bottom: 6px;
  font-size: 13px;
  line-height: 1.5;
}

.momo-label {
  font-weight: 700;
  color: #555;
  width: 110px;
  flex-shrink: 0;
}

.momo-value {
  color: #666;
}

.momo-desc-row {
  /* Inherits display: flex from momo-meta-row */
}

.momo-readmore {
  color: #0066cc;
  text-decoration: none;
}

.momo-rank-col {
  width: 60px;
  display: flex;
  justify-content: flex-end;
}

.momo-rank-square {
  width: 36px;
  height: 36px;
  background: #e61972;
  color: white;
  font-weight: 700;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

@media (max-width: 768px) {
  .momo-movie-item {
    flex-direction: column;
  }
  .momo-poster-col {
    width: 100%;
    height: 300px;
  }
  .momo-rank-col {
    position: absolute;
    top: 10px;
    right: 10px;
  }
}

/* ===== TRAILER MODAL ===== */
.trailer-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.80);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.trailer-modal {
  background: #1a1a1a;
  border-radius: 12px;
  width: 100%;
  max-width: 820px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 25px 60px rgba(0,0,0,0.8);
  animation: fadeInScale 0.25s ease;
}

@keyframes fadeInScale {
  from { opacity: 0; transform: scale(0.93); }
  to   { opacity: 1; transform: scale(1); }
}

.trailer-close {
  position: absolute;
  top: 12px;
  right: 14px;
  background: rgba(255,255,255,0.15);
  border: none;
  color: white;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}
.trailer-close:hover { background: rgba(255,255,255,0.3); }

.trailer-video-wrap {
  width: 100%;
  aspect-ratio: 16/9;
  background: #000;
}

.trailer-iframe {
  width: 100%;
  height: 100%;
  display: block;
  border: none;
}

.no-trailer {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #aaa;
  font-size: 16px;
  height: 100%;
}

.trailer-info {
  display: flex;
  gap: 16px;
  padding: 18px 20px;
  background: #ffffff;
  align-items: flex-start;
}

.trailer-thumb {
  width: 80px;
  height: 110px;
  object-fit: cover;
  border-radius: 6px;
  flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.trailer-meta {
  flex: 1;
}

.trailer-movie-title {
  font-size: 15px;
  font-weight: 700;
  color: #111;
  margin-bottom: 8px;
  line-height: 1.4;
}

.trailer-genres {
  font-weight: 400;
  color: #555;
  font-size: 13px;
}

.trailer-desc {
  font-size: 13px;
  color: #555;
  line-height: 1.6;
  margin-bottom: 12px;
}

.trailer-close-btn {
  background: #333;
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.trailer-close-btn:hover { background: #555; }
</style>
