<template>
  <div class="blog-page-container" :class="{ 'modal-open': isModalOpen }">
    <!-- HERO HEADER -->
    <div class="blog-hero">
      <div class="hero-bg-overlay"></div>
      <div class="hero-content">
        <span class="hero-tag">CINEGO MOVIES BLOG</span>
        <h1 class="hero-title">Trải Nghiệm Điện Ảnh & Tin Tức Phim</h1>
        <p class="hero-desc">
          Cập nhật nhanh nhất tin tức điện ảnh nóng hổi, bài phân tích chuyên sâu và góc nhìn đa chiều về các bộ phim bom tấn.
        </p>
      </div>
    </div>

    <div class="blog-main-content">
      <!-- CATEGORY NAV -->
      <div class="blog-nav-sticky">
        <div class="blog-categories">
          <button 
            class="cat-pill" 
            :class="{ active: activeCategory === null }"
            @click="activeCategory = null"
          >
            🔥 Mới cập nhật
          </button>
          <button 
            v-for="cat in categories" 
            :key="cat.id" 
            class="cat-pill" 
            :class="{ active: activeCategory === cat.id }"
            @click="activeCategory = cat.id"
          >
            {{ cat.name }}
          </button>
        </div>
      </div>

      <!-- FEATURED ARTICLE -->
      <div v-if="featuredArticle" class="featured-post-wrap">
        <div class="featured-post-card" @click="openBlogModal(featuredArticle.id)">
          <div class="post-image-box">
            <img :src="featuredArticle.thumbnail_url || 'https://via.placeholder.com/800x400/1a1a1a/e50914?text=CineGo+Blog'" :alt="featuredArticle.title" class="post-img" />
            <div class="post-gradient-overlay"></div>
            <span class="post-category-tag" v-if="featuredArticle.category">{{ featuredArticle.category.name }}</span>
          </div>
          <div class="post-info-box">
            <small class="post-meta-top"><i class="fa fa-calendar"></i> {{ formatDate(featuredArticle.created_at) }}</small>
            <h2 class="post-title">{{ featuredArticle.title }}</h2>
            <p class="post-excerpt">{{ featuredArticle.excerpt || "Đang cập nhật tóm tắt..." }}</p>
            <div class="post-footer">
              <span class="btn-read-more">Đọc tiếp <i class="fa fa-arrow-right"></i></span>
            </div>
          </div>
        </div>
      </div>

      <!-- ARTICLES GRID -->
      <div class="blog-grid-section">
        <div v-if="loading" class="loading-state">
          Đang tải bài viết...
        </div>
        <div v-else-if="filteredArticles.length === 0" class="empty-state">
          Chưa có bài viết nào trong chuyên mục này.
        </div>
        <div v-else class="blog-grid">
          <div v-for="post in secondaryArticles" :key="post.id" class="grid-post-card" @click="openBlogModal(post.id)">
            <div class="grid-post-image">
              <img :src="post.thumbnail_url || 'https://via.placeholder.com/400x250/1a1a1a/e50914?text=CineGo+Blog'" :alt="post.title" class="grid-img" />
              <div class="grid-gradient"></div>
              <span class="grid-category-tag" v-if="post.category">{{ post.category.name }}</span>
            </div>
            <div class="grid-post-body">
              <small class="grid-meta">{{ formatDate(post.created_at) }}</small>
              <h3 class="grid-title">{{ post.title }}</h3>
              <p class="grid-excerpt">{{ post.excerpt }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- BLOG DETAILS MODAL -->
    <transition name="modal-fade">
      <div v-if="isModalOpen" class="blog-modal-overlay" @click.self="closeModal">
        <div class="blog-modal-content">
          <!-- Header -->
          <div class="modal-header">
            <h3 class="modal-brand">Blog CineGo</h3>
            <button class="btn-close-modal" @click="closeModal">✕</button>
          </div>

          <!-- Body -->
          <div class="modal-body" v-if="modalLoading">
            <div class="loading-spinner">Đang tải nội dung...</div>
          </div>
          <div class="modal-body" v-else-if="selectedBlog">
            <div class="modal-cover">
              <img :src="selectedBlog.thumbnail_url || 'https://via.placeholder.com/800x400/1a1a1a/e50914?text=CineGo+Blog'" alt="Cover" />
            </div>
            
            <div class="modal-article-info">
              <span class="modal-cat-tag" v-if="selectedBlog.category">{{ selectedBlog.category.name }}</span>
              <small class="modal-date">{{ formatDate(selectedBlog.created_at) }}</small>
            </div>

            <h1 class="modal-title">{{ selectedBlog.title }}</h1>
            
            <div class="modal-excerpt" v-if="selectedBlog.excerpt">
              {{ selectedBlog.excerpt }}
            </div>

            <!-- HTML Content -->
            <div class="modal-html-content" v-html="selectedBlog.content"></div>
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <button class="btn-close-bottom" @click="closeModal">ĐÓNG</button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../api/axios";
import { toast } from '../../utils/alert';

const loading = ref(true);
const articles = ref([]);
const categories = ref([]);
const activeCategory = ref(null);

// Modal state
const isModalOpen = ref(false);
const modalLoading = ref(false);
const selectedBlog = ref(null);

onMounted(async () => {
  await fetchCategories();
  await fetchBlogs();
});

const fetchCategories = async () => {
  try {
    const res = await api.get("/blog-categories");
    if (res.data?.success) {
      categories.value = res.data.data;
    }
  } catch (error) {
    console.error("Lỗi tải chuyên mục", error);
  }
};

const fetchBlogs = async () => {
  loading.value = true;
  try {
    const res = await api.get("/blogs");
    if (res.data?.success) {
      articles.value = res.data.data.filter(post => post.status === "published");
    }
  } catch (error) {
    console.error("Lỗi tải bài viết", error);
  } finally {
    loading.value = false;
  }
};

const openBlogModal = async (id) => {
  isModalOpen.value = true;
  modalLoading.value = true;
  selectedBlog.value = null;
  // Block body scroll
  document.body.style.overflow = "hidden";

  try {
    const res = await api.get(`/blogs/${id}`);
    if (res.data?.success) {
      selectedBlog.value = res.data.data;
    } else {
      selectedBlog.value = res.data;
    }
  } catch (error) {
    console.error("Lỗi tải chi tiết bài viết", error);
    toast("Không thể tải bài viết này!", 'error');
    closeModal();
  } finally {
    modalLoading.value = false;
  }
};

const closeModal = () => {
  isModalOpen.value = false;
  document.body.style.overflow = "auto";
  setTimeout(() => {
    selectedBlog.value = null;
  }, 300); // clear after animation
};

const filteredArticles = computed(() => {
  if (activeCategory.value === null) return articles.value;
  return articles.value.filter(a => a.category_id === activeCategory.value);
});

const featuredArticle = computed(() => {
  if (filteredArticles.value.length > 0) {
    return filteredArticles.value[0];
  }
  return null;
});

const secondaryArticles = computed(() => {
  if (filteredArticles.value.length > 1) {
    return filteredArticles.value.slice(1);
  }
  return [];
});

const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  return `${d.getDate().toString().padStart(2, "0")}/${(d.getMonth()+1).toString().padStart(2, "0")}/${d.getFullYear()}`;
};
</script>

<style scoped>
.blog-page-container {
  width: 100%;
  min-height: 100vh;
  background-color: #f8f9fa;
  padding-bottom: 80px;
}

/* HERO HEADER */
.blog-hero {
  position: relative;
  background-image: url("https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1600&q=80");
  background-size: cover;
  background-position: center;
  padding: 100px 24px;
  text-align: center;
  margin-bottom: 0px;
}

.hero-bg-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, rgba(229,9,20,0.3) 100%);
}

.hero-content {
  position: relative;
  max-width: 800px;
  margin: 0 auto;
  z-index: 2;
}

.hero-tag {
  font-size: 13px;
  font-weight: 800;
  color: #e50914;
  letter-spacing: 3px;
  display: block;
  margin-bottom: 16px;
  text-transform: uppercase;
}

.hero-title {
  font-size: 42px;
  font-weight: 900;
  color: #ffffff;
  margin-bottom: 20px;
  letter-spacing: -1px;
  text-shadow: 0 2px 10px rgba(0,0,0,0.5);
}

.hero-desc {
  font-size: 17px;
  color: #e2e8f0;
  line-height: 1.7;
  max-width: 600px;
  margin: 0 auto;
}

.blog-main-content {
  max-width: 1200px;
  margin: -40px auto 0;
  position: relative;
  z-index: 3;
  padding: 0 20px;
}

/* CATEGORIES */
.blog-nav-sticky {
  background: #ffffff;
  padding: 20px;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.08);
  margin-bottom: 40px;
  position: sticky;
  top: 80px;
  z-index: 10;
}

.blog-categories {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

.cat-pill {
  background: #f1f5f9;
  border: none;
  color: #64748b;
  font-weight: 700;
  font-size: 14px;
  padding: 12px 24px;
  border-radius: 30px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.cat-pill:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.cat-pill.active {
  background: #e50914;
  color: #ffffff;
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
}

/* FEATURED ARTICLE */
.featured-post-wrap {
  margin-bottom: 50px;
}

.featured-post-card {
  display: grid;
  grid-template-columns: 1.5fr 1fr;
  background: #ffffff;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 15px 35px rgba(0,0,0,0.06);
  cursor: pointer;
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}

.featured-post-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 45px rgba(0,0,0,0.12);
}

@media (max-width: 900px) {
  .featured-post-card {
    grid-template-columns: 1fr;
  }
}

.post-image-box {
  position: relative;
  height: 100%;
  min-height: 380px;
  overflow: hidden;
}

.post-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.7s ease;
}

.featured-post-card:hover .post-img {
  transform: scale(1.05);
}

.post-gradient-overlay {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(to right, rgba(0,0,0,0) 50%, rgba(255,255,255,1) 100%);
}
@media (max-width: 900px) {
  .post-gradient-overlay {
    background: linear-gradient(to bottom, rgba(0,0,0,0) 50%, rgba(255,255,255,1) 100%);
  }
}

.post-category-tag {
  position: absolute;
  top: 24px;
  left: 24px;
  background: #e50914;
  color: #ffffff;
  font-weight: 800;
  font-size: 12px;
  padding: 8px 16px;
  border-radius: 6px;
  text-transform: uppercase;
  z-index: 2;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.post-info-box {
  padding: 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  z-index: 3;
}

.post-meta-top {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
  margin-bottom: 16px;
  display: block;
}

.post-title {
  font-size: 28px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.3;
  margin: 0 0 16px 0;
  transition: color 0.3s ease;
}

.featured-post-card:hover .post-title {
  color: #e50914;
}

.post-excerpt {
  font-size: 16px;
  color: #64748b;
  line-height: 1.7;
  margin: 0 0 32px 0;
}

.post-footer {
  margin-top: auto;
}

.btn-read-more {
  color: #e50914;
  font-weight: 800;
  font-size: 15px;
  text-transform: uppercase;
  letter-spacing: 1px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

/* GRID SECTION */
.blog-grid-section {
  padding-bottom: 50px;
}

.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 30px;
}

.grid-post-card {
  background: #ffffff;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 25px rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
  cursor: pointer;
  transition: all 0.4s ease;
  border: 1px solid transparent;
}

.grid-post-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 15px 35px rgba(229,9,20,0.08);
  border-color: rgba(229,9,20,0.2);
}

.grid-post-image {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.grid-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.6s ease;
}

.grid-post-card:hover .grid-img {
  transform: scale(1.08);
}

.grid-gradient {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,0.6) 100%);
  pointer-events: none;
}

.grid-category-tag {
  position: absolute;
  top: 16px;
  left: 16px;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  color: #ffffff;
  font-weight: 700;
  font-size: 11px;
  padding: 6px 12px;
  border-radius: 4px;
  text-transform: uppercase;
  border-left: 3px solid #e50914;
}

.grid-post-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.grid-meta {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
  margin-bottom: 12px;
}

.grid-title {
  font-size: 19px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.4;
  margin: 0 0 12px 0;
  transition: color 0.3s ease;
}

.grid-post-card:hover .grid-title {
  color: #e50914;
}

.grid-excerpt {
  font-size: 15px;
  color: #64748b;
  line-height: 1.6;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 80px 20px;
  font-size: 18px;
  color: #64748b;
  font-weight: 600;
}

/* ---------------- MODAL STYLES ---------------- */
.blog-modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(5px);
  z-index: 9999;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.blog-modal-content {
  background: #ffffff;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 25px 50px rgba(0,0,0,0.25);
  position: relative;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
}

.modal-brand {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 20px;
  font-weight: bold;
  color: #64748b;
  cursor: pointer;
  transition: color 0.3s;
}

.btn-close-modal:hover {
  color: #e50914;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 0 0 40px 0;
}

.loading-spinner {
  text-align: center;
  padding: 100px 20px;
  font-size: 18px;
  color: #64748b;
}

.modal-cover {
  width: 100%;
  height: 350px;
}

.modal-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-article-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 30px 40px 10px;
}

.modal-cat-tag {
  background: #f1f5f9;
  color: #e50914;
  font-weight: 700;
  font-size: 12px;
  padding: 4px 12px;
  border-radius: 20px;
  text-transform: uppercase;
}

.modal-date {
  font-size: 13px;
  color: #94a3b8;
  font-weight: 600;
}

.modal-title {
  font-size: 32px;
  font-weight: 900;
  color: #0f172a;
  padding: 0 40px;
  margin: 10px 0 20px 0;
  line-height: 1.3;
}

.modal-excerpt {
  padding: 0 40px;
  font-size: 17px;
  color: #475569;
  font-style: italic;
  line-height: 1.6;
  margin-bottom: 30px;
  border-left: 4px solid #e50914;
  margin-left: 40px;
  padding-left: 20px;
}

.modal-html-content {
  padding: 0 40px;
  font-size: 16px;
  color: #334155;
  line-height: 1.8;
}

/* Xử lý định dạng HTML bên trong modal */
.modal-html-content :deep(img) {
  max-width: 100%;
  border-radius: 8px;
  margin: 20px 0;
}
.modal-html-content :deep(h2), .modal-html-content :deep(h3) {
  color: #0f172a;
  margin-top: 30px;
  margin-bottom: 15px;
}
.modal-html-content :deep(p) {
  margin-bottom: 16px;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid #f1f5f9;
  text-align: right;
  background: #ffffff;
}

.btn-close-bottom {
  background: #e50914;
  color: #ffffff;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.3s;
}

.btn-close-bottom:hover {
  background: #cc0812;
}

/* Animations */
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.3s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}
.modal-fade-enter-active .blog-modal-content {
  animation: modalSlideUp 0.3s ease-out;
}
.modal-fade-leave-active .blog-modal-content {
  animation: modalSlideDown 0.3s ease-in;
}

@keyframes modalSlideUp {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
@keyframes modalSlideDown {
  from { transform: translateY(0); opacity: 1; }
  to { transform: translateY(30px); opacity: 0; }
}

@media (max-width: 768px) {
  .modal-cover {
    height: 200px;
  }
  .modal-title {
    font-size: 24px;
    padding: 0 20px;
    margin-left: 0;
  }
  .modal-article-info, .modal-html-content {
    padding: 20px;
  }
  .modal-excerpt {
    margin-left: 20px;
    padding-right: 20px;
  }
}
</style>

