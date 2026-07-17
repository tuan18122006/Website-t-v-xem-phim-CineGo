<template>
  <div class="blog-preview-container">
    <div class="preview-navigation-bar">
      <button @click="$router.push('/admin/blogs')" class="btn-back-list">⬅️ Quay lại danh sách quản lý</button>
      <button @click="$router.push(`/admin/blogs/edit/${post.id}`)" class="btn-edit-direct">✏️ Chỉnh sửa bài viết này</button>
    </div>

    <div v-if="loading" class="loading-center">Đang dựng giao diện bài viết...</div>
    
    <article v-else class="main-blog-article">
      <div class="article-meta">
        <span class="cat-badge">{{ post.category?.name }}</span>
        <span class="meta-date">⏱️ Xuất bản ngày: {{ new Date(post.updated_at).toLocaleDateString('vi-VN') }}</span>
      </div>

      <h1 class="article-title">{{ post.title }}</h1>
      
      <p class="article-excerpt-box"><strong>Tóm tắt:</strong> {{ post.excerpt }}</p>

      <div class="article-banner">
        <img :src="post.thumbnail_url" alt="Banner bài viết" />
      </div>

      <div class="article-rich-text" v-html="post.content"></div>

      <div v-if="post.movie" class="booking-cta-marketing-box">
        <div class="cta-poster-zone">
          <img :src="post.movie.poster_url || 'https://placehold.co/300x450?text=Poster'" alt="Poster Phim" />
        </div>
        <div class="cta-info-zone">
          <span class="cta-sub-tag">🔥 Độc quyền tại CineGo</span>
          <h3 class="cta-movie-title">{{ post.movie.title }}</h3>
          <p class="cta-message">Đọc review xong, bạn đã sẵn sàng thưởng thức siêu phẩm này trên màn ảnh rộng IMAX chưa?</p>
          
          <button @click="goToBooking(post.movie.id)" class="btn-cta-booking-now">
            🎟️ ĐẶT VÉ XEM PHIM NÀY NGAY 
          </button>
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../../api/axios';
import { toast } from '../../../utils/alert';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const post = ref({});

const fetchPostDetail = async () => {
  try {
    const response = await api.get(`/admin/blogs/${route.params.id}`);
    post.value = response.data.data || response.data;
  } catch (error) {
    toast('Không thể tải trang xem trước!', 'error');
    router.push('/admin/blogs');
  } finally {
    loading.value = false;
  }
};

const goToBooking = (movieId) => {
  alert(`Hệ thống sẽ điều hướng khách hàng ra trang mua vé trực tuyến của bộ phim có ID: #${movieId}`);
};

onMounted(fetchPostDetail);
</script>

<style scoped>
.blog-preview-container { max-width: 900px; margin: 0 auto; padding: 30px 20px; font-family: 'Inter', sans-serif;}
.preview-navigation-bar { display: flex; justify-content: space-between; margin-bottom: 25px; background: #1e293b; padding: 12px 20px; border-radius: 8px;}
.btn-back-list { background: transparent; border: none; color: white; cursor: pointer; font-weight: 600;}
.btn-edit-direct { background: #e50914; border: none; color: white; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer;}

.main-blog-article { background: white; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0;}
.article-meta { display: flex; gap: 15px; align-items: center; margin-bottom: 15px;}
.cat-badge { background: #fee2e2; color: #b91c1c; padding: 4px 12px; border-radius: 4px; font-weight: 700; font-size: 13px;}
.meta-date { color: #64748b; font-size: 13.5px;}
.article-title { font-size: 32px; font-weight: 800; color: #0f172a; line-height: 1.3; margin: 0 0 20px 0;}
.article-excerpt-box { background: #f8fafc; border-left: 4px solid #94a3b8; padding: 15px; color: #475569; font-style: italic; line-height: 1.5; margin-bottom: 25px;}
.article-banner { width: 100%; aspect-ratio: 16/9; border-radius: 10px; overflow: hidden; margin-bottom: 30px;}
.article-banner img { width: 100%; height: 100%; object-fit: cover;}
.article-rich-text { font-size: 16px; line-height: 1.8; color: #334155;}

/* STYLING BOX MARKETING ĐẶT VÉ SIÊU ĐẸP */
.booking-cta-marketing-box { display: flex; background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%); border: 2px dashed #f43f5e; border-radius: 12px; padding: 25px; margin-top: 50px; gap: 20px; align-items: center;}
.cta-poster-zone { width: 110px; height: 165px; box-shadow: 0 10px 20px rgba(0,0,0,0.15); border-radius: 6px; overflow: hidden; flex-shrink: 0;}
.cta-poster-zone img { width: 100%; height: 100%; object-fit: cover;}
.cta-info-zone { flex: 1;}
.cta-sub-tag { font-size: 11px; background: #e11d48; color: white; padding: 2px 6px; border-radius: 4px; font-weight: 800; text-transform: uppercase;}
.cta-movie-title { font-size: 22px; font-weight: 800; color: #9f1239; margin: 8px 0 4px 0;}
.cta-message { color: #475569; font-size: 14px; margin: 0 0 15px 0;}
.btn-cta-booking-now { background: linear-gradient(135deg, #e50914 0%, #9b000e 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3); transition: all 0.2s;}
.btn-cta-booking-now:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(229, 9, 20, 0.45);}
</style>