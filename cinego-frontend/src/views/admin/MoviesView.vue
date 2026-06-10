<template>
  <div class="admin-movies-view-container glass-panel">
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
            <th class="col-duration">Thời Lượng</th>
            <th class="col-rating">Phân Loại</th>
            <th class="col-genres">Thể Loại</th>
            <th class="col-status">Trạng Thái</th>
            <th class="col-actions">Hành Động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="movie in movies" :key="movie.id" class="table-row">
            <td class="cell-id">#{{ movie.id }}</td>
            <td class="cell-poster">
              <img 
                :src="getPosterUrl(movie.poster_url)" 
                class="poster-thumbnail"
                @error="handleImageError"
              />
            </td>
            <td class="cell-title">{{ movie.title }}</td>
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
              <span 
                class="status-pill-cine" 
                :class="{ 
                  active: movie.status === 'showing' || movie.status === 'Đang chiếu', 
                  upcoming: movie.status === 'upcoming' || movie.status === 'Sắp chiếu',
                  ended: movie.status === 'ended' || movie.status === 'Đã kết thúc'
                }"
              >
                {{ formatStatus(movie.status) }}
              </span>
            </td>
            <td class="cell-actions">
              <div class="action-buttons-group">
                <button @click="openEditModal(movie)" class="btn-action edit">✏️ Sửa</button>
                <button @click="deleteMovie(movie.id)" class="btn-action delete">🗑️ Xóa</button>
              </div>
            </td>
          </tr>
          
          <tr v-if="movies.length === 0">
            <td colspan="8" class="empty-state">
              📭 Chưa có bộ phim nào được lưu. Hãy bấm nút "Thêm Phim Mới" để bắt đầu!
            </td>
          </tr>
        </tbody>
      </table>
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
        
        <form @submit.prevent="saveMovie" class="movie-form">
          
          <div class="form-group-large">
            <label class="form-label-large">Tên Phim *</label>
            <input 
              v-model="form.title" 
              type="text" 
              required 
              placeholder="Nhập tên phim đầy đủ..." 
              class="form-input-large" 
              @input="handleTitleInput" 
            />
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Slug (Đường dẫn tĩnh) *</label>
            <input 
              v-model="form.slug" 
              type="text" 
              required 
              placeholder="duong-dan-tinh-phim" 
              class="form-input-large input-readonly" 
            />
          </div>

          <div class="form-row-double">
            <div class="form-group-large">
              <label class="form-label-large">Thời Lượng (Phút) *</label>
              <input 
                v-model.number="form.duration" 
                type="number" 
                required 
                min="1" 
                class="form-input-large" 
              />
            </div>

            <div class="form-group-large">
              <label class="form-label-large">Ngày Khởi Chiếu *</label>
              <input 
                v-model="form.release_date" 
                type="date" 
                required 
                class="form-input-large" 
              />
            </div>
          </div>

          <div class="form-row-double">
            <div class="form-group-large">
              <label class="form-label-large">Giới Hạn Độ Tuổi *</label>
              <select v-model="form.rating" required class="form-input-large select-cine">
                <option value="G">G - Mọi đối tượng</option>
                <option value="PG-13">PG-13 - Trên 13 tuổi</option>
                <option value="T16">T16 - Trên 16 tuổi</option>
                <option value="T18">T18 - Trên 18 tuổi</option>
              </select>
            </div>

            <div class="form-group-large">
              <label class="form-label-large">Trạng Thái Chiếu *</label>
              <select v-model="form.status" required class="form-input-large select-cine">
                <option value="Đang chiếu">Đang chiếu</option>
                <option value="Sắp chiếu">Sắp chiếu</option>
                <option value="Đã kết thúc">Đã kết thúc</option>
              </select>
            </div>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Thể Loại Phim * (Chọn ít nhất 1 thể loại)</label>
            <div class="genres-checkboxes-cine">
              <label v-for="genre in genres" :key="genre.id" class="checkbox-label-large">
                <input 
                  type="checkbox" 
                  :value="genre.id" 
                  v-model="form.genre_ids"
                  class="checkbox-box-cine"
                />
                <span class="checkbox-text">{{ genre.name }}</span>
              </label>
            </div>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Chọn Ảnh Poster Phim *</label>
            <div class="file-upload-section">
              <input 
                type="file" 
                accept="image/*" 
                @change="handleFileChange" 
                :required="!isEdit" 
                class="form-input-large file-input" 
              />
              <div v-if="imagePreview" class="image-preview-box">
                <p class="preview-title">Ảnh được chọn:</p>
                <img :src="getPosterUrl(imagePreview)" class="preview-image-cine" />
              </div>
            </div>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Trailer URL (YouTube Link)</label>
            <input 
              v-model="form.trailer_url" 
              type="text" 
              placeholder="Ví dụ: https://www.youtube.com/watch?v=..." 
              class="form-input-large" 
            />
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Mô Tả Tóm Tắt Nội Dung</label>
            <textarea 
              v-model="form.description" 
              rows="4" 
              placeholder="Nhập giới thiệu tóm tắt phim..." 
              class="form-input-large textarea-cine"
            ></textarea>
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
import { ref, onMounted } from 'vue';
import api from '../../api/axios';

const movies = ref([]);
const genres = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEdit = ref(false);
const submitting = ref(false);
const currentMovieId = ref(null);

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
  currentMovieId.value = null;
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

const fetchMovies = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/movies');
    movies.value = response.data.data || response.data;
  } catch (err) {
    console.error('Lỗi tải danh sách phim:', err);
  } finally {
    loading.value = false;
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

const saveMovie = async () => {
  if (!form.value.genre_ids || form.value.genre_ids.length === 0) {
    alert('Vui lòng chọn ít nhất một thể loại cho phim!');
    return;
  }
  
  submitting.value = true;
  try {
    const formData = new FormData();
    formData.append('title', form.value.title);
    formData.append('slug', form.value.slug);
    formData.append('description', form.value.description);
    formData.append('duration', form.value.duration);
    formData.append('release_date', form.value.release_date);
    formData.append('trailer_url', form.value.trailer_url);
    formData.append('rating', form.value.rating); 
    formData.append('status', form.value.status);
    
    form.value.genre_ids.forEach(id => {
      formData.append('genre_ids[]', id);
    });

    if (selectedFile.value) {
      formData.append('poster', selectedFile.value);
    }

    if (isEdit.value) {
      formData.append('_method', 'PUT');
    }

    const url = isEdit.value ? `/admin/movies/${currentMovieId.value}` : '/admin/movies';
    
    await api.post(url, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    alert(isEdit.value ? '✅ Cập nhật thành công!' : '✅ Thêm phim thành công!');
    showModal.value = false;
    await fetchMovies();
  } catch (err) {
    console.error("Lỗi lưu phim:", err);
    const errorMsg = err.response?.data?.errors 
      ? Object.values(err.response.data.errors).flat().join('\n') 
      : (err.response?.data?.message || err.message);
    alert('Có lỗi xảy ra:\n' + errorMsg);
  } finally {
    submitting.value = false;
  }
};

const deleteMovie = async (id) => {
  if (!confirm('⚠️ Bạn có chắc chắn muốn xóa bộ phim này? Mọi lịch chiếu và vé đặt sẽ bị xóa.')) return;
  try {
    await api.delete(`/admin/movies/${id}`);
    alert('🗑️ Xóa phim thành công khỏi hệ thống!');
    await fetchMovies();
  } catch (err) {
    console.error('Lỗi xóa phim:', err);
    alert(err.response?.data?.message || 'Không thể xóa phim này do ràng buộc dữ liệu.');
  }
};

onMounted(async () => {
  await fetchMovies();
  await fetchGenres();
});
</script>

<style scoped>
.admin-movies-view-container {
  padding: 30px;
  background-color: #ffffff;
  color: #1e293b;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

/* Beautiful CineGo themed Primary button */
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
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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

.col-id { width: 80px; text-align: center; }
.col-poster { width: 90px; text-align: center; }
.col-title { min-width: 200px; }
.col-duration { width: 130px; }
.col-rating { width: 120px; text-align: center; }
.col-genres { min-width: 150px; }
.col-status { width: 150px; text-align: center; }
.col-actions { width: 160px; text-align: center; }

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
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
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
.rating-g { background-color: #10b981; }
.rating-t13 { background-color: #3b82f6; }
.rating-t16 { background-color: #f59e0b; }
.rating-t18 { background-color: #ef4444; }

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
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 700;
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

.cell-actions {
  text-align: center;
}
.action-buttons-group {
  display: flex;
  justify-content: center;
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
</style>