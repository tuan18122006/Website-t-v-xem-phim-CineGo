<template>
  <div class="admin-showtimes-view glass-panel">
    <div class="header-row">
      <h2 class="gradient-text-accent">Quản Lý Lịch Chiếu</h2>
      <button @click="openCreateModal" class="btn-add">+ Thêm Suất Chiếu</button>
    </div>
    
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Đang tải danh sách suất chiếu từ database MySQL...</p>
    </div>

    <div v-else class="showtimes-table-wrapper">
      <table class="showtimes-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên Phim</th>
            <th>Phòng</th>
            <th>Giờ Bắt Đầu</th>
            <th>Giờ Kết Thúc</th>
            <th>Định Dạng</th>
            <th>Dịch Thuật</th>
            <th>Trạng Thái</th>
            <th>Hành Động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="st in showtimes" :key="st.id">
            <td>#{{ st.id }}</td>
            <td class="font-bold">{{ st.movie_title }}</td>
            <td><span class="room-pill">{{ st.room_name }}</span></td>
            <td class="time-highlight">{{ formatDateTime(st.start_time) }}</td>
            <td class="time-end">{{ formatDateTime(st.end_time) }}</td>
            <td><span class="format-pill">{{ st.format }}</span></td>
            <td><span class="translation-pill">{{ st.translation }}</span></td>
            <td>
              <span class="status-pill" :class="{ active: st.status === 'active', cancelled: st.status === 'cancelled' }">
                {{ st.status === 'active' ? 'Hoạt động' : 'Đã hủy' }}
              </span>
            </td>
            <td>
              <button @click="deleteShowtime(st.id)" class="action-btn delete-btn">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- CREATE SHOWTIME MODAL -->
    <div v-if="showModal" class="modal-backdrop">
      <div class="modal-content glass-panel">
        <h3 class="modal-title glow-text-pink">Thêm Suất Chiếu Mới</h3>
        
        <form @submit.prevent="saveShowtime" class="showtime-form">
          <div class="form-group">
            <label>Chọn Phim</label>
            <select v-model="form.movie_id" required class="form-input" @change="onMovieChange">
              <option value="" disabled>-- Vui lòng chọn phim --</option>
              <option v-for="movie in movies" :key="movie.id" :value="movie.id">
                {{ movie.title }} ({{ movie.duration }} phút)
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Chọn Phòng Chiếu</label>
            <select v-model="form.room_id" required class="form-input">
              <option value="" disabled>-- Vui lòng chọn phòng --</option>
              <option v-for="room in rooms" :key="room.id" :value="room.id">
                {{ room.name }} (Sức chứa: {{ room.total_seats }} ghế)
              </option>
            </select>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Thời Gian Chiếu (Bắt đầu)</label>
              <input v-model="form.start_time" type="datetime-local" required class="form-input" @change="calculateEndTime" />
            </div>

            <div class="form-group">
              <label>Thời Gian Kết Thúc (Tự động)</label>
              <input v-model="form.end_time" type="datetime-local" required readonly class="form-input readonly" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Định Dạng</label>
              <select v-model="form.format" required class="form-input">
                <option value="2D">2D</option>
                <option value="3D">3D</option>
                <option value="IMAX">IMAX</option>
              </select>
            </div>

            <div class="form-group">
              <label>Dịch Thuật</label>
              <select v-model="form.translation" required class="form-input">
                <option value="Phụ đề">Phụ đề</option>
                <option value="Thuyết minh">Thuyết minh</option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">Hủy</button>
            <button type="submit" class="btn-save" :disabled="submitting">
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
  
  // Format for datetime-local input
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
    movies.value = response.data;
  } catch (err) {
    console.error('Fetch movies error:', err);
  }
};

const fetchRooms = async () => {
  try {
    const response = await api.get('/rooms');
    rooms.value = response.data;
  } catch (err) {
    console.error('Fetch rooms error:', err);
  }
};

const saveShowtime = async () => {
  submitting.value = true;
  try {
    await api.post('/admin/showtimes', form.value);
    alert('Thêm suất chiếu mới thành công!');
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
  if (!confirm('Bạn có chắc chắn muốn xóa suất chiếu này? Hành động này không thể hoàn tác!')) return;
  try {
    await api.delete(`/admin/showtimes/${id}`);
    alert('Xóa suất chiếu thành công!');
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
.admin-showtimes-view {
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
.showtimes-table-wrapper {
  overflow-x: auto;
}
.showtimes-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.showtimes-table th, .showtimes-table td {
  padding: 16px;
  border-bottom: 1px solid var(--border-glass);
}
.showtimes-table th {
  color: var(--text-muted);
  font-size: 13px;
  text-transform: uppercase;
}
.font-bold {
  font-weight: 700;
}
.room-pill {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  padding: 4px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}
.time-highlight {
  color: var(--accent-mint);
  font-weight: 800;
  font-size: 14px;
}
.time-end {
  color: var(--text-secondary);
  font-size: 13px;
}
.format-pill {
  background: rgba(112, 0, 255, 0.1);
  color: var(--accent-violet);
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 700;
}
.translation-pill {
  background: rgba(255, 0, 127, 0.05);
  color: var(--accent-pink);
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
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
.status-pill.cancelled {
  background: rgba(255, 0, 0, 0.1);
  color: #ff5555;
}
.action-btn {
  background: transparent;
  border: 1px solid var(--border-glass);
  padding: 6px 12px;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: var(--transition-smooth);
}
.delete-btn {
  color: #ff5555;
}
.delete-btn:hover {
  border-color: #ff0000;
  background: rgba(255, 0, 0, 0.1);
  color: white;
}

/* MODAL STYLING */
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
  max-width: 500px;
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
.showtime-form {
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
.form-input.readonly {
  background: rgba(255, 255, 255, 0.01);
  color: var(--text-muted);
  cursor: not-allowed;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
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
