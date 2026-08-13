<template>
  <div class="top-movies-listing">
    <div class="hero-section">
      <div class="hero-content">
        <h1>Bảng Xếp Hạng Top Phim</h1>
        <p>Khám phá các bộ phim hay nhất được đánh giá cao trên CineGo</p>
      </div>
    </div>
    
    <div class="listing-container" v-if="!loading">
      <div v-if="articles.length === 0" class="no-data">
        <p>Hiện chưa có danh sách phim nào.</p>
      </div>
      
      <div class="articles-grid">
        <router-link 
          v-for="article in articles" 
          :key="article.id" 
          :to="`/top-phim/${article.slug}`" 
          class="article-card glass-panel"
        >
          <div class="article-thumbnail">
            <template v-if="article.movies && article.movies.length > 0">
              <div class="poster-stack">
                <div v-for="(movie, index) in article.movies.slice(0, 4)" 
                     :key="movie.id" 
                     class="stack-img-container"
                     :class="'stack-' + index">
                  <img :src="getPosterUrl(movie.poster_url)" :alt="movie.title" />
                </div>
              </div>
            </template>
            <template v-else>
              <img :src="getPosterUrl(article.thumbnail_url)" :alt="article.title" class="single-thumb" />
            </template>
            <div class="article-badge">Top {{ article.movies_count || (article.movies ? article.movies.length : 0) }} phim</div>
          </div>
          <div class="article-info">
            <h2 class="article-title">{{ article.title }}</h2>
            <p class="article-excerpt">{{ article.excerpt }}</p>
            <div class="article-meta">
              <span><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> {{ article.views }} lượt xem</span>
              <span><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> {{ formatDate(article.created_at) }}</span>
            </div>
          </div>
        </router-link>
      </div>
    </div>
    
    <div v-else class="loading">Đang tải dữ liệu...</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../api/axios';
import { toast } from '../../utils/alert';

const articles = ref([]);
const loading = ref(true);

const fetchArticles = async () => {
  try {
    const res = await axios.get('/articles');
    articles.value = res.data.data;
  } catch (error) {
    toast('Lỗi khi tải danh sách bài viết', 'error');
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

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('vi-VN').format(date);
};

onMounted(() => {
  fetchArticles();
});
</script>

<style scoped>
.top-movies-listing {
  background-color: var(--bg-dark, #f8f9fa);
  min-height: 100vh;
  padding-bottom: 60px;
}

.hero-section {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  padding: 60px 20px;
  text-align: center;
  color: white;
  margin-bottom: 40px;
}

.hero-content h1 {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 15px;
}

.hero-content p {
  font-size: 1.1rem;
  opacity: 0.9;
}

.listing-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.articles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 30px;
}

.article-card {
  display: flex;
  flex-direction: column;
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  background: white;
}

.glass-panel {
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.article-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(216, 45, 139, 0.15);
}

.article-thumbnail {
  position: relative;
  height: 250px;
  width: 100%;
  background-color: #1a1a1a;
  overflow: hidden;
}

.single-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.poster-stack {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
}

.stack-img-container {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: -10px 0 20px rgba(0,0,0,0.8);
  transition: transform 0.3s ease;
}

.stack-img-container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.stack-0 {
  left: 5%;
  height: 90%;
  width: 35%;
  z-index: 4;
}

.stack-1 {
  left: 20%;
  height: 85%;
  width: 33%;
  z-index: 3;
}

.stack-2 {
  left: 35%;
  height: 80%;
  width: 31%;
  z-index: 2;
}

.stack-3 {
  left: 50%;
  height: 75%;
  width: 29%;
  z-index: 1;
}

.article-card:hover .stack-0 { transform: translateY(-50%) scale(1.02); }
.article-card:hover .stack-1 { transform: translateY(-50%) translateX(15px); }
.article-card:hover .stack-2 { transform: translateY(-50%) translateX(30px); }
.article-card:hover .stack-3 { transform: translateY(-50%) translateX(45px); }

.article-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background: var(--cinema-red, #dc2626);
  color: white;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: bold;
}

.article-info {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.article-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 10px;
  line-height: 1.4;
  color: #1e293b;
}

.article-excerpt {
  color: #64748b;
  font-size: 0.95rem;
  line-height: 1.5;
  margin-bottom: 20px;
  flex-grow: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.article-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #94a3b8;
  font-size: 0.85rem;
  border-top: 1px solid #f1f5f9;
  padding-top: 15px;
}

.article-meta span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.no-data, .loading {
  text-align: center;
  padding: 60px;
  font-size: 1.2rem;
  color: #64748b;
}
</style>
