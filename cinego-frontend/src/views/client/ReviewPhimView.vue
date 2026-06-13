<template>
  <div class="review-page-container">
    <!-- HERO BANNER SECTION -->
    <div class="review-hero-banner">
      <div class="hero-glow"></div>
      <div class="hero-content">
        <span class="hero-subtitle">MOMO CINEMA INSPIRED</span>
        <h1 class="hero-title">Đánh Giá & Review Phim Chiếu Rạp</h1>
        <p class="hero-desc">
          Nơi cộng đồng người yêu điện ảnh CineGo chia sẻ góc nhìn chân thực, đánh giá chất lượng phim khách quan và tin cậy nhất.
        </p>
        
        <!-- STATS BADGES -->
        <div class="hero-stats">
          <div class="stat-badge">
            <span class="stat-num">360+</span>
            <span class="stat-lbl">Phim đã Review</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-badge">
            <span class="stat-num">48K+</span>
            <span class="stat-lbl">Bình luận từ cộng đồng</span>
          </div>
          <div class="stat-divider"></div>
          <div class="stat-badge">
            <span class="stat-num">15K+</span>
            <span class="stat-lbl">Đánh giá tin cậy</span>
          </div>
        </div>
      </div>
    </div>

    <!-- MAIN GRID SECTION -->
    <div class="review-content-section">
      <h2 class="section-title">Bình luận nổi bật</h2>
      
      <div class="reviews-grid">
        <div v-for="review in movieReviews" :key="review.id" class="review-card glass-panel">
          <!-- Movie Trailer Preview Area -->
          <div class="movie-preview-box">
            <img :src="review.moviePoster" :alt="review.movieTitle" class="movie-backdrop-img" />
            <div class="overlay-gradient"></div>
            
            <!-- Play Button simulation -->
            <button class="btn-play-preview" title="Xem trailer review">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor">
                <polygon points="5 3 19 12 5 21 5 3" />
              </svg>
            </button>
            
            <span class="movie-rating-pill">⭐ {{ review.rating }}/5</span>
            <span class="movie-duration-pill">{{ review.duration }} phút</span>
          </div>
          
          <!-- Card Info -->
          <div class="review-card-body">
            <h3 class="movie-title">{{ review.movieTitle }}</h3>
            <span class="movie-genres">{{ review.genres.join(', ') }}</span>
            
            <div class="user-comment-wrap">
              <div class="user-meta">
                <span class="user-avatar" :style="{ background: review.avatarColor }">{{ review.userInitials }}</span>
                <div>
                  <h4 class="user-name">{{ review.userName }}</h4>
                  <small class="comment-time">{{ review.timeAgo }}</small>
                </div>
                <!-- Verification Badge -->
                <span class="verified-buyer-badge">✓ Đã mua vé qua CineGo</span>
              </div>
              <p class="user-comment-text">"{{ review.comment }}"</p>
            </div>
            
            <div class="card-footer-action">
              <router-link to="/mua-ve" class="btn-book-ticket">
                Đặt vé xem phim
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const movieReviews = ref([
  {
    id: 1,
    movieTitle: 'Doctor Strange: Đa Vũ Trụ Hỗn Loạn',
    genres: ['Hành Động', 'Viễn Tưởng', 'Kỳ Ảo'],
    rating: '4.8',
    duration: '126',
    moviePoster: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=600&q=80',
    userName: 'Trần Anh Tuấn',
    userInitials: 'AT',
    avatarColor: 'linear-gradient(135deg, #e50914, #9b000e)',
    timeAgo: '1 ngày trước',
    comment: 'Kỹ xảo hình ảnh vô cùng hoành tráng, các phân cảnh đa vũ trụ làm quá đỉnh. Thích nhất phần âm nhạc dồn dập kịch tính. Rất đáng tiền xem rạp LUXURY!',
  },
  {
    id: 2,
    movieTitle: 'Avatar: Dòng Chảy Của Nước',
    genres: ['Kỳ Ảo', 'Viễn Tưởng', 'Hành Động'],
    rating: '5.0',
    duration: '192',
    moviePoster: 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=600&q=80',
    userName: 'Hoàng Linh Trang',
    userInitials: 'LT',
    avatarColor: 'linear-gradient(135deg, #7c4dff, #512da8)',
    timeAgo: '2 ngày trước',
    comment: 'Tác phẩm hình ảnh hoàn hảo nhất từng được xem! 3 tiếng đồng hồ trôi qua nhanh như một cái chớp mắt. Khuyên mọi người nên xem định dạng IMAX 3D tại CineGo.',
  },
  {
    id: 3,
    movieTitle: 'Kẻ Kiến Tạo (The Creator)',
    genres: ['Viễn Tưởng', 'Hành Động', 'Drama'],
    rating: '4.7',
    duration: '133',
    moviePoster: 'https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=600&q=80',
    userName: 'Nguyễn Văn Hùng',
    userInitials: 'VH',
    avatarColor: 'linear-gradient(135deg, #00bcd4, #00838f)',
    timeAgo: '5 ngày trước',
    comment: 'Cốt truyện nhân văn sâu sắc về AI và con người. Âm hưởng hùng vĩ, diễn xuất xuất thần của diễn viên nhí. Bố cục phim cực kỳ chặt chẽ và lôi cuốn.',
  },
  {
    id: 4,
    movieTitle: 'Spider-Man: Across the Spider-Verse',
    genres: ['Hoạt Hình', 'Hành Động', 'Kỳ Ảo'],
    rating: '4.9',
    duration: '140',
    moviePoster: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=600&q=80',
    userName: 'Phạm Minh Đức',
    userInitials: 'MĐ',
    avatarColor: 'linear-gradient(135deg, #ff9800, #e65100)',
    timeAgo: '1 tuần trước',
    comment: 'Hoạt họa xuất sắc vượt tầm thời đại, cách phối màu và nhạc phim khiến người xem nổi da gà. Xứng đáng là siêu phẩm hoạt hình xuất sắc nhất năm.',
  }
]);
</script>

<style scoped>
.review-page-container {
  width: 100%;
  min-height: 100vh;
  background-color: #ffffff;
  padding-bottom: 60px;
}

/* HERO BANNER */
.review-hero-banner {
  position: relative;
  background: linear-gradient(135deg, #180004 0%, #000000 100%);
  padding: 80px 24px;
  border-radius: 24px;
  overflow: hidden;
  margin-bottom: 40px;
  border: 1px solid rgba(229, 9, 20, 0.1);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
}

.hero-glow {
  position: absolute;
  top: -50%;
  right: -20%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(229, 9, 20, 0.25) 0%, transparent 70%);
  filter: blur(50px);
  pointer-events: none;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 2;
}

.hero-subtitle {
  font-size: 11px;
  font-weight: 800;
  color: #e50914;
  letter-spacing: 2px;
  text-transform: uppercase;
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

.hero-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 32px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 18px 32px;
  border-radius: 16px;
  max-width: 650px;
  margin: 0 auto;
}

.stat-badge {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.stat-num {
  font-size: 24px;
  font-weight: 800;
  color: #e50914;
}

.stat-lbl {
  font-size: 11.5px;
  color: #94a3b8;
  margin-top: 4px;
}

.stat-divider {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
}

/* MAIN CONTENT */
.review-content-section {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.section-title {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 24px;
  border-left: 5px solid #e50914;
  padding-left: 12px;
}

/* REVIEWS GRID */
.reviews-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(540px, 1fr));
  gap: 30px;
}

@media (max-width: 768px) {
  .reviews-grid {
    grid-template-columns: 1fr;
  }
}

.review-card {
  background: #ffffff;
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.12);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.03);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: flex;
  flex-direction: column;
}

.review-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(229, 9, 20, 0.08);
  border-color: rgba(229, 9, 20, 0.15);
}

.movie-preview-box {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.movie-backdrop-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.review-card:hover .movie-backdrop-img {
  transform: scale(1.05);
}

.overlay-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.7) 100%);
}

.btn-play-preview {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #e50914;
  color: #ffffff;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.4);
  transition: transform 0.25s ease, background-color 0.25s ease;
}

.btn-play-preview svg {
  margin-left: 3px;
}

.btn-play-preview:hover {
  transform: translate(-50%, -50%) scale(1.1);
  background: #ff121f;
}

.movie-rating-pill {
  position: absolute;
  top: 16px;
  left: 16px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(4px);
  color: #ffffff;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.movie-duration-pill {
  position: absolute;
  bottom: 16px;
  right: 16px;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(4px);
  color: #ffffff;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.review-card-body {
  padding: 24px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.movie-title {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
  line-height: 1.3;
}

.movie-genres {
  font-size: 12px;
  font-weight: 600;
  color: #e50914;
  text-transform: uppercase;
  margin-bottom: 20px;
  letter-spacing: 0.5px;
}

.user-comment-wrap {
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
  margin-bottom: 20px;
  flex: 1;
}

.user-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 10px;
  position: relative;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: #ffffff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 11.5px;
}

.user-name {
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
}

.comment-time {
  font-size: 10px;
  color: #94a3b8;
}

.verified-buyer-badge {
  margin-left: auto;
  background: rgba(16, 185, 129, 0.08);
  color: #10b981;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 9.5px;
  font-weight: 700;
  border: 1px solid rgba(16, 185, 129, 0.15);
}

.user-comment-text {
  font-size: 13px;
  color: #475569;
  line-height: 1.5;
  margin: 0;
  font-style: italic;
}

.card-footer-action {
  margin-top: auto;
  border-top: 1px solid #f1f5f9;
  padding-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.btn-book-ticket {
  background: #e50914;
  color: #ffffff;
  font-weight: 700;
  font-size: 12.5px;
  padding: 10px 20px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 10px rgba(229, 9, 20, 0.15);
}

.btn-book-ticket:hover {
  background: #ff121f;
  transform: translateY(-1px);
  box-shadow: 0 6px 15px rgba(229, 9, 20, 0.25);
}
</style>
