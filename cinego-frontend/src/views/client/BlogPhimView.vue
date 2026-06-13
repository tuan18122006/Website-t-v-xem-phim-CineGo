<template>
  <div class="blog-page-container">
    <!-- HERO HEADER -->
    <div class="blog-hero">
      <div class="hero-glow"></div>
      <div class="hero-content">
        <span class="hero-tag">CINEGO MOVIES BLOG</span>
        <h1 class="hero-title">Blog Điện Ảnh & Tin Tức Phim Hot</h1>
        <p class="hero-desc">
          Cập nhật các bài phân tích, tin tức điện ảnh nóng hổi, hậu trường làm phim và xu hướng điện ảnh thế giới mới nhất.
        </p>

        <!-- Category selectors -->
        <div class="blog-categories">
          <button 
            v-for="cat in categories" 
            :key="cat.key" 
            class="cat-pill" 
            :class="{ active: activeCategory === cat.key }"
            @click="activeCategory = cat.key"
          >
            {{ cat.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- FEATURED ARTICLE (HERO POST) -->
    <div v-if="featuredArticle" class="featured-post-wrap">
      <div class="featured-post-card glass-panel">
        <div class="post-image-box">
          <img :src="featuredArticle.image" :alt="featuredArticle.title" class="post-img" />
          <span class="post-category-tag">{{ featuredArticle.categoryName }}</span>
        </div>
        <div class="post-info-box">
          <small class="post-meta-top">🕒 {{ featuredArticle.date }} • ✍️ {{ featuredArticle.author }}</small>
          <h2 class="post-title">{{ featuredArticle.title }}</h2>
          <p class="post-excerpt">{{ featuredArticle.excerpt }}</p>
          <div class="post-footer">
            <span class="reading-time">📖 {{ featuredArticle.readingTime }} phút đọc</span>
            <a href="#" class="btn-read-more" @click.prevent="alertReadMore">Đọc tiếp →</a>
          </div>
        </div>
      </div>
    </div>

    <!-- SECONDARY ARTICLES GRID -->
    <div class="blog-grid-section">
      <h2 class="grid-section-title">Bài viết mới cập nhật</h2>

      <div class="blog-grid">
        <div v-for="post in filteredArticles" :key="post.id" class="grid-post-card glass-panel">
          <div class="grid-post-image">
            <img :src="post.image" :alt="post.title" class="grid-img" />
            <span class="grid-category-tag">{{ post.categoryName }}</span>
          </div>
          <div class="grid-post-body">
            <small class="grid-meta">🕒 {{ post.date }} • {{ post.readingTime }} phút đọc</small>
            <h3 class="grid-title">{{ post.title }}</h3>
            <p class="grid-excerpt">{{ post.excerpt }}</p>
            <div class="grid-footer">
              <a href="#" class="btn-grid-read" @click.prevent="alertReadMore">Đọc chi tiết</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const activeCategory = ref('all');

const categories = [
  { key: 'all', label: '🔥 Mới nhất' },
  { key: 'behind', label: '🎬 Hậu trường phim' },
  { key: 'review', label: '✍️ Phân tích & Review' },
  { key: 'roundup', label: '📰 Tổng hợp điện ảnh' }
];

const featuredArticle = ref({
  id: 1,
  title: 'Doraemon: Từ "Ký Ức Tuổi Thơ" Đến "Cỗ Máy Phòng Vé" Đa Thế Hệ',
  category: 'behind',
  categoryName: 'Hậu trường phim',
  author: 'Đức Huy',
  date: '13/06/2026',
  excerpt: 'Cùng khám phá hành trình dài hơn nửa thế kỷ của chú mèo máy thông minh Doraemon và người bạn Nobita, tìm hiểu lý do tại sao loạt phim điện ảnh thường niên này luôn chiếm lĩnh doanh số phòng vé mỗi dịp hè về.',
  image: 'https://images.unsplash.com/photo-1578849278619-e73505e9610f?auto=format&fit=crop&w=800&q=80',
  readingTime: '5'
});

const articles = ref([
  {
    id: 2,
    title: 'Xu Hướng Đặt Vé Sớm & Sự Trỗi Dậy Của Suất Chiếu Đặc Biệt',
    category: 'roundup',
    categoryName: 'Tổng hợp điện ảnh',
    date: '12/06/2026',
    excerpt: 'Lý do tại sao các bom tấn Hollywood đang tăng cường suất chiếu sớm (Sneak Show) trước ngày khởi chiếu chính thức và phản ứng nồng nhiệt từ người hâm mộ.',
    image: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=400&q=80',
    readingTime: '3'
  },
  {
    id: 3,
    title: 'Khi Sức Mua Bùng Nổ Nhưng Trải Nghiệm Bị Lệch Pha',
    category: 'review',
    categoryName: 'Phân tích & Review',
    date: '10/06/2026',
    excerpt: 'Bài phân tích sâu sắc về sự phát triển của hệ thống phòng chiếu hiện đại tại Việt Nam và cách CineGo nâng cao chuẩn mực dịch vụ phục vụ khách hàng.',
    image: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=400&q=80',
    readingTime: '4'
  },
  {
    id: 4,
    title: 'Nghệ Thuật Của Điện Ảnh: Review & Phân Tích Toàn Diện',
    category: 'review',
    categoryName: 'Phân tích & Review',
    date: '08/06/2026',
    excerpt: 'Hướng dẫn cách người xem cảm nhận nghệ thuật góc quay, màu sắc và thông điệp ẩn dụ sâu bên trong các bộ phim kinh điển được chiếu rạp.',
    image: 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=400&q=80',
    readingTime: '4'
  },
  {
    id: 5,
    title: 'Top 5 Bộ Phim Hậu Trường Đáng Xem Nhất Mọi Thời Đại',
    category: 'behind',
    categoryName: 'Hậu trường phim',
    date: '05/06/2026',
    excerpt: 'Hành trình vượt khó khăn của các đoàn làm phim khi thực hiện các dự án bom tấn lớn trong lịch sử điện ảnh nhân loại.',
    image: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=400&q=80',
    readingTime: '3'
  }
]);

const filteredArticles = computed(() => {
  if (activeCategory.value === 'all') return articles.value;
  return articles.value.filter(a => a.category === activeCategory.value);
});

const alertReadMore = () => {
  alert('Tính năng đang được phát triển. Chi tiết bài viết đầy đủ sẽ sớm ra mắt!');
};
</script>

<style scoped>
.blog-page-container {
  width: 100%;
  min-height: 100vh;
  background-color: #ffffff;
  padding-bottom: 60px;
}

/* HERO HEADER */
.blog-hero {
  position: relative;
  background: linear-gradient(135deg, #1b0004 0%, #000000 100%);
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
  right: -20%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(229, 9, 20, 0.2) 0%, transparent 70%);
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

/* CATEGORIES */
.blog-categories {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.cat-pill {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  font-weight: 700;
  font-size: 13px;
  padding: 10px 20px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s ease;
  backdrop-filter: blur(4px);
}

.cat-pill:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.cat-pill.active {
  background: #e50914;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.25);
  border-color: #e50914;
}

/* FEATURED ARTICLE */
.featured-post-wrap {
  max-width: 1100px;
  margin: 0 auto 50px auto;
  padding: 0 16px;
}

.featured-post-card {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}

@media (max-width: 768px) {
  .featured-post-card {
    grid-template-columns: 1fr;
  }
}

.post-image-box {
  position: relative;
  height: 340px;
  overflow: hidden;
}

.post-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.featured-post-card:hover .post-img {
  transform: scale(1.03);
}

.post-category-tag {
  position: absolute;
  top: 20px;
  left: 20px;
  background: #e50914;
  color: #ffffff;
  font-weight: 800;
  font-size: 11px;
  padding: 6px 14px;
  border-radius: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.post-info-box {
  padding: 32px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.post-meta-top {
  font-size: 12px;
  color: #94a3b8;
  font-weight: 600;
  margin-bottom: 12px;
  display: block;
}

.post-title {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.3;
  margin: 0 0 14px 0;
}

.post-excerpt {
  font-size: 14.5px;
  color: #64748b;
  line-height: 1.6;
  margin: 0 0 24px 0;
}

.post-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid #f1f5f9;
  padding-top: 20px;
  margin-top: auto;
}

.reading-time {
  font-size: 12.5px;
  color: #94a3b8;
  font-weight: 600;
}

.btn-read-more {
  color: #e50914;
  font-weight: 700;
  font-size: 13.5px;
  transition: transform 0.2s;
}

.btn-read-more:hover {
  transform: translateX(3px);
}

/* GRID SECTION */
.blog-grid-section {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 16px;
}

.grid-section-title {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 24px;
  border-left: 5px solid #e50914;
  padding-left: 12px;
}

.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 30px;
}

.grid-post-card {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.02);
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

.grid-post-card:hover {
  transform: translateY(-4px);
  border-color: rgba(229, 9, 20, 0.15);
  box-shadow: 0 10px 24px rgba(229, 9, 20, 0.08);
}

.grid-post-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.grid-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.grid-post-card:hover .grid-img {
  transform: scale(1.04);
}

.grid-category-tag {
  position: absolute;
  top: 14px;
  left: 14px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(4px);
  color: #ffffff;
  font-weight: 700;
  font-size: 10px;
  padding: 4px 10px;
  border-radius: 6px;
  text-transform: uppercase;
}

.grid-post-body {
  padding: 20px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.grid-meta {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
  margin-bottom: 8px;
}

.grid-title {
  font-size: 16px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.4;
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.grid-excerpt {
  font-size: 13px;
  color: #64748b;
  line-height: 1.5;
  margin: 0 0 16px 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}

.grid-footer {
  margin-top: auto;
  border-top: 1px solid #f1f5f9;
  padding-top: 12px;
  display: flex;
  justify-content: flex-end;
}

.btn-grid-read {
  color: #e50914;
  font-weight: 700;
  font-size: 12.5px;
}

.btn-grid-read:hover {
  text-decoration: underline;
}
</style>
