<template>
  <div class="blog-page-container">
    <section class="blog-hero">
      <div class="hero-overlay"></div>

      <div class="hero-content">
        <span class="hero-tag"> CINEGO BLOG </span>

        <h1 class="hero-title">Blog Điện Ảnh & Tin Tức Phim</h1>

        <p class="hero-desc">
          Cập nhật review phim, tin điện ảnh, hậu trường, xu hướng điện ảnh và
          các bài viết mới nhất từ CineGo.
        </p>

        <div class="category-list">
          <button
            class="category-btn"
            :class="{ active: activeCategory === 'all' }"
            @click="activeCategory = 'all'"
          >
            🔥 Tất cả
          </button>

          <button
            v-for="category in categories"
            :key="category.id"
            class="category-btn"
            :class="{ active: activeCategory === category.id }"
            @click="activeCategory = category.id"
          >
            {{ category.name }}
          </button>
        </div>
      </div>
    </section>

    <section v-if="featuredArticle" class="featured-section">
      <div class="featured-card">
        <div class="featured-image">
          <img
            :src="featuredArticle.thumbnail_url"
            :alt="featuredArticle.title"
          />

          <span class="featured-category">
            {{ featuredArticle.category?.name }}
          </span>
        </div>

        <div class="featured-content">
          <div class="featured-meta">
            <span>
              🗓
              {{ formatDate(featuredArticle.published_at) }}
            </span>

            <span> ✍ Admin </span>
          </div>

          <h2>
            {{ featuredArticle.title }}
          </h2>

          <p>
            {{ featuredArticle.excerpt }}
          </p>

          <router-link class="btn-read" :to="`/blog/${featuredArticle.slug}`">
            Đọc tiếp →
          </router-link>
        </div>
      </div>
    </section>

    <section class="blog-grid-section">
      <h2 class="section-title">Bài viết mới</h2>

      <div v-if="filteredArticles.length" class="blog-grid">
        <article
          v-for="post in filteredArticles"
          :key="post.id"
          class="blog-card"
        >
          <div class="card-image">
            <img :src="post.thumbnail_url" :alt="post.title" />

            <span class="card-category">
              {{ post.category?.name }}
            </span>
          </div>

          <div class="card-body">
            <small>
              {{ formatDate(post.published_at) }}
            </small>

            <h3>
              {{ post.title }}
            </h3>

            <p>
              {{ post.excerpt }}
            </p>

            <router-link class="btn-detail" :to="`/blog/${post.slug}`">
              Xem chi tiết →
            </router-link>
          </div>
        </article>
      </div>

      <div v-else class="empty-blog">
        <h3>Chưa có bài viết nào.</h3>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../../api/axios";

const blogs = ref([]);
const categories = ref([]);

const featuredArticle = ref(null);

const loading = ref(false);

const activeCategory = ref("all");

const formatDate = (date) => {
  if (!date) return "";

  return new Date(date).toLocaleDateString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
};

const fetchBlogs = async () => {
  loading.value = true;

  try {
    const res = await api.get("/blogs");

    const data = res.data.data || res.data;

    // chỉ lấy bài đã xuất bản
    const published = data.filter((item) => item.status === "published");

    // mới nhất lên đầu
    published.sort(
      (a, b) => new Date(b.published_at) - new Date(a.published_at),
    );

    blogs.value = published;

    if (published.length) {
      featuredArticle.value = published[0];
    }
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const res = await api.get("/blog-categories");

    categories.value = res.data.data || res.data;
  } catch (err) {
    console.error(err);
  }
};

const filteredArticles = computed(() => {
  let list = blogs.value;

  if (featuredArticle.value) {
    list = list.filter((item) => item.id !== featuredArticle.value.id);
  }

  if (activeCategory.value === "all") {
    return list;
  }

  return list.filter((item) => item.category_id == activeCategory.value);
});

const keyword = ref("");

const searchedArticles = computed(() => {
  if (!keyword.value) {
    return filteredArticles.value;
  }

  return filteredArticles.value.filter((item) => {
    return (
      item.title.toLowerCase().includes(keyword.value.toLowerCase()) ||
      item.excerpt?.toLowerCase().includes(keyword.value.toLowerCase())
    );
  });
});

const refreshFeatured = () => {
  if (!blogs.value.length) {
    featuredArticle.value = null;

    return;
  }

  const newest = [...blogs.value].sort(
    (a, b) => new Date(b.published_at) - new Date(a.published_at),
  );

  featuredArticle.value = newest[0];
};

onMounted(async () => {
  await fetchCategories();

  await fetchBlogs();

  refreshFeatured();
});
</script>

<style scoped>
.blog-page-container {
  min-height: 100vh;
  background: #f8fafc;
  padding-bottom: 60px;
}

.blog-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #1a0004 0%, #000 100%);
  padding: 60px 20px;
  text-align: center;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(
    circle at top right,
    rgba(229, 9, 20, 0.25),
    transparent 55%
  );
  pointer-events: none;
}

.hero-content {
  position: relative;
  z-index: 2;
  max-width: 900px;
  margin: auto;
}

.hero-tag {
  display: inline-block;
  color: #e50914;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 2px;
  margin-bottom: 16px;
}

.hero-title {
  color: white;
  font-size: 48px;
  font-weight: 800;
  margin-bottom: 18px;
}

.hero-desc {
  color: #d1d5db;
  font-size: 16px;
  line-height: 1.8;
  margin-bottom: 35px;
}

.category-list {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 12px;
}

.category-btn {
  border: none;
  cursor: pointer;
  padding: 11px 22px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  color: white;
  font-weight: 600;
  transition: 0.25s;
}

.category-btn:hover {
  background: rgba(255, 255, 255, 0.18);
}

.category-btn.active {
  background: #e50914;
}

.featured-section {
  max-width: 1200px;
  margin: 40px auto 60px; /* Đổi -50px thành 40px */
  padding: 0 20px;
  position: relative;
  z-index: 20;
}

.featured-card {
  display: grid;
  grid-template-columns: 55% 45%;
  background: #fff;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 15px 45px rgba(0, 0, 0, 0.08);
  min-height: 430px;
}

.featured-image {
  position: relative;
  min-height: 430px;
  overflow: hidden;
}

.featured-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.featured-category {
  position: absolute;
  top: 20px;
  left: 20px;
  background: #e50914;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  padding: 8px 14px;
  border-radius: 8px;
}

.featured-content {
  padding: 42px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.featured-meta {
  display: flex;
  gap: 18px;
  color: #64748b;
  font-size: 13px;
  margin-bottom: 18px;
}

.featured-content h2 {
  font-size: 42px;
  line-height: 1.25;
  margin: 0 0 20px;
  color: #0f172a;
  font-weight: 800;
}

.featured-content p {
  color: #475569;
  line-height: 1.9;
  margin-bottom: 30px;
  font-size: 15px;
}

.btn-read {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 140px;
  height: 46px;
  background: #e50914;
  color: #fff;
  border-radius: 10px;
  font-weight: 700;
  text-decoration: none;
  transition: 0.25s;
}

.btn-read:hover {
  background: #c20811;
  transform: translateY(-2px);
}

.blog-grid-section {
  max-width: 1200px;
  margin: auto;
  padding: 0 20px;
}

.section-title {
  font-size: 30px;
  font-weight: 800;
  margin-bottom: 30px;
  color: #111827;
}

.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 30px;
}

.blog-card {
  background: white;
  overflow: hidden;
  border-radius: 20px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.05);
  transition: 0.3s;
}

.blog-card:hover {
  transform: translateY(-6px);
}

.card-image {
  position: relative;
  height: 220px;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-category {
  position: absolute;
  left: 16px;
  top: 16px;
  background: rgba(0, 0, 0, 0.75);
  color: white;
  padding: 5px 12px;
  border-radius: 7px;
  font-size: 12px;
}

.card-body {
  padding: 22px;
}

.card-body small {
  color: #64748b;
}

.card-body h3 {
  margin: 12px 0;
  color: #111827;
  font-size: 20px;
  line-height: 1.5;
}

.card-body p {
  color: #64748b;
  line-height: 1.7;
  margin-bottom: 25px;
}

.btn-detail {
  text-decoration: none;
  color: #e50914;
  font-weight: 700;
}

.btn-detail:hover {
  text-decoration: underline;
}

.empty-blog {
  text-align: center;
  background: white;
  padding: 60px;
  border-radius: 18px;
  color: #64748b;
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
}

</style>
