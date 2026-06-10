<template>
  <div class="admin-showtimes-view-container glass-panel">
    <div class="header-row">
      <h2 class="title-cine">🕒 Quản Lý Lịch Chiếu</h2>
      <button @click="openCreateModal" class="btn-primary-cine">+ Thêm Suất Chiếu</button>
    </div>
    
    <!-- Spinner loading dữ liệu -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-cine"></div>
      <p>Đang tải danh sách suất chiếu từ database...</p>
    </div>

    <div v-else class="showtimes-table-wrapper">
      <table class="showtimes-table">
        <thead>
          <tr>
            <th class="col-id">ID</th>
            <th class="col-movie">Tên Phim</th>
            <th class="col-room">Phòng</th>
            <th class="col-time">Giờ Bắt Đầu</th>
            <th class="col-time">Giờ Kết Thúc</th>
            <th class="col-format">Định Dạng</th>
            <th class="col-translation">Dịch Thuật</th>
            <th class="col-status">Trạng Thái</th>
            <th class="col-actions">Hành Động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="st in showtimes" :key="st.id" class="table-row">
            <td class="cell-id">#{{ st.id }}</td>
            <td class="cell-movie">{{ st.movie_title }}</td>
            <td><span class="room-pill-cine">{{ st.room_name }}</span></td>
            <td class="time-start-cine">{{ formatDateTime(st.start_time) }}</td>
            <td class="time-end-cine">{{ formatDateTime(st.end_time) }}</td>
            <td><span class="format-pill-cine">{{ st.format }}</span></td>
            <td><span class="translation-pill-cine">{{ st.translation }}</span></td>
            <td>
              <span class="status-pill-cine" :class="{ active: st.status === 'active', cancelled: st.status === 'cancelled' }">
                {{ st.status === 'active' ? 'Hoạt động' : 'Đã hủy' }}
              </span>
            </td>
            <td class="cell-actions">
              <div class="action-buttons-group">
                <button @click="deleteShowtime(st.id)" class="btn-action delete">🗑️ Xóa</button>
              </div>
            </td>
          </tr>
          
          <tr v-if="showtimes.length === 0">
            <td colspan="9" class="empty-state">
              📭 Hiện chưa có suất chiếu nào được lên lịch. Hãy bấm nút "Thêm Suất Chiếu"!
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CREATE SHOWTIME MODAL (RED/WHITE HIGH ACCESSIBILITY THEME) -->
    <div v-if="showModal" class="modal-backdrop" @click.self="closeModal">
      <div class="modal-content-cine">
        <div class="modal-header">
          <h3 class="modal-title-cine">✨ Thêm Suất Chiếu Mới</h3>
          <button @click="closeModal" class="btn-close-modal">✕</button>
        </div>
        
        <form @submit.prevent="saveShowtime" class="movie-form">
          <div class="form-group-large">
            <label class="form-label-large">Chọn Phim *</label>
            <select v-model="form.movie_id" required class="form-input-large select-cine" @change="onMovieChange">
              <option value="" disabled>-- Vui lòng chọn phim --</option>
              <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                {{ movie.title }} ({{ movie.duration }} phút)
              </option>
            </select>
          </div>

          <div class="form-group-large">
            <label class="form-label-large">Chọn Phòng Chiếu *</label>
            <select v-model="form.room_id" required class="form-input-large select-cine">
              <option value="" disabled>-- Vui lòng chọn phòng chiếu --</option>
              <option v-for="room in rooms" :key="room.id" :value="room.id">
                {{ room.name }} (Sức chứa: {{ room.total_seats }} ghế)
              </option>
            </select>
          </div>

          <div class="form-row-double">
            <div class="form-group-large">
              <label class="form-label-large">Thời Gian Chiếu (Bắt đầu) *</label>
              <input v-model="form.start_time" type="datetime-local" required class="form-input-large" @change="calculateEndTime" />
            </div>

            <div class="form-group-large">
              <label class="form-label-large">Thời Gian Kết Thúc (Tự động)</label>
              <input v-model="form.end_time" type="datetime-local" required readonly class="form-input-large input-readonly" />
            </div>
          </div>

          <div class="form-row-double">
            <div class="form-group-large">
              <label class="form-label-large">Định Dạng Suất Chiếu *</label>
              <select v-model="form.format" required class="form-input-large select-cine">
                <option value="2D">2D</option>
                <option value="3D">3D</option>
                <option value="IMAX">IMAX</option>
              </select>
            </div>

            <div class="form-group-large">
              <label class="form-label-large">Hình Thức Dịch Thuật *</label>
              <select v-model="form.translation" required class="form-input-large select-cine">
                <option value="Phụ đề">Phụ đề (Vietsub)</option>
                <option value="Thuyết minh">Thuyết minh</option>
              </select>
            </div>
          </div>

          <div class="modal-footer-cine">
            <button type="button" @click="closeModal" class="btn-secondary-cine">Hủy bỏ</button>
            <button type="submit" class="btn-primary-cine" :disabled="submitting">
              {{ submitting ? 'Đang tạo...' : 'Tạo Suất Chiếu' }}
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

const showtimes = ref([]);
const movies = ref([]);
const rooms = ref([]);
const loading = ref(false);
const showModal = ref(false);
const submitting = ref(false);

const form = ref({
  movie_id: '',
  room_id: '',
  start_time: '',
  end_time: '',
  format: '2D',
  translation: 'Phụ đề'
});

const openCreateModal = () => {
  form.value = {
    movie_id: '',
    room_id: '',
    start_time: '',
    end_time: '',
    format: '2D',
    translation: 'Phụ đề'
  };
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const formatDateTime = (dateTimeStr) => {
  if (!dateTimeStr) return '';
  const d = new Date(dateTimeStr);
  return d.toLocaleString('vi-VN', { 
    day: '2-digit', 
    month: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const onMovieChange = () => {
  calculateEndTime();
};

const calculateEndTime = () => {
  if (!form.value.start_time || !form.value.movie_id) return;
  const movie = movies.value.find(m => m.id === form.value.movie_id);
  if (!movie) return;

  const start = new Date(form.value.start_time);
  const end = new Date(start.getTime() + movie.duration * 60 * 1000);
  
  // Định dạng chuẩn cho datetime-local input
  const tzOffset = end.getTimezoneOffset() * 60000;
  const localISOTime = new Date(end.getTime() - tzOffset).toISOString().slice(0, 16);
  form.value.end_time = localISOTime;
};

const fetchShowtimes = async () => {
  loading.value = true;
  try {
    const response = await api.get('/admin/showtimes');
    showtimes.value = response.data;
  } catch (err) {
    console.error('Fetch showtimes error:', err);
  } finally {
    loading.value = false;
  }
};

const fetchMovies = async () => {
  try {
    const response = await api.get('/movies');
    // Nhận diện dữ liệu dạng response.data.data hoặc response.data
    movies.value = response.data.data || response.data;
  } catch (err) {
    console.error('Fetch movies error:', err);
  }
};

const fetchRooms = async () => {
  try {
    const response = await api.get('/rooms');
    rooms.value = response.data.data || response.data;
  } catch (err) {
    console.error('Fetch rooms error:', err);
  }
};

const saveShowtime = async () => {
  submitting.value = true;
  try {
    await api.post('/admin/showtimes', form.value);
    alert('🎉 Thêm suất chiếu mới thành công!');
    showModal.value = false;
    await fetchShowtimes();
  } catch (err) {
    console.error('Save showtime error:', err);
    alert(err.response?.data?.message || 'Có lỗi xảy ra khi tạo suất chiếu!');
  } finally {
    submitting.value = false;
  }
};

const deleteShowtime = async (id) => {
  if (!confirm('⚠️ Bạn có chắc chắn muốn xóa suất chiếu này? Hành động này không thể hoàn tác!')) return;
  try {
    await api.delete(`/admin/showtimes/${id}`);
    alert('🗑️ Xóa suất chiếu thành công!');
    await fetchShowtimes();
  } catch (err) {
    console.error('Delete showtime error:', err);
    alert('Không thể xóa suất chiếu này!');
  }
};

onMounted(async () => {
  await fetchShowtimes();
  await fetchMovies();
  await fetchRooms();
});
</script>

<style scoped>
.admin-showtimes-view-container {
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

/* Showtimes List Table */
.showtimes-table-wrapper {
  width: 100%;
  overflow-x: auto;
}
.showtimes-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.showtimes-table th {
  padding: 16px;
  background-color: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
  color: #475569;
  font-size: 15px;
  font-weight: 800;
}
.showtimes-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 15px;
}
.table-row:hover {
  background-color: #fffafb;
}

.col-id { width: 80px; text-align: center; }
.col-movie { min-width: 200px; }
.col-room { width: 120px; }
.col-time { width: 160px; }
.col-format { width: 110px; text-align: center; }
.col-translation { width: 130px; text-align: center; }
.col-status { width: 130px; text-align: center; }
.col-actions { width: 110px; text-align: center; }

.cell-id {
  font-weight: 800;
  color: #e50914;
  text-align: center;
}
.cell-movie {
  font-weight: 700;
  color: #1e293b;
}
.room-pill-cine {
  background-color: #f1f5f9;
  color: #1e293b;
  border: 1px solid #e2e8f0;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
}
.time-start-cine {
  color: #0d9488;
  font-weight: 800;
}
.time-end-cine {
  color: #64748b;
}
.format-pill-cine {
  background-color: #fee2e2;
  color: #b91c1c;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 700;
}
.translation-pill-cine {
  background-color: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
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
.status-pill-cine.cancelled {
  background-color: #fee2e2;
  color: #991b1b;
}
.cell-actions {
  text-align: center;
}
.action-buttons-group {
  display: flex;
  justify-content: center;
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

/* PREMIUM SCROLLABLE RED & WHITE MODAL */
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
  max-width: 600px;
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

.modal-footer-cine {
  display: flex;
  justify-content: flex-end;
  gap: 15px;
  margin-top: 15px;
  border-top: 2px solid #fee2e2;
  padding-top: 20px;
}
</style>
