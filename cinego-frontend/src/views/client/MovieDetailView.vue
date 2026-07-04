<template>
  <div v-if="movie" class="movie-detail-view">
    <div class="detail-header glass-panel">
      <div class="poster-container">
        <img :src="movie.poster_url" :alt="movie.title" class="detail-poster" />
        <span class="detail-rating-badge" :class="getRatingClass(movie.rating)">
          {{ movie.rating }}
        </span>
      </div>

      <div class="info-container">
        <h1 class="movie-title">{{ movie.title }}</h1>

        <div class="genres-row">
          <span v-for="g in movie.genres" :key="g.id || g" class="detail-genre">
            {{ g.name || g }}
          </span>
        </div>

        <div class="metadata-grid">
          <div class="meta-item">
            <span class="meta-label">Thời lượng:</span>
            <span class="meta-value">{{ movie.duration }} phút</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Khởi chiếu:</span>
            <span class="meta-value">{{ formatDate(movie.release_date) }}</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Đạo diễn:</span>
            <span class="meta-value">John Doe</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Ngôn ngữ:</span>
            <span class="meta-value">Phụ đề tiếng Việt</span>
          </div>
        </div>

        <div class="movie-description">
          <h3>Nội Dung Phim</h3>
          <p>{{ movie.description || 'Bộ phim kể về một cuộc phiêu lưu kịch tính đầy bất ngờ, dẫn dắt người xem đi qua nhiều cung bậc cảm xúc với kỹ xảo hoành tráng và diễn xuất đỉnh cao từ dàn diễn viên hạng A.' }}</p>
        </div>
      </div>
    </div>

    <!-- Chọn lịch chiếu -->
    <section class="schedule-section glass-panel">
      <h2 class="section-title gradient-text-accent">Lịch Chiếu & Đặt Vé</h2>

      <!-- Chọn ngày -->
      <div class="date-selector">
        <button v-for="(day, index) in availableDays" :key="index" class="date-btn"
          :class="{ active: selectedDayIndex === index }" @click="selectedDayIndex = index">
          <span class="day-name">{{ day.weekday }}</span>
          <span class="day-date">{{ day.dateLabel }}</span>
        </button>
      </div>

      <div v-if="loadingShowtimes" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải lịch chiếu...</p>
      </div>

      <!-- Khung hiển thị Suất Chiếu theo Phòng -->
      <div v-else class="showtimes-by-room">
        <div v-for="room in showtimesByRoom" :key="room.roomId" class="room-row glass-panel">
          <div class="room-info">
            <h3 class="room-name">{{ room.roomName }}</h3>
            <span class="room-type">Phòng chiếu 2D & Âm thanh Dolby Atmos</span>
          </div>

          <div class="showtimes-grid">
            <button v-for="showtime in room.showtimes" :key="showtime.id" class="showtime-btn"
              :class="{ active: selectedShowtime?.id === showtime.id }" @click="selectShowtime(showtime)">
              <span class="time-label">{{ showtime.start_time }}</span>
              <span class="seat-available">Trống {{ showtime.available_seats || 85 }} ghế</span>
            </button>
          </div>
        </div>

        <div v-if="showtimesByRoom.length === 0" class="no-showtimes">
          Không có suất chiếu nào cho ngày đã chọn.
        </div>
      </div>

      <!-- Footer điều hướng -->
      <div class="schedule-footer" v-if="selectedShowtime">
        <div class="selected-summary">
          <p>Suất chiếu đã chọn: <strong>{{ selectedShowtime.start_time }}</strong> - Phòng: <strong>{{
            selectedShowtime.room_name }}</strong></p>
          <p>Ngày chiếu: <strong>{{ availableDays[selectedDayIndex].fullLabel }}</strong></p>
        </div>
        <button @click="proceedToSeatSelection" class="btn-proceed">
          Tiếp Tục Chọn Ghế
        </button>
      </div>
    </section>

    <section class="casts-section glass-panel">
      <h2 class="section-title gradient-text-accent">Diễn Viên Chính</h2>
      <div class="casts-grid">
        <div v-for="cast in (movie.casts || mockCasts)" :key="cast.id || cast.name" class="cast-card">
          <div class="cast-avatar-box">
            <img
              :src="cast.avatar_url || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80'"
              :alt="cast.name" class="cast-avatar" />
          </div>
          <div class="cast-info">
            <h4 class="cast-name">{{ cast.name }}</h4>
            <p class="cast-character">trong vai {{ cast.character || 'Character' }}</p>
          </div>
        </div>
      </div>
    </section>

    <section class="reviews-section glass-panel">
      <h2 class="section-title gradient-text-accent">Đánh Giá Từ Mọt Phim</h2>

      <div class="reviews-summary">
        <div class="summary-card">
          <span class="summary-label">Điểm trung bình</span>
          <strong class="summary-value">{{ reviewSummary.average_rating.toFixed(1) }}/5</strong>
          <span class="summary-subtext">Dựa trên {{ reviewSummary.total_reviews }} lượt đánh giá</span>
        </div>
        <div class="summary-card">
          <span class="summary-label">Tổng lượt đánh giá</span>
          <strong class="summary-value">{{ reviewSummary.total_reviews }}</strong>
          <span class="summary-subtext">Khách hàng đã chia sẻ cảm nhận</span>
        </div>
      </div>

      <div class="review-form-box">
        <h4 class="form-title">{{ isEditingReview ? 'Chỉnh sửa đánh giá của bạn' : 'Để lại đánh giá của bạn' }}</h4>
        <div v-if="reviewNotice" class="review-notice" :class="reviewNotice.type">
          {{ reviewNotice.message }}
        </div>
        <div v-if="canSubmitReview" class="rating-input-row">
          <span>Đánh giá của bạn:</span>
          <div class="stars-picker">
            <span v-for="star in 5" :key="star" class="star-pick" :class="{ active: myReview.rating >= star }"
              @click="myReview.rating = star">★</span>
          </div>
        </div>
        <div v-if="canSubmitReview" class="comment-input-group">
          <textarea v-model="myReview.comment" placeholder="Chia sẻ cảm nghĩ của bạn về bộ phim này..."
            class="review-textarea"></textarea>
          <div class="review-action-row">
            <button v-if="isEditingReview" @click="cancelEditReview" class="btn-cancel-review">Hủy</button>
            <button @click="submitReview" class="btn-submit-review">{{ isEditingReview ? 'Cập nhật' : 'Gửi Review' }}</button>
          </div>
        </div>
      </div>

      <div v-if="reviewsLoading" class="loading-state small-state">
        <div class="spinner"></div>
        <p>Đang tải đánh giá...</p>
      </div>

      <div v-else class="reviews-list">
        <div v-for="review in reviews" :key="review.id" class="review-item-card">
          <div class="review-user-header">
            <div class="user-meta">
              <span class="user-avatar-text">{{ getDisplayName(review.user?.name || review.user_name || 'U').charAt(0).toUpperCase() }}</span>
              <div>
                <h5 class="user-name">{{ review.user?.name || review.user_name || 'Khách hàng CineGo' }}</h5>
                <span class="review-date">{{ formatDate(review.created_at) || 'Vừa xong' }}</span>
              </div>
            </div>
            <div class="review-actions">
              <div class="user-stars">
                <span v-for="s in review.rating" :key="'fill-' + s" class="star-filled">★</span>
                <span v-for="s in (5 - review.rating)" :key="'empty-' + s" class="star-empty">★</span>
              </div>
              <div v-if="canManageReview(review)" class="review-action-buttons">
                <button @click="startEditReview(review)" class="review-action-btn">Sửa</button>
              </div>
            </div>
          </div>
          <p class="review-content">{{ review.comment }}</p>
        </div>

        <div v-if="reviews.length === 0" class="no-reviews">
          Chưa có đánh giá nào. Hãy là người đầu tiên review bộ phim này!
        </div>

        <div v-if="sampleReviews.length > 0" class="sample-reviews-section">
          <h3 class="sample-reviews-title">Bình luận tham khảo cho phim này</h3>
          <div class="sample-reviews-grid">
            <div v-for="comment in sampleReviews" :key="comment.id" class="sample-review-card">
              <div class="sample-review-header">
                <div class="sample-user-meta">
                  <span class="sample-user-avatar-text">{{ comment.userName.charAt(0).toUpperCase() }}</span>
                  <div>
                    <p class="sample-review-user">{{ comment.userName }}</p>
                    <div class="sample-review-meta">
                      <span class="sample-review-date">{{ comment.timeAgo }}</span>
                      <span class="sample-review-rating-badge">{{ comment.rating }}/5</span>
                    </div>
                    <div class="sample-review-stars">
                      <span v-for="star in comment.rating" :key="comment.id + '-filled-' + star" class="sample-star-filled">★</span>
                      <span v-for="star in (5 - comment.rating)" :key="comment.id + '-empty-' + star" class="sample-star-empty">★</span>
                    </div>
                  </div>
                </div>
              </div>
              <p class="sample-review-text">{{ comment.comment }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
  <div v-else class="loading-state">
    <div class="spinner"></div>
    <p>Đang tải chi tiết phim...</p>
  </div>


</template>



<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useBookingStore } from '../../stores/booking';
import api from '../../api/axios';

const route = useRoute();
const router = useRouter();
const bookingStore = useBookingStore();

const movie = ref(null);
const loadingShowtimes = ref(false);
const selectedDayIndex = ref(0);
const selectedShowtime = ref(null);
const showtimesByRoom = ref([]);
const reviews = ref([]);
const reviewSummary = ref({ average_rating: 0, total_reviews: 0 });
const reviewsLoading = ref(false);
const reviewNotice = ref(null);
const canSubmitReview = ref(false);

const calculateReviewSummary = (includeSample = false) => {
  const actualReviews = Array.isArray(reviews.value) ? reviews.value : [];
  const sampleList = includeSample && Array.isArray(sampleReviews.value) ? sampleReviews.value : [];
  const combined = [...actualReviews, ...sampleList];
  const total = combined.length;
  const sum = combined.reduce((acc, item) => acc + Number(item.rating || 0), 0);
  reviewSummary.value = {
    average_rating: total > 0 ? sum / total : 0,
    total_reviews: total
  };
};

const availableDays = ref([]);

// Khởi tạo ngày hôm nay nếu DB chưa có lịch
const defaultDays = () => {
  const days = [];
  const weekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];

  for (let i = 0; i < 5; i++) {
    const d = new Date();
    d.setDate(d.getDate() + i);
    days.push({
      dateStr: d.toISOString().split('T')[0],
      weekday: i === 0 ? 'Hôm nay' : weekdays[d.getDay()],
      dateLabel: `${d.getDate()}/${d.getMonth() + 1}`,
      fullLabel: d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
    });
  }
  return days;
};

// State phục vụ việc gửi bình luận / review lên DB
const myReview = ref({
  rating: 5,
  comment: ''
});
const isEditingReview = ref(false);
const editingReviewId = ref(null);

// Dữ liệu mồi cục bộ cho thành phần phụ không ảnh hưởng luồng mua vé (Casts/Reviews) nếu DB trống
const mockCasts = ref([
  { id: 1, name: 'Diễn viên chính', character: 'Vai diễn xuất sắc', avatar_url: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=150&q=80' }
]);

// 🔥 HÀM LẤY CHI TIẾT PHIM THUẦN TỪ DATABASE
const fetchMovieDetail = async () => {
  const movieId = route.params.id;
  try {
    const response = await api.get(`/movies/${movieId}`);
    // Chấp nhận cấu trúc bọc dữ liệu chuẩn của API
    const resData = response.data?.data || response.data;
    
    if (resData) {
      movie.value = resData;
      bookingStore.selectMovie(movie.value);
      await fetchMovieReviews();
      console.log('=== ĐÃ TẢI CHI TIẾT PHIM TỪ DB ===', movie.value);
    } else {
      console.error('Không tìm thấy dữ liệu bộ phim này trong DB.');
    }
  } catch (err) {
    console.error('Lỗi API lấy chi tiết phim từ cơ sở dữ liệu:', err);
    // Nếu trong Pinia store trang chủ đã nạp sẵn phim này thì lôi ra hiển thị tiếp
    if (bookingStore.selectedMovie && bookingStore.selectedMovie.id == movieId) {
      movie.value = bookingStore.selectedMovie;
    }
  }
};

// 🔥 HÀM LẤY CÁC NGÀY CÓ SUẤT CHIẾU TRONG TƯƠNG LAI
const fetchAvailableDates = async () => {
  if (!movie.value) return;
  try {
    const response = await api.get(`/movies/${movie.value.id}/available-dates`);
    const dates = response.data?.data || [];
    
    if (dates.length > 0) {
      const weekdays = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
      const todayStr = new Date().toISOString().split('T')[0];
      
      availableDays.value = dates.map(dateStr => {
        const d = new Date(dateStr);
        return {
          dateStr: dateStr,
          weekday: dateStr === todayStr ? 'Hôm nay' : weekdays[d.getDay()],
          dateLabel: `${d.getDate()}/${d.getMonth() + 1}`,
          fullLabel: d.toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
        };
      });
    } else {
      availableDays.value = defaultDays(); // Fallback nếu phim chưa có lịch chiếu
    }
  } catch (err) {
    console.error('Lỗi lấy ngày chiếu:', err);
    availableDays.value = defaultDays();
  }
};

// 🔥 HÀM LẤY SUẤT CHIẾU THEO PHÒNG THUẦN TỪ DATABASE
const fetchShowtimes = async () => {
  if (!movie.value || availableDays.value.length === 0) return;
  loadingShowtimes.value = true;
  selectedShowtime.value = null;

  const dateStr = availableDays.value[selectedDayIndex.value].dateStr;
  try {
    // Gọi route public: /api/movies/{id}/showtimes?date=YYYY-MM-DD
    const response = await api.get(`/movies/${movie.value.id}/showtimes`, {
      params: { date: dateStr }
    });
    
    const resData = response.data?.data || response.data;
    showtimesByRoom.value = Array.isArray(resData) ? resData : [];
    console.log('=== ĐÃ TẢI LỊCH CHIẾU TỪ DB ===', showtimesByRoom.value);
  } catch (err) {
    console.error('Lỗi khi lấy danh sách suất chiếu từ DB:', err);
    showtimesByRoom.value = []; // Trả về mảng rỗng nếu DB chưa được thiết lập suất chiếu
  } finally {
    loadingShowtimes.value = false;
  }
};

const fetchMovieReviews = async () => {
  if (!movie.value?.id) return;
  reviewsLoading.value = true;

  try {
    const response = await api.get(`/movies/${movie.value.id}/reviews`);
    const resData = response.data?.data || response.data;

    reviews.value = Array.isArray(resData?.reviews) ? resData.reviews : [];
    reviewNotice.value = {
      type: resData?.review_status === 'eligible' ? 'success' : 'info',
      message: resData?.review_message || 'Bạn cần đăng nhập và đủ điều kiện để đánh giá phim này.'
    };
    canSubmitReview.value = Boolean(resData?.can_review);

    movie.value.reviews = reviews.value;
    calculateReviewSummary(true);
  } catch (err) {
    console.error('Lỗi khi tải đánh giá phim:', err);
    reviews.value = [];
    reviewSummary.value = { average_rating: 0, total_reviews: 0 };
    reviewNotice.value = { type: 'info', message: 'Không thể tải trạng thái đánh giá.' };
    canSubmitReview.value = false;
  } finally {
    reviewsLoading.value = false;
  }
};

const submitReview = async () => {
  if (!myReview.value.comment.trim()) {
    alert('Vui lòng nhập nội dung đánh giá trước khi gửi!');
    return;
  }

  try {
    const dataPost = {
      rating: myReview.value.rating,
      comment: myReview.value.comment
    };

    let response;
    if (isEditingReview.value && editingReviewId.value) {
      response = await api.put(`/movies/${movie.value.id}/reviews/${editingReviewId.value}`, dataPost);
    } else {
      response = await api.post(`/movies/${movie.value.id}/reviews`, dataPost);
    }

    const savedReview = response.data?.data || response.data;

    if (isEditingReview.value && editingReviewId.value) {
      reviews.value = reviews.value.map((item) => item.id === savedReview.id ? savedReview : item);
    } else {
      reviews.value.unshift(savedReview);
    }

    movie.value.reviews = reviews.value;
    calculateReviewSummary(true);

    myReview.value.comment = '';
    myReview.value.rating = 5;
    isEditingReview.value = false;
    editingReviewId.value = null;
    reviewNotice.value = { type: 'success', message: 'Đánh giá của bạn đã được gửi thành công.' };
    canSubmitReview.value = false;

    alert('Đánh giá của bạn đã được gửi thành công!');
  } catch (err) {
    console.error('Lỗi khi lưu review vào Database:', err);
    alert(err.response?.data?.message || 'Không thể gửi đánh giá, vui lòng kiểm tra trạng thái đăng nhập hệ thống!');
  }
};

const startEditReview = (review) => {
  myReview.value.rating = review.rating;
  myReview.value.comment = review.comment || '';
  isEditingReview.value = true;
  editingReviewId.value = review.id;
};

const cancelEditReview = () => {
  myReview.value.rating = 5;
  myReview.value.comment = '';
  isEditingReview.value = false;
  editingReviewId.value = null;
};

const canManageReview = (review) => {
  const authUser = JSON.parse(localStorage.getItem('cinego_user') || 'null');
  if (!authUser) return false;
  return authUser.id === review.user_id || authUser.role === 'admin';
};

const getRatingClass = (rating) => {
  if (!rating) return 'rating-g';
  const r = rating.toUpperCase();
  if (r.includes('18') || r === 'R') return 'rating-t18';
  if (r.includes('16')) return 'rating-t16';
  if (r.includes('13') || r === 'PG-13') return 'rating-t13';
  return 'rating-g';
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const getDisplayName = (name) => {
  if (!name) return 'U';
  return name;
};

const sampleReviews = ref([]);

watch([reviews, sampleReviews], () => {
  calculateReviewSummary(true);
}, { deep: true });

const getSampleReviews = (movieTitle) => {
  const samples = {
    'Doctor Strange: Đa Vũ Trụ Hỗn Loạn': [
      { id: 'ds-s-1', userName: 'Phan Minh', timeAgo: '3 giờ trước', rating: 5, comment: 'Cảnh hành động hoành tráng, tôi thích nhất phần đa vũ trụ và pháp thuật.' },
      { id: 'ds-s-2', userName: 'Ngọc Trâm', timeAgo: '5 giờ trước', rating: 4, comment: 'Cốt truyện hơi rối nhưng diễn xuất rất tốt và kịch bản thông minh.' },
      { id: 'ds-s-3', userName: 'Huyền My', timeAgo: '1 ngày trước', rating: 5, comment: 'Phần kỹ xảo và âm nhạc quá chất, cảm giác xem rạp đã hơn rất nhiều.' },
      { id: 'ds-s-4', userName: 'Minh Hằng', timeAgo: '1 ngày trước', rating: 4, comment: 'Một số đoạn hơi khó hiểu với người không theo dõi vũ trụ Marvel nhưng vẫn rất thú vị.' },
      { id: 'ds-s-5', userName: 'Quốc Bảo', timeAgo: '2 ngày trước', rating: 5, comment: 'Mình muốn xem lại ngay sau khi ra rạp, quá đã cho fan Marvel.' }
    ],
    'Avatar: Dòng Chảy Của Nước': [
      { id: 'av-s-1', userName: 'Lê Lan', timeAgo: '4 giờ trước', rating: 5, comment: 'Cảnh dưới nước đẹp mê hoặc, âm thanh và màu sắc quá ấn tượng.' },
      { id: 'av-s-2', userName: 'Đức Anh', timeAgo: '6 giờ trước', rating: 5, comment: 'Nếu có thể, mình sẽ xem lại một lần nữa vì quá đã mắt.' },
      { id: 'av-s-3', userName: 'Hải Yến', timeAgo: '14 giờ trước', rating: 4, comment: 'Cốt truyện sâu sắc, chỉ mong đoạn cuối đỡ dài hơn một chút.' },
      { id: 'av-s-4', userName: 'Thảo Nhi', timeAgo: '1 ngày trước', rating: 5, comment: 'Những phân cảnh hành động và sinh thái dưới nước thật sự xuất sắc.' },
      { id: 'av-s-5', userName: 'Vĩnh Khang', timeAgo: '2 ngày trước', rating: 4, comment: 'Mình thấy hợp để đi xem cùng gia đình, vừa giải trí vừa đáng suy ngẫm.' }
    ],
    'Kẻ Kiến Tạo (The Creator)': [
      { id: 'tc-s-1', userName: 'Nguyễn Sơn', timeAgo: '30 phút trước', rating: 5, comment: 'Chủ đề AI rất hợp thời, nội dung vừa hành động vừa có chiều sâu.' },
      { id: 'tc-s-2', userName: 'Bảo Châu', timeAgo: '2 giờ trước', rating: 4, comment: 'Nhiều phân cảnh giàu cảm xúc, kết cấu phim khiến mình suy nghĩ lâu.' },
      { id: 'tc-s-3', userName: 'Kiều Oanh', timeAgo: '7 giờ trước', rating: 5, comment: 'Diễn viên thể hiện tự nhiên, tôi rất thích cách xây dựng nhân vật.' },
      { id: 'tc-s-4', userName: 'Duy Phương', timeAgo: '1 ngày trước', rating: 4, comment: 'Cảnh chiến đấu đẹp, tuy hơi nặng đề tài nhưng vẫn rất đáng xem.' },
      { id: 'tc-s-5', userName: 'Trọng Kiên', timeAgo: '1 ngày trước', rating: 5, comment: 'Tôi đánh giá cao phần kịch bản và thông điệp nhân văn của phim.' }
    ]
  };
  return samples[movieTitle] || [];
};

const selectShowtime = (showtime) => {
  selectedShowtime.value = showtime;
};

// Theo dõi thay đổi thanh ngày chiếu để gọi lại suất chiếu tương ứng của DB
watch(selectedDayIndex, () => {
  fetchShowtimes();
});

onMounted(async () => {
  await fetchMovieDetail();
  await fetchAvailableDates();
  await fetchShowtimes();
  sampleReviews.value = getSampleReviews(movie.value?.title);
  calculateReviewSummary(true);
});

// Chuyển tiếp sang màn hình chọn ghế ngồi thực tế
const proceedToSeatSelection = () => {
  if (selectedShowtime.value) {
    const formattedShowtime = {
      ...selectedShowtime.value,
      date: availableDays.value[selectedDayIndex.value].dateStr,
      dateLabel: availableDays.value[selectedDayIndex.value].fullLabel
    };
    bookingStore.selectShowtime(formattedShowtime);
    router.push('/booking/seats');
  }
};
</script>


<style scoped>
@import '../../assets/css/pages/movie-detail.css';
</style>
