<template>
  <div class="admin-movies-view glass-panel">
    <div class="header-row">
      <h2 class="gradient-text-accent">Quản Lý Danh Sách Phim</h2>
      <button @click="openCreateModal" class="btn-add">+ Thêm Phim Mới</button>
    </div>
    
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Đang tải danh sách phim từ database MySQL...</p>
    </div>

    <div v-else class="movies-table-wrapper">
      <table class="movies-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Poster</th>
            <th>Tên Phim</th>
            <th>Thời Lượng</th>
            <th>Phân Loại</th>
            <th>Thể Loại</th>
            <th>Trạng Thái</th>
            <th>Hành Động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="movie in movies" :key="movie.id">
            <td>#{{ movie.id }}</td>
            <td>
              <img 
                :src="movie.poster_url || 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=100&q=80'" 
                class="poster-thumbnail" 
              />
            </td>
            <td class="font-bold">{{ movie.title }}</td>
            <td>{{ movie.duration }} phút</td>
            <td><span class="rating-pill">{{ movie.rating || movie.rating || 'G' }}</span></td>
            <td>
              <div class="genres-list">
                <span v-for="g in movie.genres" :key="g.id" class="genre-tag">
                  {{ g.name }}
                </span>
              </div>
            </td>
            <td>
              <span 
                class="status-pill" 
                :class="{ 
                  active: movie.status === 'showing' || movie.status === 'Đang chiếu', 
                  upcoming: movie.status === 'upcoming' || movie.status === 'Sắp chiếu',
                  ended: movie.status === 'ended' || movie.status === 'Đã kết thúc'
                }"
              >
                {{ formatStatus(movie.status) }}
              </span>
            </td>
            <td>
              <button @click="openEditModal(movie)" class="action-btn edit-btn">Sửa</button>
              <button @click="deleteMovie(movie.id)" class="action-btn delete-btn">Xóa</button>
            </td>
          </tr>
          <tr v-if="movies.length === 0">
            <td colspan="8" style="text-align: center; padding: 30px; color: var(--text-muted);">
              📭 Chưa có bộ phim nào được in ra. Hãy bấm "Thêm Phim Mới"!
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showModal" class="modal-backdrop">
      <div class="modal-content glass-panel">
        <h3 class="modal-title glow-text-pink">{{ isEdit ? 'Cập Nhật Phim' : 'Thêm Phim Mới' }}</h3>
        
        <form @submit.prevent="saveMovie" class="movie-form">
          <div class="form-group">
            <label>Tên Phim *</label>
            <input v-model="form.title" type="text" required placeholder="Nhập tên phim..." class="form-input" @input="handleTitleInput" />
          </div>

          <div class="form-group">
            <label>Slug (Đường dẫn tĩnh) *</label>
            <input v-model="form.slug" type="text" required placeholder="example-slug-phim" class="form-input" />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Thời Lượng (Phút) *</label>
              <input v-model.number="form.duration" type="number" required min="1" class="form-input" />
            </div>

            <div class="form-group">
              <label>Ngày Phát Hành *</label>
              <input v-model="form.release_date" type="date" required class="form-input" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phân Loại Độ Tuổi *</label>
              <select v-model="form.age_rating" required class="form-input">
                <option value="G">G - Mọi đối tượng</option>
                <option value="PG-13">PG-13 - Trên 13 tuổi</option>
                <option value="T16">T16 - Trên 16 tuổi</option>
                <option value="T18">T18 - Trên 18 tuổi</option>
              </select>
            </div>

            <div class="form-group">
              <label>Trạng Thái Chiếu *</label>
              <select v-model="form.status" required class="form-input">
                <option value="Đang chiếu">Đang chiếu</option>
                <option value="Sắp chiếu">Sắp chiếu</option>
                <option value="Đã kết thúc">Đã kết thúc</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Thể Loại Phim * (Chọn ít nhất 1 mục)</label>
            <div class="genres-checkboxes">
              <label v-for="genre in genres" :key="genre.id" class="checkbox-label">
                <input 
                  type="checkbox" 
                  :value="genre.id" 
                  v-model="form.genre_ids"
                />
                {{ genre.name }}
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Chọn Ảnh Poster Phim *</label>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <input 
                type="file" 
                accept="image/*" 
                @change="handleFileChange" 
                :required="!isEdit" 
                class="form-input" 
                style="padding: 8px;"
              />
              <div v-if="imagePreview" style="margin-top: 5px;">
                <p style="font-size: 11px; color: var(--text-secondary); margin-bottom: 4px;">Xem trước hình ảnh:</p>
                <img :src="imagePreview" style="width: 80px; height: 110px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-glass);" />
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>Trailer URL</label>
            <input v-model="form.trailer_url" type="text" placeholder="https://youtube.com/embed/..." class="form-input" />
          </div>

          <div class="form-group">
            <label>Mô Tả Phim</label>
            <textarea v-model="form.description" rows="3" placeholder="Nhập tóm tắt nội dung phim..." class="form-input textarea"></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Hủy</button>
            <button type="submit" class="btn-save" :disabled="submitting">
              {{ submitting ? 'Đang lưu...' : 'Lưu Lại' }}
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

// Biến lưu trữ file thật và link preview tạm thời
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

const getAuthConfig = (isMultipart = false) => {
  const token = localStorage.getItem('cinego_token');
  return {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': isMultipart ? 'multipart/form-data' : 'application/json'
    }
  };
};

const formatStatus = (status) => {
  if (status === 'showing' || status === 'Đang chiếu') return 'Đang chiếu';
  if (status === 'upcoming' || status === 'Sắp chiếu') return 'Sắp chiếu';
  return 'Đã kết thúc';
};

// Xử lý khi người dùng chọn file từ máy tính
const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    selectedFile.value = file;
    // Tạo link blob tạm thời hiển thị ảnh lên giao diện luôn
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
  // Điền link ảnh cũ từ DB vào ô xem trước
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
    rating: movie.rating || movie.rating || 'G',
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
    const response = await api.get('admin/movies', getAuthConfig(false));
    movies.value = response.data.data || response.data;
  } catch (err) {
    console.error('Fetch movies error:', err);
  } finally {
    loading.value = false;
  }
};

const fetchGenres = async () => {
  try {
    const response = await api.get('/admin/genres', getAuthConfig(false));
    genres.value = response.data.data || response.data;
  } catch (err) {
    console.error('Fetch genres error:', err);
  }
};

const saveMovie = async () => {
  // Kiểm tra thể loại
  if (!form.value.genre_ids || form.value.genre_ids.length === 0) {
    alert('Vui lòng chọn ít nhất một thể loại!');
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
    
    // Đảm bảo tên trường là 'rating' để khớp với Laravel
    formData.append('rating', form.value.rating); 
    formData.append('status', form.value.status);
    
    form.value.genre_ids.forEach(id => {
      formData.append('genre_ids[]', id);
    });

    if (selectedFile.value) {
      formData.append('poster', selectedFile.value);
    }

    // Xử lý phương thức PUT cho Laravel khi Cập nhật
    if (isEdit.value) {
      formData.append('_method', 'PUT');
    }

    const url = isEdit.value ? `admin/movies/${currentMovieId.value}` : 'admin/movies';
    
    // Gửi request (KHÔNG set Content-Type header thủ công, để axios tự làm)
    const token = localStorage.getItem('cinego_token');
    await api.post(url, formData, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });

    alert(isEdit.value ? '✅ Cập nhật thành công!' : '✅ Thêm phim thành công!');
    showModal.value = false;
    await fetchMovies();
  } catch (err) {
    console.error("Lỗi từ server:", err.response?.data);
    // Hiển thị lỗi chi tiết để bạn biết chính xác tại sao lỗi
    const errorMsg = err.response?.data?.errors 
      ? Object.values(err.response.data.errors).flat().join('\n') 
      : (err.response?.data?.message || err.message);
    alert('Có lỗi xảy ra:\n' + errorMsg);
  } finally {
    submitting.value = false;
  }
};

const deleteMovie = async (id) => {
  if (!confirm('⚠️ Bạn có chắc chắn muốn xóa bộ phim này? Mọi suất chiếu và vé liên quan sẽ bị ảnh hưởng!')) return;
  try {
    await api.delete(`admin/movies/${id}`, getAuthConfig(false));
    alert('🗑️ Xóa phim thành công khỏi hệ thống!');
    await fetchMovies();
  } catch (err) {
    console.error('Delete movie error:', err);
    alert('Không thể xóa phim này! Lỗi liên kết khóa ngoại dữ liệu.');
  }
};

onMounted(async () => {
  await fetchMovies();
  await fetchGenres();
});
</script>

<style scoped>
/* Toàn bộ CSS gốc hoành tráng phong cách Cyberpunk của bạn được giữ nguyên vẹn */
.admin-movies-view {
  padding: 30px;
}
.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}
.btn-add {
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
  border: none;
  padding: 12px 24px;
  font-weight: 700;
  border-radius: var(--radius-sm);
  cursor: pointer;
  box-shadow: var(--shadow-neon-pink);
  transition: var(--transition-bounce);
}
.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 20px rgba(255, 0, 127, 0.4);
}
.movies-table-wrapper {
  overflow-x: auto;
}
.movies-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.movies-table th, .movies-table td {
  padding: 16px;
  border-bottom: 1px solid var(--border-glass);
}
.movies-table th {
  color: var(--text-muted);
  font-size: 13px;
  text-transform: uppercase;
}
.poster-thumbnail {
  width: 44px;
  height: 60px;
  object-fit: cover;
  border-radius: 4px;
}
.font-bold {
  font-weight: 700;
}
.rating-pill {
  background: var(--bg-tertiary);
  color: var(--accent-pink);
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
}
.genres-list {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
  max-width: 250px;
}
.genre-tag {
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border-glass);
  color: var(--text-secondary);
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 3px;
}
.status-pill {
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}
.status-pill.active {
  background: rgba(0, 245, 160, 0.1);
  color: var(--accent-mint);
}
.status-pill.upcoming {
  background: rgba(255, 170, 0, 0.1);
  color: #ffaa00;
}
.status-pill.ended {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-muted);
}
.action-btn {
  background: transparent;
  border: 1px solid var(--border-glass);
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  margin-right: 8px;
  transition: var(--transition-smooth);
}
.edit-btn {
  color: var(--text-secondary);
}
.edit-btn:hover {
  border-color: var(--accent-violet);
  color: white;
  background: rgba(112, 0, 255, 0.1);
}
.delete-btn {
  color: #ff5555;
}
.delete-btn:hover {
  border-color: #ff0000;
  background: rgba(255, 0, 0, 0.1);
  color: white;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(5px);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.modal-content {
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  padding: 30px;
  background: rgba(18, 14, 36, 0.95);
  border-radius: var(--radius-lg);
}
.modal-title {
  font-size: 24px;
  font-weight: 800;
  margin-bottom: 24px;
  text-align: center;
}
.movie-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-secondary);
}
.form-input {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-glass);
  color: white;
  padding: 10px 16px;
  border-radius: var(--radius-sm);
  font-size: 14px;
}
.form-input:focus {
  outline: none;
  border-color: var(--accent-pink);
  box-shadow: var(--shadow-neon-pink);
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.genres-checkboxes {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 10px;
  background: rgba(255,255,255,0.02);
  padding: 12px;
  border-radius: 4px;
  border: 1px solid var(--border-glass);
  max-height: 150px;
  overflow-y: auto;
}
.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-secondary);
  cursor: pointer;
}
.textarea {
  resize: vertical;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 10px;
  border-top: 1px solid var(--border-glass);
  padding-top: 16px;
}
.btn-cancel {
  background: transparent;
  border: 1px solid var(--border-glass);
  color: var(--text-secondary);
  padding: 10px 20px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-weight: 600;
}
.btn-save {
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-weight: 700;
  box-shadow: var(--shadow-neon-pink);
}
.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px;
}
</style>