<template>
  <div class="admin-movies-view-container">
    <div class="glass-panel list-card">
      <div class="header-row">
        <h2 class="title-cine">🏟️ Quản Lý Phòng Chiếu & Ghế</h2>
        <button @click="openCreateModal" class="btn-primary-cine">+ Thêm Phòng Chiếu Mới</button>
      </div>

      <div class="table-responsive-wrapper">
        <table class="movies-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tên Phòng Chiếu</th>
            <th>Tổng Ghế</th>
            <th>Trạng thái</th>
            <th>Hành Động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="room in rooms" :key="room.id" class="table-row">
            <td>#{{ room.id }}</td>
            <td class="cell-title">{{ room.name }}</td>
            <td>{{ room.total_seats }}</td>
            <td><span class="status-pill-cine active">Đang hoạt động</span></td>
            <td>
              <div class="action-buttons-group">
                <button @click="goToEdit(room.id)" class="btn-action edit">✏️ Chỉnh sửa sơ đồ ghế</button>
                <button @click="deleteRoom(room.id)" class="btn-action delete">🗑️ Xóa</button>
              </div>
            </td>
          </tr>
        </tbody>
        </table>
      </div>
    </div>

    <div v-if="isCreateModalOpen" class="modal-overlay-cine">
      <div class="glass-panel modal-content-cine">
        <div class="modal-header">
          <h3 style="margin: 0;">➕ Thêm Phòng Chiếu Mới</h3>
          <button class="close-btn" @click="isCreateModalOpen = false">×</button>
        </div>
        <div class="modal-body">
          <div class="form-group-cine">
            <label>Tên Phòng Chiếu</label>
            <input v-model="newRoomName" placeholder="Ví dụ: Phòng 01" class="input-cine" />
            <span v-if="errors.newRoomName" class="error-msg">{{ errors.newRoomName[0] }}</span>
          </div>
          <div class="grid-inputs">
            <div class="form-group-cine">
              <label>Số hàng</label>
              <input v-model="newRows" type="number" class="input-cine" />
              <span v-if="errors.newRows" class="error-msg">{{ errors.newRows[0] }}</span>
            </div>
            <div class="form-group-cine">
              <label>Số cột</label>
              <input v-model="newCols" type="number" class="input-cine" />
              <span v-if="errors.newCols" class="error-msg">{{ errors.newCols[0] }}</span>
            </div>
          </div>
        </div>
        <br>
        <div class="modal-footer">
          <button @click="isCreateModalOpen = false" class="btn-secondary-cine">Hủy</button>
          <button @click="saveRoom" class="btn-primary-cine">Lưu Phòng Chiếu</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api/axios';
import { toast, confirmDialog } from '../../utils/alert';

const router = useRouter();
const rooms = ref([]);
const isCreateModalOpen = ref(false);
const newRoomName = ref('');
const newRows = ref(5);
const newCols = ref(10);

const fetchRooms = async () => {
  try {
    const res = await api.get('admin/rooms');
    rooms.value = res.data.data;
  } catch (e) { console.error(e); }
};

const errors = ref({});

const validateForm = () => {
  errors.value = {};
  let isValid = true;

  if (!newRoomName.value || newRoomName.value.trim() === '') {
    errors.value.newRoomName = ['Vui lòng nhập tên phòng chiếu.'];
    isValid = false;
  }

  if (!newRows.value || newRows.value <= 0) {
    errors.value.newRows = ['Số hàng phải lớn hơn 0.'];
    isValid = false;
  }

  if (!newCols.value || newCols.value <= 0) {
    errors.value.newCols = ['Số cột phải lớn hơn 0.'];
    isValid = false;
  }

  return isValid;
};

const saveRoom = async () => {
  if (!validateForm()) return;
  try {
    await api.post('admin/rooms', {
      name: newRoomName.value,
      rows: newRows.value,
      cols: newCols.value
    });
    isCreateModalOpen.value = false;
    toast("Thêm rạp thành công!");
    fetchRooms();
  } catch (e) { toast("Lỗi khi thêm rạp!", "error"); }
};

const deleteRoom = async (id) => {
  if (!(await confirmDialog("Bạn có chắc chắn muốn xóa rạp này không?", "Hành động này không thể hoàn tác!"))) return;

  const originalRooms = [...rooms.value];
  rooms.value = rooms.value.filter(r => r.id !== id);

  try {
    await api.delete(`/admin/rooms/${id}`);
    toast("Đã xóa rạp thành công!");
  } catch (e) {
    rooms.value = originalRooms;
    console.error("Lỗi chi tiết:", e.response ? e.response.data : e);
    toast("Lỗi khi xóa: " + (e.response?.data?.message || "Vui lòng kiểm tra console"), "error");
  }
};

const goToEdit = (id) => router.push({ name: 'admin-room-edit', params: { id } });

const openCreateModal = () => isCreateModalOpen.value = true;

onMounted(fetchRooms);
</script>

<style scoped>
.grid-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-top: 10px;
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
</style>
<style scoped>
.admin-movies-view-container {
  padding: 20px;
}

.list-card {
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 15px;
  padding: 20px;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.table-responsive-wrapper {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.movies-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.movies-table th,
.movies-table td {
  padding: 15px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.cell-title {
  max-width: 180px;
  overflow-x: auto;
  white-space: nowrap;
  -webkit-overflow-scrolling: touch;
}
.cell-title::-webkit-scrollbar {
  height: 3px;
}
.cell-title::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.action-buttons-group {
  display: flex;
  flex-wrap: nowrap;
  gap: 8px;
}

.btn-action {
  padding: 8px 12px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-size: 0.9em;
  white-space: nowrap;
}

.edit {
  background: #e0f2fe;
  color: #0284c7;
}

.delete {
  background: #fee2e2;
  color: #dc2626;
}

.input-cine {
  width: 100%;
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  box-sizing: border-box;
}

.table-row:hover {
  background-color: #f9fafb;
  transition: 0.3s;
}
.btn-primary-cine {
  background-color: #ef4444;
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.3s ease;
  margin-left: 5px; 
}

.btn-secondary-cine {
  background-color: #f3f4f6 !important;
  color: #374151 !important;
  padding: 10px 20px !important;
  border-radius: 8px !important;
  border: 1px solid #d1d5db !important;
  font-weight: 600 !important;
  cursor: pointer !important;
  transition: all 0.3s ease !important;
}

.btn-primary-cine:hover {
  background-color: #dc2626;
}

.modal-overlay-cine {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100% !important;
  height: 100% !important;
  background: rgba(0, 0, 0, 0.6) !important;
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  z-index: 9999 !important;
}

.modal-content-cine {
  background: white !important;
  display: block !important;
  padding: 25px;
  border-radius: 15px;
  width: 450px;
  visibility: visible !important;
  opacity: 1 !important;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
  color: #9ca3af;
  transition: color 0.3s;
  padding: 0;
  margin-top: -5px;
}

.close-btn:hover {
  color: #ef4444;
}

.error-msg {
  color: #dc2626;
  font-size: 0.85rem;
  margin-top: 5px;
  display: block;
  font-weight: 600;
}
</style>