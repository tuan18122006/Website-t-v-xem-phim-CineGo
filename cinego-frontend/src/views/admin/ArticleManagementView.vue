<template>
  <div class="admin-movies-view-container">
    <div class="glass-panel list-card">
      <div class="header-row">
        <h2 class="title-cine"><Newspaper :size="15" style="vertical-align:-2px" /> QL Trang Top Phim</h2>
        <button @click="openModal()" class="btn-primary-cine">+ Thêm Bài Viết Mới</button>
      </div>

      <div class="movies-table-wrapper">
        <table class="movies-table">
          <thead>
            <tr>
              <th class="col-poster">Bìa</th>
              <th class="col-title">Bài Viết</th>
              <th class="col-status">Trạng Thái</th>
              <th class="col-rating">Số Phim</th>
              <th class="col-rating">Lượt Xem</th>
              <th class="col-actions">Hành Động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="article in articles" :key="article.id" class="table-row">
              <td class="cell-poster">
                <img :src="article.thumbnail_url || 'https://via.placeholder.com/150'" class="poster-thumbnail" />
              </td>
              <td class="cell-title">
                <div class="article-title">{{ article.title }}</div>
                <div class="text-small text-muted">{{ article.excerpt }}</div>
              </td>
              <td class="cell-status">
                <span class="status-pill-cine" :class="article.is_published ? 'active' : 'upcoming'">
                  {{ article.is_published ? 'Đã xuất bản' : 'Bản nháp' }}
                </span>
              </td>
              <td class="cell-rating">{{ article.movies_count }} phim</td>
              <td class="cell-rating">{{ article.views }} view</td>
              <td class="cell-actions">
                <div class="action-buttons-group">
                  <button @click="openModal(article)" class="btn-action edit"><Pencil :size="15" style="vertical-align:-2px" /> Sửa</button>
                  <button @click="deleteArticle(article.id)" class="btn-action delete"><Trash2 :size="15" style="vertical-align:-2px" /> Xóa</button>
                </div>
              </td>
            </tr>
            <tr v-if="articles.length === 0">
              <td colspan="6" class="empty-state"><Inbox :size="15" style="vertical-align:-2px" /> Chưa có bài viết nào. Hãy bấm "Thêm Bài Viết Mới" để bắt đầu!</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content-cine modal-lg">
        <div class="modal-header">
          <h3 class="modal-title-cine"><template v-if="isEditing"><SquarePen :size="20" /> Chỉnh Sửa Bài Viết</template><template v-else><Sparkles :size="20" /> Thêm Bài Viết Mới</template></h3>
          <button @click="closeModal" class="btn-close-modal">✕</button>
        </div>

        <form @submit.prevent="saveArticle" class="movie-form">
          <div class="form-group-large">
            <label class="form-label-large">Tiêu đề *</label>
            <input v-model="form.title" type="text" class="form-input-large" :class="{'is-invalid': errors.title}" placeholder="Nhập tiêu đề bài viết..." />
            <span v-if="errors.title" class="text-danger-cine">{{ errors.title }}</span>
          </div>

          <div class="form-row">
            <div class="form-group flex-center">
              <label class="checkbox-label">
                <input v-model="form.is_published" type="checkbox" class="styled-checkbox" />
                <span>Xuất bản ngay</span>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Mô tả ngắn (Excerpt)</label>
            <textarea v-model="form.excerpt" class="form-textarea" rows="2" placeholder="Tóm tắt ngắn gọn..."></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Nội dung chi tiết (HTML)</label>
            <textarea v-model="form.content" class="form-textarea" rows="4" placeholder="<p>Bài viết chi tiết...</p>"></textarea>
          </div>

          <hr class="divider" />

          <div class="form-group">
            <div class="list-header">
              <h4 class="sub-title">Danh Sách Phim Xếp Hạng</h4>
              <div class="add-movie-row">
                <select v-model="selectedMovieToAdd" class="form-select">
                  <option value="">-- Chọn phim để thêm --</option>
                  <option v-for="movie in allMovies" :key="movie.id" :value="movie.id">{{ movie.title }}</option>
                </select>
                <button type="button" @click="addMovieToList" class="btn-secondary">Thêm</button>
              </div>
            </div>

            <div v-if="form.movies.length === 0" class="empty-list">
              Chưa có phim nào trong danh sách.
            </div>
            <div v-else class="movie-list-container">
              <div v-for="(movieItem, index) in form.movies" :key="index" class="movie-list-item">
                <div class="rank-col">
                  <span class="rank-label">Rank</span>
                  <input v-model.number="movieItem.rank" type="number" class="rank-input" />
                </div>
                <div class="info-col">
                  <div class="movie-name">{{ getMovieTitle(movieItem.id) }}</div>
                  <textarea v-model="movieItem.review_text" class="review-textarea" rows="2" placeholder="Nhận xét riêng cho phim này (tùy chọn)..."></textarea>
                </div>
                <button type="button" @click="removeMovieFromList(index)" class="btn-remove">✕</button>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Hủy</button>
            <button type="submit" class="btn-save">{{ isEditing ? 'Cập Nhật' : 'Lưu' }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from '../../api/axios';
import { toast, confirmDialog } from '../../utils/alert';
import { Newspaper, Pencil, Trash2, Inbox, SquarePen, Sparkles } from 'lucide-vue-next';

const articles = ref([]);
const allMovies = ref([]);

const showModal = ref(false);
const isEditing = ref(false);
const currentId = ref(null);
const selectedMovieToAdd = ref('');
const errors = ref({});
const selectedFile = ref(null);

// Removed handleFileChange

const form = ref({
  title: '',
  excerpt: '',
  content: '',
  thumbnail_url: '',
  is_published: true,
  movies: []
});

const fetchArticles = async () => {
  try {
    const res = await axios.get('/admin/articles');
    articles.value = res.data;
  } catch (error) {
    toast('Lỗi khi tải danh sách bài viết', 'error');
  }
};

const fetchMovies = async () => {
  try {
    const res = await axios.get('/admin/movies');
    allMovies.value = res.data.data || res.data;
  } catch (error) {
    console.error(error);
  }
};

onMounted(() => {
  fetchArticles();
  fetchMovies();
});

const openModal = async (article = null) => {
  errors.value = {};
  selectedFile.value = null;
  if (article) {
    isEditing.value = true;
    currentId.value = article.id;
    try {
      const res = await axios.get(`/admin/articles/${article.id}`);
      const data = res.data;
      form.value = {
        title: data.title,
        excerpt: data.excerpt || '',
        content: data.content || '',
        thumbnail_url: data.thumbnail_url || '',
        is_published: data.is_published,
        movies: data.movies.map(m => ({
          id: m.id,
          rank: m.pivot.rank,
          review_text: m.pivot.review_text
        }))
      };
    } catch (e) {
      toast('Lỗi khi tải chi tiết bài viết', 'error');
      return;
    }
  } else {
    isEditing.value = false;
    currentId.value = null;
    form.value = {
      title: '',
      excerpt: '',
      content: '',
      thumbnail_url: '',
      is_published: true,
      movies: []
    };
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const getMovieTitle = (id) => {
  const m = allMovies.value.find(x => x.id == id);
  return m ? m.title : 'Phim không xác định';
};

const addMovieToList = () => {
  if (!selectedMovieToAdd.value) return;
  
  // Check if already in list
  if (form.value.movies.find(m => m.id == selectedMovieToAdd.value)) {
    toast('Phim này đã có trong danh sách', 'warning');
    return;
  }

  form.value.movies.push({
    id: selectedMovieToAdd.value,
    rank: form.value.movies.length + 1,
    review_text: ''
  });
  
  selectedMovieToAdd.value = '';
};

const removeMovieFromList = (index) => {
  form.value.movies.splice(index, 1);
  // Re-rank
  form.value.movies.forEach((m, i) => {
    m.rank = i + 1;
  });
};

const saveArticle = async () => {
  errors.value = {};
  let isValid = true;
  
  if (!form.value.title || !form.value.title.trim()) {
    errors.value.title = 'Vui lòng nhập tiêu đề bài viết';
    isValid = false;
  }

  // Removed thumbnail validation

  if (!isValid) {
    toast('Vui lòng kiểm tra lại thông tin', 'error');
    return;
  }
  
  try {
    const formData = new FormData();
    formData.append('title', form.value.title || '');
    formData.append('excerpt', form.value.excerpt || '');
    formData.append('content', form.value.content || '');
    formData.append('is_published', form.value.is_published ? '1' : '0');
    
    // Removed thumbnail upload
    
    form.value.movies.forEach((m, index) => {
      formData.append(`movies[${index}][id]`, m.id);
      formData.append(`movies[${index}][rank]`, m.rank);
      formData.append(`movies[${index}][review_text]`, m.review_text || '');
    });
    
    const config = {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    };

    if (isEditing.value) {
      formData.append('_method', 'PUT');
      await axios.post(`/admin/articles/${currentId.value}`, formData, config);
      toast('Cập nhật bài viết thành công', 'success');
    } else {
      await axios.post('/admin/articles', formData, config);
      toast('Tạo bài viết thành công', 'success');
    }
    closeModal();
    fetchArticles();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      const backendErrors = error.response.data.errors;
      for (let key in backendErrors) {
        errors.value[key] = backendErrors[key][0];
      }
      toast('Vui lòng kiểm tra lại thông tin', 'error');
    } else {
      toast(error.response?.data?.message || 'Có lỗi xảy ra', 'error');
    }
  }
};

const deleteArticle = async (id) => {
  const confirmed = await confirmDialog('Xác nhận xóa', 'Bạn có chắc chắn muốn xóa bài viết này không?');
  if (confirmed) {
    try {
      await axios.delete(`/admin/articles/${id}`);
      toast('Đã xóa bài viết', 'success');
      fetchArticles();
    } catch (error) {
      toast('Lỗi khi xóa bài viết', 'error');
    }
  }
};
</script>

<style scoped>
.admin-movies-view-container {
  padding: 24px;
}

.glass-panel {
  background: white;
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.list-card {
  display: flex;
  flex-direction: column;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 30px;
  border-bottom: 1px solid #f1f5f9;
  background-color: #ffffff;
}

.title-cine {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}

.btn-primary-cine {
  background: linear-gradient(135deg, #e50914 0%, #b91c1c 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
  transition: all 0.3s ease;
}

.btn-primary-cine:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(229, 9, 20, 0.4);
}

/* Movies List Table */
.movies-table-wrapper {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
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
  vertical-align: middle;
}

.table-row:hover {
  background-color: #fffafb;
}

.col-poster { width: 90px; text-align: center; }
.col-title { width: auto; }
.col-status { width: 140px; text-align: center; }
.col-rating { width: 120px; text-align: center; }
.col-actions { width: 180px; text-align: center; }

.cell-poster { text-align: center; }
.poster-thumbnail {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.article-title {
  font-weight: 800;
  color: #1e293b;
  font-size: 16px;
  margin-bottom: 4px;
}
.text-small {
  font-size: 13px;
}
.text-muted {
  color: #64748b;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  max-width: 400px;
}

.cell-status, .cell-rating {
  text-align: center;
}

.status-pill-cine {
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  display: inline-block;
}

.status-pill-cine.active {
  background-color: #d1fae5;
  color: #059669;
  border: 1px solid #34d399;
}

.status-pill-cine.upcoming {
  background-color: #f1f5f9;
  color: #64748b;
  border: 1px solid #cbd5e1;
}

.action-buttons-group {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.btn-action {
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-action.edit {
  background-color: #eff6ff;
  color: #2563eb;
}
.btn-action.edit:hover { background-color: #dbeafe; }

.btn-action.delete {
  background-color: #fef2f2;
  color: #dc2626;
}
.btn-action.delete:hover { background-color: #fee2e2; }

.empty-state {
  text-align: center;
  padding: 40px;
  color: #64748b;
  font-weight: 600;
  font-size: 16px;
}

/* Modal CSS */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content-cine {
  background-color: white;
  border-radius: 24px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
}

.modal-content-cine.modal-lg {
  max-width: 800px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 30px;
  border-bottom: 1px solid #f1f5f9;
  position: sticky;
  top: 0;
  background: white;
  z-index: 10;
}

.modal-title-cine {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.btn-close-modal {
  background: #f1f5f9;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  font-size: 16px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-close-modal:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.movie-form {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group-large {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.form-label-large {
  font-size: 16px;
  font-weight: 800;
  color: #1e293b;
}
.form-input-large {
  width: 100%;
  padding: 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 18px;
  font-weight: 700;
  transition: border-color 0.2s;
  box-sizing: border-box;
}
.form-input-large:focus {
  outline: none;
  border-color: #e50914;
}

.form-row {
  display: flex;
  gap: 20px;
}
.half {
  flex: 1;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.form-label {
  font-size: 14px;
  font-weight: 700;
  color: #475569;
}
.form-input, .form-select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 15px;
  color: #1e293b;
  transition: all 0.2s;
  box-sizing: border-box;
}
.form-textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  font-size: 15px;
  color: #1e293b;
  transition: all 0.2s;
  box-sizing: border-box;
  font-family: inherit;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #e50914;
  box-shadow: 0 0 0 3px rgba(229, 9, 20, 0.1);
}

.is-invalid {
  border-color: #e50914 !important;
}
.text-danger-cine {
  color: #e50914;
  font-size: 13px;
  font-weight: 600;
  margin-top: 4px;
}

.flex-center {
  justify-content: center;
  margin-top: 25px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
  color: #1e293b;
  cursor: pointer;
}
.styled-checkbox {
  width: 20px;
  height: 20px;
  accent-color: #e50914;
}

.divider {
  border: none;
  border-top: 2px dashed #e2e8f0;
  margin: 10px 0;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}
.sub-title {
  font-size: 16px;
  font-weight: 800;
  color: #1e293b;
  margin: 0;
}
.add-movie-row {
  display: flex;
  gap: 10px;
}
.btn-secondary {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 700;
  color: #1e293b;
  cursor: pointer;
}
.btn-secondary:hover {
  background: #e2e8f0;
}

.empty-list {
  text-align: center;
  padding: 30px;
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  color: #64748b;
  font-weight: 600;
}

.movie-list-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.movie-list-item {
  display: flex;
  gap: 16px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  align-items: flex-start;
}
.rank-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 60px;
}
.rank-label {
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8;
  margin-bottom: 4px;
}
.rank-input {
  width: 100%;
  text-align: center;
  padding: 8px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-weight: 700;
  box-sizing: border-box;
}

.info-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.movie-name {
  font-weight: 700;
  font-size: 15px;
  color: #1e293b;
}
.review-textarea {
  width: 100%;
  padding: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 14px;
  box-sizing: border-box;
  font-family: inherit;
}
.btn-remove {
  background: none;
  border: none;
  color: #ef4444;
  font-size: 18px;
  font-weight: 800;
  cursor: pointer;
  padding: 4px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 16px;
  margin-top: 10px;
  padding: 20px;
  border-top: 1px solid #f1f5f9;
  position: sticky;
  bottom: 0;
  background: white;
  z-index: 10;
}

.btn-cancel {
  padding: 12px 24px;
  background: white;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  color: #475569;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-cancel:hover { background: #f8fafc; }

.btn-save {
  padding: 12px 32px;
  background: linear-gradient(135deg, #e50914 0%, #b91c1c 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
  transition: all 0.3s;
}
.btn-save:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(229, 9, 20, 0.4);
}
</style>
