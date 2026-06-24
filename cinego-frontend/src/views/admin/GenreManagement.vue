<template>
  <div class="genre-management-container">

    <!-- CARD 1: FORM THÊM / CẬP NHẬT THỂ LOẠI -->
    <div class="glass-panel form-card">
      <h3 class="card-title">
        <span class="title-icon">{{ isEditing ? '📝' : '✨' }}</span>
        {{ isEditing ? 'Cập Nhật Thể Loại Phim' : 'Thêm Thể Loại Mới' }}
      </h3>

      <form @submit.prevent="saveGenre" class="genre-form">
        <div class="form-inputs-row">
          <div class="input-group">
            <label class="form-label">Tên thể loại *</label>
            <input v-model="form.name" type="text" class="form-input-large" placeholder="Nhập tên thể loại phim..." />
            <span v-if="errors?.name" class="error-msg" style="color: red; font-size: 0.8rem;">
              {{ errors.name[0] }}
            </span>
          </div>

          <div class="input-group">
            <label class="form-label label-muted">Slug tự sinh (Đường dẫn tĩnh)</label>
            <input :value="generateSlug(form.name)" type="text" class="form-input-large input-readonly" readonly
              placeholder="slug-tu-dong" />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary-cine">
            {{ isEditing ? 'Cập nhật thể loại' : 'Lưu thể loại' }}
          </button>

          <button v-if="isEditing" type="button" @click="resetForm" class="btn-secondary-cine">
            Hủy bỏ
          </button>
        </div>
      </form>
    </div>

    <!-- CARD 2: DANH SÁCH THỂ LOẠI -->
    <div class="glass-panel list-card">
      <div class="list-header">
        <h3 class="list-title">📁 Danh Sách Thể Loại Hiện Có</h3>
        <span class="count-badge">Tổng cộng: {{ genres.length }} thể loại</span>
      </div>

      <!-- Loading spinner -->
      <div v-if="loading" class="loading-state">
        <div class="spinner-cine"></div>
        <p>Đang tải dữ liệu thể loại...</p>
      </div>

      <div v-else class="table-responsive">
        <table class="genre-table">
          <thead>
            <tr>
              <th class="col-id">ID</th>
              <th class="col-name">Tên thể loại</th>
              <th class="col-slug">Slug đường dẫn</th>
              <th class="col-date">Thời gian cập nhật</th>
              <th class="col-actions">Hành Động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="genre in genres" :key="genre.id" class="table-row">
              <td class="cell-id">#{{ genre.id }}</td>
              <td class="cell-name">{{ genre.name }}</td>
              <td class="cell-slug">
                <span class="slug-tag">{{ genre.slug }}</span>
              </td>
              <td class="cell-date">{{ formatDate(genre.updated_at) }}</td>
              <td class="cell-actions">
                <div class="action-buttons-group">
                  <button @click="editGenre(genre)" class="btn-action edit">
                    ✏️ Sửa
                  </button>
                  <button @click="deleteGenre(genre.id)" class="btn-action delete">
                    🗑️ Xóa
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="genres.length === 0">
              <td colspan="5" class="empty-state">
                📭 Hệ thống chưa có dữ liệu thể loại phim.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/axios'; // Dùng api config tập trung giúp tối ưu hiệu năng và xử lý lỗi 401

const genres = ref([]);
const isEditing = ref(false);
const editingId = ref(null);
const loading = ref(false);
const form = ref({ name: '' });

const generateSlug = (title) => {
  if (!title) return '';
  let slug = title.toLowerCase();
  slug = slug.replace(/á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/gi, 'a');
  slug = slug.replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/gi, 'e');
  slug = slug.replace(/i|í|ì|ỉ|ĩ|ị/gi, 'i');
  slug = slug.replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/gi, 'o');
  slug = slug.replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/gi, 'u');
  slug = slug.replace(/ý|ỳ|ỷ|ỹ|ị/gi, 'y');
  slug = slug.replace(/đ/gi, 'd');
  slug = slug.replace(/\s+/g, '-');
  slug = slug.replace(/[^a-z0-9\-]/g, '');
  slug = slug.replace(/\-+/g, '-');
  slug = slug.replace(/^-+|-+$/g, '');
  return slug;
};

const formatDate = (dateString) => {
  if (!dateString) return 'Chưa cập nhật';
  const date = new Date(dateString);
  return date.toLocaleString('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  });
};

const fetchGenres = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/genres');
    genres.value = response.data.data || response.data;
  } catch (error) {
    console.error('Lỗi tải danh sách thể loại:', error);
  } finally {
    loading.value = false;
  }
};

const errors = ref({});

const saveGenre = async () => {
  errors.value = {};

  try {
    const payload = {
      name: form.value.name.trim(),
    };

    if (isEditing.value) {
      await api.put(`/admin/genres/${editingId.value}`, payload);
      alert('🎉 Cập nhật thành công!');
    } else {
      await api.post('/admin/genres', payload);
      alert('🎉 Thêm thành công!');
    }
    resetForm();
    await fetchGenres();
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors;
    } else {
      alert('Có lỗi xảy ra!');
    }
  }
};

const editGenre = (genre) => {
  isEditing.value = true;
  editingId.value = genre.id;
  form.value.name = genre.name;
  // Cuộn nhẹ lên trên để người dùng dễ nhập liệu
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const deleteGenre = async (id) => {
  if (confirm('⚠️ Bạn có chắc chắn muốn xóa thể loại này không? Tất cả các liên kết phim liên quan sẽ bị ảnh hưởng.')) {
    try {
      await api.delete(`/admin/genres/${id}`);
      alert('🗑️ Đã xóa thể loại thành công!');
      await fetchGenres();
    } catch (error) {
      console.error('Lỗi xóa thể loại:', error);
      alert(error.response?.data?.message || 'Không thể xóa thể loại này!');
    }
  }
};

const resetForm = () => {
  form.value.name = '';
  isEditing.value = false;
  editingId.value = null;
};

onMounted(fetchGenres);
</script>

<style scoped>
.genre-management-container {
  display: flex;
  flex-direction: column;
  gap: 25px;
  background-color: #ffffff;
  color: #1e293b;
}

/* Glass-like Panel styled for Red/White Theme */
.glass-panel {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
  padding: 30px;
  transition: all 0.3s ease;
}

.glass-panel:hover {
  box-shadow: 0 6px 25px rgba(229, 9, 20, 0.04);
  border-color: rgba(229, 9, 20, 0.15);
}

/* Form Card Styles */
.form-card {
  border-left: 5px solid #e50914;
  /* Dải đỏ đô sang trọng ở viền trái */
}

.card-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 20px;
  font-weight: 800;
  color: #9b000e;
  margin-bottom: 25px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.title-icon {
  font-size: 24px;
}

.genre-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-inputs-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.input-group {
  flex: 1;
  min-width: 280px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.form-label {
  font-size: 15px;
  font-weight: 700;
  color: #334155;
}

.form-label.label-muted {
  color: #64748b;
}

/* Inputs are larger (16px) and taller */
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

/* Dynamic CineGo themed buttons */
.form-actions {
  display: flex;
  gap: 15px;
  margin-top: 5px;
}

.btn-primary-cine {
  background: linear-gradient(135deg, #e50914 0%, #9b000e 100%);
  color: #ffffff;
  border: none;
  padding: 14px 28px;
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

.btn-primary-cine:active {
  transform: translateY(0);
}

.btn-secondary-cine {
  background-color: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  padding: 14px 24px;
  border-radius: 10px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary-cine:hover {
  background-color: #f1f5f9;
  color: #1e293b;
  border-color: #94a3b8;
}

/* List Card Styles */
.list-card {
  border-top: 4px solid #cbd5e1;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 25px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 15px;
}

.list-title {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
}

.count-badge {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 6px 14px;
  border-radius: 30px;
  font-size: 13px;
  font-weight: 700;
}

/* Performance Loading Spinner */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 50px 0;
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

/* Table styling with 15px headers and 16px body */
.table-responsive {
  width: 100%;
  overflow-x: auto;
}

.genre-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.genre-table th {
  padding: 16px;
  background-color: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  color: #475569;
  font-size: 15px;
  font-weight: 800;
}

.genre-table td {
  padding: 18px 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 15px;
}

.table-row:hover {
  background-color: #fffafb;
}

/* Cell Specific Styles */
.col-id {
  width: 90px;
  text-align: center;
}

.col-name {
  min-width: 200px;
}

.col-slug {
  min-width: 180px;
}

.col-date {
  width: 220px;
}

.col-actions {
  width: 180px;
  text-align: center;
}

.cell-id {
  font-weight: 800;
  color: #e50914;
  text-align: center;
}

.cell-name {
  font-weight: 700;
  color: #1e293b;
}

.slug-tag {
  background-color: #f1f5f9;
  color: #475569;
  font-family: monospace;
  font-size: 14px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.cell-date {
  color: #64748b;
}

/* Buttons in the table: larger, easier to click */
.action-buttons-group {
  display: flex;
  justify-content: center;
  gap: 10px;
}

.btn-action {
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  padding: 8px 16px;
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
</style>