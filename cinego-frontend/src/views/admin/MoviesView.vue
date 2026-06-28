<template>
  <div class="admin-movies-view-container">
    <div class="glass-panel list-card">
      <div class="header-row">
        <h2 class="title-cine">🎬 Quản Lý Danh Sách Phim</h2>
        <button @click="openCreateModal" class="btn-primary-cine">+ Thêm Phim Mới</button>
      </div>

      <!-- Spinner loading dữ liệu -->
      <div v-if="loading" class="loading-state">
        <div class="spinner-cine"></div>
        <p>Đang quét và tải danh sách phim từ database...</p>
      </div>

      <div v-else class="movies-table-wrapper">
        <table class="movies-table">
          <thead>
            <tr>
              <th class="col-id">ID</th>
              <th class="col-poster">Poster</th>
              <th class="col-title">Tên Phim</th>
              <th class="col-slug">Slug</th>
              <th class="col-duration">Thời Lượng</th>
              <th class="col-rating">Phân Loại</th>
              <th class="col-genres">Thể Loại</th>
              <th class="col-status">Trạng Thái</th>
              <th class="col-url">URL Link</th>
              <th class="col-actions">Hành Động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="movie in paginatedMovies" :key="movie.id" class="table-row">
              <td class="cell-id">#{{ movie.id }}</td>
              <td class="cell-poster">
                <img :src="getPosterUrl(movie.poster_url)" class="poster-thumbnail" @error="handleImageError" />
              </td>
              <td class="cell-title"><div class="cell-scrollable">{{ movie.title }}</div></td>
              <td class="cell-slug"><div class="cell-scrollable">{{ movie.slug }}</div></td>

              <td class="cell-duration">{{ movie.duration }} phút</td>
              <td class="cell-rating">
                <span class="rating-pill-cine" :class="getRatingClass(movie.rating)">
                  {{ movie.rating || 'G' }}
                </span>
              </td>
              <td class="cell-genres">
                <div class="genres-list">
                  <span v-for="g in movie.genres" :key="g.id" class="genre-tag">
                    {{ g.name }}
                  </span>
                </div>
              </td>
              <td class="cell-status">
                <span class="status-pill-cine" :class="{
                  active: movie.status === 'showing' || movie.status === 'Đang chiếu',
                  upcoming: movie.status === 'upcoming' || movie.status === 'Sắp chiếu',
                  ended: movie.status === 'ended' || movie.status === 'Đã kết thúc'
                }">
                  {{ formatStatus(movie.status) }}
                </span>
              </td>
              <td class="cell-url"><div class="cell-scrollable">{{ movie.trailer_url }}</div></td>
              <td class="cell-actions">
                <div class="action-buttons-group">
                  <button @click="openEditModal(movie)" class="btn-action edit">✏️ Sửa</button>
                  <button @click="deleteMovie(movie.id)" class="btn-action delete">🗑️ Xóa</button>
                </div>
              </td>
            </tr>

            <tr v-if="paginatedMovies.length === 0">
              <td colspan="10" class="empty-state">
                📭 Chưa có bộ phim nào được lưu. Hãy bấm nút "Thêm Phim Mới" để bắt đầu!
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Phân trang -->
      <div v-if="totalPages > 1" class="pagination-cine">
        <button @click="currentPage--" :disabled="currentPage === 1" class="btn-page">Trước</button>
        <span class="page-info">Trang {{ currentPage }} / {{ totalPages }}</span>
        <button @click="currentPage++" :disabled="currentPage === totalPages" class="btn-page">Sau</button>
      </div>
    </div>

    <!-- MODAL THÊM / SỬA PHIM (TÔNG MÀU TRẮNG ĐỎ CHỦ ĐẠO) -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content-cine">
        <div class="modal-header">
          <h3 class="modal-title-cine">
            {{ isEdit ? '📝 Cập Nhật Thông Tin Phim' : '✨ Thêm Phim Chiếu Rạp Mới' }}
          </h3>
          <button @click="closeModal" class="btn-close-modal">✕</button>
        </div>

        <form @submit.prevent="saveMovie" class="movie-form" novalidate>
          <div class="form-group-large">
            <label class="form-label-large">Tên Phim *</label>
            <input v-model="form.title" type="text" placeholder="Nhập tên phim đầy đủ..." class="form-input-large"
              @input="handleTitleInput" />
            <span v-if="errors.title" class="error-msg">{{ errors.title[0] }}</span>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Slug (Đường dẫn tĩnh) *</label>
            <input v-model="form.slug" type="text" placeholder="duong-dan-tinh-phim"
              class="form-input-large input-readonly" />
            <span v-if="errors.slug" class="error-msg">{{ errors.slug[0] }}</span>
          </div>

          <div class="form-row-double">
            <div class="form-group-large">
              <label class="form-label-large">Thời Lượng (Phút) *</label>
              <input v-model.number="form.duration" type="number" min="1" class="form-input-large" />
              <span v-if="errors.duration" class="error-msg">{{ errors.duration[0] }}</span>
            </div>

            <div class="form-group-large">
              <label class="form-label-large">Ngày Khởi Chiếu *</label>
              <input v-model="form.release_date" type="date" class="form-input-large" />
              <span v-if="errors.release_date" class="error-msg">{{ errors.release_date[0] }}</span>
            </div>
          </div>

          <div class="form-row-double">
            <div class="form-group-large">
              <label class="form-label-large">Giới Hạn Độ Tuổi *</label>
              <select v-model="form.rating" class="form-input-large select-cine">
                <option value="G">G - Mọi đối tượng</option>
                <option value="PG-13">PG-13 - Trên 13 tuổi</option>
                <option value="T16">T16 - Trên 16 tuổi</option>
                <option value="T18">T18 - Trên 18 tuổi</option>
              </select>
              <span v-if="errors.rating" class="error-msg">{{ errors.rating[0] }}</span>
            </div>

            <div class="form-group-large">
              <label class="form-label-large">Trạng Thái Chiếu *</label>
              <select v-model="form.status" class="form-input-large select-cine">
                <option value="Đang chiếu">Đang chiếu</option>
                <option value="Sắp chiếu">Sắp chiếu</option>
                <option value="Đã kết thúc">Đã kết thúc</option>
              </select>
              <span v-if="errors.status" class="error-msg">{{ errors.status[0] }}</span>
            </div>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Thể Loại Phim * (Chọn ít nhất 1 thể loại)</label>
            <div class="genres-checkboxes-cine">
              <label v-for="genre in genres" :key="genre.id" class="checkbox-label-large">
                <input type="checkbox" :value="genre.id" v-model="form.genre_ids" class="checkbox-box-cine" />
                <span class="checkbox-text">{{ genre.name }}</span>
              </label>
            </div>

            <span v-if="errors?.genre_ids" class="error-msg">
              {{ errors.genre_ids[0] }}
            </span>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Chọn Ảnh Poster Phim *</label>
            <div class="file-upload-section">
              <input type="file" accept="image/*" @change="handleFileChange" class="form-input-large file-input" />
              <span v-if="errors.poster" class="error-msg">{{ errors.poster[0] }}</span>
              <div v-if="imagePreview" class="image-preview-box">
                <p class="preview-title">Ảnh được chọn:</p>
                <img :src="getPosterUrl(imagePreview)" class="preview-image-cine" />
              </div>
            </div>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Trailer URL (YouTube Link)</label>
            <input v-model="form.trailer_url" type="text" placeholder="Ví dụ: https://www.youtube.com/watch?v=..."
              class="form-input-large" />
            <span v-if="errors.trailer_url" class="error-msg">{{ errors.trailer_url[0] }}</span>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Mô Tả Tóm Tắt Nội Dung</label>
            <textarea v-model="form.description" rows="4" placeholder="Nhập giới thiệu tóm tắt phim..."
              class="form-input-large textarea-cine"></textarea>
            <span v-if="errors.description" class="error-msg">{{ errors.description[0] }}</span>
          </div>

          <div class="modal-footer-cine">
            <button type="button" @click="closeModal" class="btn-secondary-cine">Hủy bỏ</button>
            <button type="submit" class="btn-primary-cine" :disabled="submitting">
              {{ submitting ? 'Đang lưu phim...' : 'Lưu lại' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios';
import { toast, confirmDialog } from '../../utils/alert';

const movies = ref([]);
const genres = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEdit = ref(false);
const submitting = ref(false);
const currentMovieId = ref(null);

// Pagination
const currentPage = ref(1);
const itemsPerPage = 8;

const totalPages = computed(() => Math.ceil(movies.value.length / itemsPerPage));
const paginatedMovies = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  return movies.value.slice(start, end);
});

const selectedFile = ref(null);
const imagePreview = ref('');

const form = ref({
  title: '',
  slug: '',
  description: '',
  duration: 120,
  release_date: '',
  trailer_url: '',
  rating: 'G',
  status: 'Đang chiếu',
  genre_ids: []
});

// BỘ LỌC TỰ ĐỘNG DỊCH URL CHUẨN XÁC: Đảm bảo ảnh poster được lấy đúng Host API port 8000
const getPosterUrl = (url) => {
  if (!url) return 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=100&q=80';

  // Tránh dịch các URL preview dạng blob tạm thời
  if (url.startsWith('blob:')) return url;

  // Dịch port localhost nếu database bị lệch URL do .env cũ
  if (url.startsWith('http://localhost/storage/')) {
    return url.replace('http://localhost/storage/', 'http://127.0.0.1:8000/storage/');
  }
  return url;
};

const handleImageError = (event) => {
  event.target.src = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=100&q=80';
};

const truncateDescription = (text, length = 50) => {
  if (!text) return '';
  return text.length > length ? text.substring(0, length) + '...' : text;
};

const formatStatus = (status) => {
  if (status === 'showing' || status === 'Đang chiếu') return 'Đang chiếu';
  if (status === 'upcoming' || status === 'Sắp chiếu') return 'Sắp chiếu';
  return 'Đã kết thúc';
};

const getRatingClass = (rating) => {
  if (!rating) return 'rating-g';
  const r = rating.toUpperCase();
  if (r.includes('18')) return 'rating-t18';
  if (r.includes('16')) return 'rating-t16';
  if (r.includes('13') || r === 'PG-13') return 'rating-t13';
  return 'rating-g';
};

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    selectedFile.value = file;
    imagePreview.value = URL.createObjectURL(file);
  }
};


const openCreateModal = () => {
  isEdit.value = false;
  errors.value = {};
  selectedFile.value = null;
  imagePreview.value = '';
  form.value = {
    title: '',
    slug: '',
    description: '',
    duration: 120,
    release_date: new Date().toISOString().split('T')[0],
    trailer_url: '',
    rating: 'G',
    status: 'Đang chiếu',
    genre_ids: []
  };
  showModal.value = true;
};

const openEditModal = (movie) => {
  isEdit.value = true;
  errors.value = {};
  currentMovieId.value = movie.id;
  selectedFile.value = null;
  imagePreview.value = movie.poster_url || '';

  let formattedDate = '';
  if (movie.release_date) {
    const d = new Date(movie.release_date);
    formattedDate = d.toISOString().split('T')[0];
  }

  form.value = {
    title: movie.title,
    slug: movie.slug,
    description: movie.description || '',
    duration: movie.duration,
    release_date: formattedDate,
    trailer_url: movie.trailer_url || '',
    rating: movie.rating || 'G',
    status: movie.status || 'Đang chiếu',
    genre_ids: movie.genres ? movie.genres.map(g => g.id) : []
  };
  showModal.value = true;
};

const clearError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field];
  }
};

const closeModal = () => {
  showModal.value = false;
};

const handleTitleInput = () => {
  if (isEdit.value) return;
  form.value.slug = form.value.title
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[đĐ]/g, 'd')
    .replace(/([^a-z0-9\s-]|_)+/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-');
};

const fetchMovies = async (showLoading = true) => {
  if (showLoading) loading.value = true;
  try {
    const response = await api.get('/admin/movies');
    movies.value = response.data.data || response.data;
  } catch (err) {
    console.error('Lỗi tải danh sách phim:', err);
  } finally {
    if (showLoading) loading.value = false;
  }
};

const fetchGenres = async () => {
  try {
    const response = await api.get('/admin/genres');
    genres.value = response.data.data || response.data;
  } catch (err) {
    console.error('Lỗi tải danh sách thể loại:', err);
  }
};

const errors = ref({});

const validateForm = () => {
  errors.value = {};
  let isValid = true;

  if (!form.value.title || form.value.title.trim() === '') {
    errors.value.title = ['Vui lòng nhập tên phim.'];
    isValid = false;
  }

  if (!form.value.slug || form.value.slug.trim() === '') {
    errors.value.slug = ['Đường dẫn tĩnh không hợp lệ.'];
    isValid = false;
  }

  if (!form.value.duration || form.value.duration <= 0) {
    errors.value.duration = ['Thời lượng phim phải lớn hơn 0 phút.'];
    isValid = false;
  }

  if (!form.value.release_date) {
    errors.value.release_date = ['Vui lòng chọn ngày khởi chiếu.'];
    isValid = false;
  }

  if (!form.value.genre_ids || form.value.genre_ids.length === 0) {
    errors.value.genre_ids = ['Vui lòng chọn ít nhất 1 thể loại.'];
    isValid = false;
  }

  if (!isEdit.value && !selectedFile.value) {
    errors.value.poster = ['Vui lòng chọn ảnh poster cho phim mới.'];
    isValid = false;
  }

  return isValid;
};

const saveMovie = async () => {
  if (!validateForm()) return;
  
  submitting.value = true;

  try {
    const formData = new FormData();
    formData.append('title', form.value.title || '');
    formData.append('description', form.value.description || '');
    formData.append('duration', form.value.duration || '');
    formData.append('release_date', form.value.release_date || '');
    formData.append('status', form.value.status || '');
    formData.append('rating', form.value.rating || '');
    formData.append('trailer_url', form.value.trailer_url || '');

    if (form.value.genre_ids) {
      form.value.genre_ids.forEach(id => formData.append('genre_ids[]', id));
    }

    if (selectedFile.value) {
      formData.append('poster', selectedFile.value);
    }

    if (isEdit.value) formData.append('_method', 'PUT');

    const url = isEdit.value ? `/admin/movies/${currentMovieId.value}` : '/admin/movies';

    await api.post(url, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    toast("Lưu thành công!");
    showModal.value = false;
    await fetchMovies(false);
  } catch (err) {
    console.error("Lỗi khi lưu phim:", err);
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {};
      toast("Lỗi xác thực dữ liệu", "error");
    } else {
      toast("Không thể lưu phim. Kiểm tra lại!", "error");
    }
  } finally {
    submitting.value = false;
  }
};

const deleteMovie = async (id) => {
  const isConfirm = await confirmDialog(
    "Xóa bộ phim này?", 
    "Toàn bộ dữ liệu suất chiếu liên quan cũng có thể bị ảnh hưởng!"
  );
  if (!isConfirm) return;

  const originalMovies = [...movies.value];
  movies.value = movies.value.filter(m => m.id !== id);

  try {
    await api.delete(`/admin/movies/${id}`);

    toast("Xóa phim thành công!");

    await fetchMovies();
  } catch (err) {
    console.error("Lỗi khi xóa:", err);
    toast("Có lỗi xảy ra khi xóa phim. Vui lòng thử lại!", "error");
  }
};

onMounted(async () => {
  await fetchMovies();
  await fetchGenres();
});
</script>

<style scoped>
.admin-movies-view-container {
  background-color: #ffffff;
  color: #1e293b;
  width: 100%;
  max-width: 100vw;
  box-sizing: border-box;
  padding: 20px;
  overflow-x: hidden;
}

.list-card {
  width: 100%;
  padding: 20px;
  box-sizing: border-box;
  border-radius: 15px;
  overflow-x: hidden;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 25px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 15px;
}

.title-cine {
  font-size: 22px;
  font-weight: 800;
  color: #9b000e;
  text-transform: uppercase;
}

.btn-primary-cine {
  background: linear-gradient(135deg, #e50914 0%, #9b000e 100%);
  color: #ffffff;
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.25);
  transition: all 0.2s ease;
  white-space: nowrap;
  flex-shrink: 0;
}

.btn-primary-cine:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(229, 9, 20, 0.35);
}

.btn-secondary-cine {
  background-color: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 12px 22px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary-cine:hover {
  background-color: #f1f5f9;
  border-color: #94a3b8;
  color: #1e293b;
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 0;
  gap: 15px;
}

.spinner-cine {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #e50914;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }

  100% {
    transform: rotate(360deg);
  }
}

.loading-state p {
  font-size: 15px;
  color: #64748b;
  font-weight: 600;
}

/* Movies List Table */
.movies-table-wrapper {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.pagination-cine {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border-top: 1px solid #e2e8f0;
}
.btn-page {
  padding: 8px 16px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-page:not(:disabled):hover {
  border-color: #e50914;
  color: #e50914;
}
.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.page-info {
  font-size: 14px;
  font-weight: 600;
  color: #475569;
}

.movies-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.movies-table th {
  padding: 16px;
  background-color: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  color: #475569;
  font-size: 15px;
  font-weight: 800;
}

.movies-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 15px;
}

.table-row:hover {
  background-color: #fffafb;
}

.col-id {
  width: 80px;
  text-align: center;
}

.col-poster {
  width: 90px;
  text-align: center;
}

.col-title {
  width: 200px;
}

.col-duration {
  width: 130px;
}

.col-rating {
  width: 120px;
  text-align: center;
}

.col-genres {
  min-width: 150px;
}

.col-status {
  width: 170px;
  text-align: center;
}

.col-actions {
  width: 160px;
  text-align: center;
}

.cell-id {
  font-weight: 800;
  color: #e50914;
  text-align: center;
}

.poster-thumbnail {
  width: 50px;
  height: 70px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.cell-title {
  font-weight: 700;
  color: #1e293b;
}

.cell-duration {
  color: #475569;
}

.cell-rating {
  text-align: center;
}

/* Rating Badge Classes */
.rating-pill-cine {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 800;
  color: #ffffff;
}

.rating-g {
  background-color: #10b981;
}

.rating-t13 {
  background-color: #3b82f6;
}

.rating-t16 {
  background-color: #f59e0b;
}

.rating-t18 {
  background-color: #ef4444;
}

.genres-list {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.genre-tag {
  background-color: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #475569;
  font-size: 13px;
  padding: 4px 10px;
  border-radius: 6px;
}

.cell-status {
  text-align: center;
}

.status-pill-cine {
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 800;
  display: inline-block;
  white-space: nowrap;
}

.status-pill-cine.active {
  background-color: #d1fae5;
  color: #065f46;
}

.status-pill-cine.upcoming {
  background-color: #fef3c7;
  color: #92400e;
}

.status-pill-cine.ended {
  background-color: #f1f5f9;
  color: #475569;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 160px; /* Có thể dùng rem/em */
  display: block; /* Sửa từ inline-block thành block để không phá table */
}

.cell-scrollable {
  max-width: 200px; /* Giới hạn chiều rộng để ô không phình to */
  overflow-x: auto;
  white-space: nowrap;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 4px; /* Tránh thanh cuộn đè lên chữ */
}

/* Tùy chỉnh thanh cuộn siêu mỏng và thanh lịch cho ô */
.cell-scrollable::-webkit-scrollbar {
  height: 4px;
}
.cell-scrollable::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
.cell-scrollable::-webkit-scrollbar-track {
  background: transparent;
}

.cell-actions {
  text-align: center;
}

.action-buttons-group {
  display: flex;
  justify-content: center;
  flex-wrap: nowrap; /* Cấm rớt dòng */
  gap: 8px;
}

.btn-action {
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  padding: 6px 14px;
  font-size: 14px;
  font-weight: 700;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap; /* Chống bóp chữ Sửa, Xóa */
}

.btn-action.edit {
  color: #d97706;
  border-color: #fde68a;
}

.btn-action.edit:hover {
  background-color: #fef3c7;
  border-color: #d97706;
}

.btn-action.delete {
  color: #dc2626;
  border-color: #fecaca;
}

.btn-action.delete:hover {
  background-color: #fee2e2;
  border-color: #dc2626;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
  font-size: 15px;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(8px);
  z-index: 999;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 40px 20px;
  overflow-y: auto;
}

.modal-content-cine {
  width: 100%;
  max-width: 650px;
  padding: 35px;
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #cbd5e1;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  color: #1e293b;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  border-bottom: 2px solid #fee2e2;
  padding-bottom: 15px;
}

.modal-title-cine {
  font-size: 22px;
  font-weight: 800;
  color: #9b000e;
  text-transform: uppercase;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 22px;
  color: #94a3b8;
  cursor: pointer;
  transition: color 0.2s ease;
}

.btn-close-modal:hover {
  color: #e50914;
}

/* Movie Form inputs & layout */
.movie-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group-large {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label-large {
  font-size: 15px;
  font-weight: 700;
  color: #334155;
}

.form-input-large {
  width: 100%;
  border: 1px solid #cbd5e1;
  padding: 14px 20px;
  border-radius: 10px;
  outline: none;
  font-size: 16px;
  background-color: #f8fafc;
  color: #1e293b;
  transition: all 0.2s ease-in-out;
}

.form-input-large:focus {
  border-color: #e50914;
  box-shadow: 0 0 0 4px rgba(229, 9, 20, 0.1);
  background-color: #ffffff;
}

.input-readonly {
  background-color: #f1f5f9;
  color: #64748b;
  border-color: #e2e8f0;
  font-family: monospace;
}

.form-row-double {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 576px) {
  .form-row-double {
    grid-template-columns: 1fr;
  }
}

.select-cine {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 16px center;
  background-size: 18px;
  appearance: none;
  padding-right: 40px;
  cursor: pointer;
}

.genres-checkboxes-cine {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 12px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 10px;
  border: 1px solid #cbd5e1;
  max-height: 180px;
  overflow-y: auto;
}

.checkbox-label-large {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  color: #334155;
  cursor: pointer;
}

.checkbox-box-cine {
  width: 18px;
  height: 18px;
  accent-color: #e50914;
  cursor: pointer;
}

.checkbox-text {
  font-weight: 600;
}

.file-upload-section {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.file-input {
  padding: 10px;
  cursor: pointer;
}

.image-preview-box {
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: #f8fafc;
  padding: 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  width: fit-content;
}

.preview-title {
  font-size: 13px;
  color: #64748b;
  font-weight: 700;
}

.preview-image-cine {
  width: 100px;
  height: 140px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
}

.textarea-cine {
  resize: vertical;
  min-height: 100px;
}

.modal-footer-cine {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 15px;
  border-top: 2px solid #fee2e2;
  padding-top: 20px;
}

.error-msg {
  color: #dc2626;
  font-size: 0.85rem;
  margin-top: 5px;
  display: block;
  font-weight: 600;
}
</style>
