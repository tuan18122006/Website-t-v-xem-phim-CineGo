<template>
    <div class="reviews-page-container">
        <div class="reviews-header text-center">
            <h1 class="page-title">Bình luận nổi bật</h1>
            <p class="page-subtitle">Cộng đồng mọt phim CineGo nói gì về các bộ phim hot nhất hiện nay?</p>
        </div>

        <div class="reviews-grid">
            <div v-for="item in reviewsData" :key="item.movie_id" class="review-movie-card">

                <div class="poster-wrapper">
                    <img :src="item.movie_poster" :alt="item.movie_title" class="movie-banner" />
                    <div class="poster-overlay">
                        <button class="play-btn" @click="openTrailer(item.trailer_url)">
                            <span class="play-icon">▶</span>
                        </button>
                        <h3 class="movie-title-on-poster">{{ item.movie_title }}</h3>
                    </div>
                    <div class="poster-stats-bar">
                        <div class="stat-badge score-badge">
                            <span class="cinego-rating-icon">⭐</span>
                            <strong class="score-text">{{ item.avg_rating }}</strong>
                        </div>
                        <div class="stat-badge comment-count-badge">
                            <span class="chat-icon">💬</span>
                            <span>{{ item.total_comments }}</span>
                        </div>
                    </div>
                </div>

                <div class="comments-list-section">
                    <div v-for="comment in item.comments" :key="comment.id" class="user-comment-row">
                        <div class="comment-user-meta">
                            <div class="user-avatar">
                                <div class="avatar-placeholder">{{ comment.user_name.charAt(0) }}</div>
                            </div>
                            <div class="user-name-time">
                                <h5 class="u-name">{{ comment.user_name }}</h5>
                                <span class="u-time">• {{ comment.time_ago }}</span>
                            </div>
                        </div>

                        <p class="comment-text-short">
                            {{ truncateText(comment.content, 120) }}
                        </p>

                        <span class="view-more-comment-btn" @click="goToMovieDetail(item.movie_id)">
                            Xem thêm ➔
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="load-more-wrapper text-center">
            <button class="btn-load-more" @click="loadMoreMovies">
                ↓ Xem tiếp nhé !
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const reviewsData = ref([
    {
        movie_id: 1,
        movie_title: 'Ba Trợn',
        movie_poster: 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=600&q=80',
        avg_rating: '9.0', // Đưa về định dạng 1 chữ số thập phân cho chuẩn
        total_comments: 100,
        trailer_url: '#',
        comments: [
            {
                id: 101,
                user_name: 'Lương Nguyễn Gia Hân',
                time_ago: '2 hôm trước',
                content: 'Phim ổn lắm nhưng mà xem xong vẫn thấy nó còn vươn vấn cái gì ý khó tả lắm mấy mom. Đừng để ý ảnh ở dưới nha.'
            },
            {
                id: 102,
                user_name: 'Nguyễn Võ Đức Hồng',
                time_ago: '5 hôm trước',
                content: 'Peak'
            }
        ]
    },
    {
        movie_id: 2,
        movie_title: 'Ngôi Đền Kỳ Quái 5',
        movie_poster: 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?auto=format&fit=crop&w=600&q=80',
        avg_rating: '9.0',
        total_comments: 100,
        trailer_url: '#',
        comments: [
            {
                id: 201,
                user_name: 'Hoàng Triệu Song Thư',
                time_ago: '2 hôm trước',
                content: 'phim hay với hài xỉu, cười điên cười khùng thì thôi nhé luôn á, mà phần này có cảnh hơi buồn 1 xíu, trộm vía vẫn hay như các phần...'
            },
            {
                id: 202,
                user_name: 'Huỳnh Thanh Trọng',
                time_ago: '2 hôm trước',
                content: 'Hay yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy nhaaaaaaaaaaa'
            }
        ]
    },
    {
        movie_id: 3,
        movie_title: 'Star Wars: Mandalorian & Grogu',
        movie_poster: 'https://images.unsplash.com/photo-1579546929518-9e396f3cc809?auto=format&fit=crop&w=600&q=80',
        avg_rating: '9.5',
        total_comments: 39,
        trailer_url: '#',
        comments: [
            {
                id: 301,
                user_name: 'Bùi Thị Ngọc Anh',
                time_ago: 'Hôm qua',
                content: 'Phim quá tuyệt vời không uổng công mình chờ phim 2 năm'
            },
            {
                id: 302,
                user_name: 'Đỗ Thành Long',
                time_ago: '03/06/2026',
                content: 'Hay'
            }
        ]
    }
]);

const truncateText = (text, length) => {
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
};

const goToMovieDetail = (movieId) => {
    router.push(`/movie/${movieId}`);
};

const openTrailer = (url) => {
    console.log('Mở Trailer:', url);
};

const loadMoreMovies = () => {
    console.log('Tải thêm đánh giá...');
};
</script>

<style scoped>
@import '../../assets/css/pages/review-movies.css';
</style>