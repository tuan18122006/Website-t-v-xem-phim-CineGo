<template>
  <div class="admin-view combos-view">
    <header class="admin-header">
      <div class="header-left">
        <h2>🍿 Quản lý Bắp nước (Combos)</h2>
        <p class="subtitle">Quản lý danh sách, giá cả và trạng thái của các Combo</p>
      </div>
      <button class="btn-cinego" @click="openModal()">
        <i class="fas fa-plus"></i> Thêm Combo Mới
      </button>
    </header>

    <div class="table-container glass-panel">
      <table class="cinego-table">
        <thead>
          <tr>
            <th>Hình ảnh</th>
            <th>Tên Combo</th>
            <th>Mô tả</th>
            <th>Giá bán (VNĐ)</th>
            <th>Trạng thái</th>
            <th class="actions-col">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="combo in combos" :key="combo.id">
            <td>
              <img :src="combo.image_url || 'https://via.placeholder.com/60?text=No+Image'" alt="Combo" class="combo-img-thumb" />
            </td>
            <td class="combo-name">{{ combo.name }}</td>
            <td class="combo-desc" :title="combo.description">{{ truncateText(combo.description, 40) }}</td>
            <td class="combo-price">{{ formatCurrency(combo.price) }}</td>
            <td>
              <span class="status-badge" :class="combo.status">
                {{ combo.status === 'active' ? 'Đang bán' : 'Hết hàng/Ẩn' }}
              </span>
            </td>
            <td class="actions-col">
              <button class="btn-action edit" @click="openModal(combo)" title="Sửa">
                ✏️ Sửa
              </button>
              <button class="btn-action toggle" @click="toggleStatus(combo)" :title="combo.status === 'active' ? 'Tạm ngưng bán' : 'Mở bán lại'">
                {{ combo.status === 'active' ? '🚫 Ẩn' : '👁️ Hiện' }}
              </button>
              <button class="btn-action delete" @click="deleteCombo(combo.id)" title="Xóa">
                🗑️ Xóa
              </button>
            </td>
          </tr>
          <tr v-if="combos.length === 0">
            <td colspan="6" class="text-center py-8 text-gray-400">
              Chưa có dữ liệu Combo nào.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Form -->
    <div v-if="isModalOpen" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content glass-panel">
        <h3 class="modal-title">{{ editForm.id ? 'Sửa Combo Bắp Nước' : 'Thêm Combo Bắp Nước' }}</h3>
        
        <form @submit.prevent="saveCombo" class="combo-form">
          <div class="form-group">
            <label>Tên Combo <span class="required">*</span></label>
            <input type="text" v-model="editForm.name" class="cinego-input" :class="{ 'is-invalid': formErrors.name }" placeholder="VD: Combo Solo Bắp Ngọt" />
            <span class="error-msg" v-if="formErrors.name">{{ formErrors.name }}</span>
          </div>

          <div class="form-group">
            <label>Giá bán (VNĐ) <span class="required">*</span></label>
            <input type="text" :value="formattedPrice" @input="handlePriceInput" class="cinego-input" :class="{ 'is-invalid': formErrors.price }" placeholder="0" />
            <span class="error-msg" v-if="formErrors.price">{{ formErrors.price }}</span>
          </div>

          <div class="form-group">
            <label>Hình Ảnh</label>
            <input type="file" @change="handleFileChange" accept="image/*" class="cinego-input" :class="{ 'is-invalid': formErrors.image_file }" />
            <span class="error-msg" v-if="formErrors.image_file">{{ formErrors.image_file }}</span>
            <div class="img-preview" v-if="previewImageUrl">
              <img :src="previewImageUrl" alt="Preview" />
            </div>
          </div>

          <div class="form-group">
            <label>Mô tả chi tiết <span class="required">*</span></label>
            <textarea v-model="editForm.description" class="cinego-input" :class="{ 'is-invalid': formErrors.description }" rows="3" placeholder="VD: 1 Bắp lớn vị ngọt + 1 Nước ngọt size L tùy chọn"></textarea>
            <span class="error-msg" v-if="formErrors.description">{{ formErrors.description }}</span>
          </div>

          <div class="form-group">
            <label>Trạng thái</label>
            <select v-model="editForm.status" class="cinego-input">
              <option value="active">Đang bán (Active)</option>
              <option value="inactive">Hết hàng / Ẩn (Inactive)</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-outline" @click="closeModal">Hủy bỏ</button>
            <button type="submit" class="btn-cinego" :disabled="isSaving">
              {{ isSaving ? 'Đang lưu...' : 'Lưu lại' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../api/axios';
import Swal from 'sweetalert2';

const combos = ref([]);
const isModalOpen = ref(false);
const isSaving = ref(false);
const selectedFile = ref(null);
const previewImageUrl = ref('');

const formErrors = ref({
  name: '',
  price: '',
  image_file: '',
  description: ''
});

const editForm = ref({
  id: null,
  name: '',
  description: '',
  price: 0,
  image_url: '',
  status: 'active'
});

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    if (!file.type.startsWith('image/')) {
      formErrors.value.image_file = 'Vui lòng chọn file hình ảnh.';
      return;
    }
    formErrors.value.image_file = '';
    selectedFile.value = file;
    previewImageUrl.value = URL.createObjectURL(file);
  }
};

const formattedPrice = computed(() => {
  if (editForm.value.price === null || editForm.value.price === undefined || editForm.value.price === '') return '';
  return Number(editForm.value.price).toLocaleString('vi-VN');
});

const handlePriceInput = (e) => {
  let val = e.target.value.replace(/\D/g, '');
  if (val === '') {
    editForm.value.price = '';
  } else {
    editForm.value.price = parseInt(val, 10);
  }
};

const validateForm = () => {
  let isValid = true;
  formErrors.value = { name: '', price: '', image_file: '', description: '' };
  
  if (!editForm.value.name || editForm.value.name.trim() === '') {
    formErrors.value.name = 'Vui lòng nhập Tên Combo.';
    isValid = false;
  }
  
  if (editForm.value.price === '' || editForm.value.price === null || editForm.value.price < 0) {
    formErrors.value.price = 'Giá bán phải là số >= 0.';
    isValid = false;
  }

  if (!editForm.value.description || editForm.value.description.trim() === '') {
    formErrors.value.description = 'Vui lòng nhập Mô tả chi tiết cho Combo.';
    isValid = false;
  }
  
  return isValid;
};

const fetchCombos = async () => {
  try {
    const res = await api.get('/admin/combos');
    if (res.data.success) {
      combos.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to load combos:', err);
    Swal.fire('Lỗi', 'Không thể tải danh sách Combo: ' + (err.response?.data?.message || err.message), 'error');
  }
};

const formatCurrency = (val) => {
  if (!val) return '0 ₫';
  return Number(val).toLocaleString('vi-VN') + ' ₫';
};

const truncateText = (text, len) => {
  if (!text) return '';
  return text.length > len ? text.substring(0, len) + '...' : text;
};

const openModal = (combo = null) => {
  formErrors.value = { name: '', price: '', image_file: '', description: '' };
  selectedFile.value = null;
  if (combo) {
    editForm.value = { ...combo };
    previewImageUrl.value = combo.image_url || '';
  } else {
    editForm.value = {
      id: null,
      name: '',
      description: '',
      price: 0,
      image_url: '',
      status: 'active'
    };
    previewImageUrl.value = '';
  }
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const saveCombo = async () => {
  if (!validateForm()) return;
  
  isSaving.value = true;
  try {
    const formData = new FormData();
    formData.append('name', editForm.value.name);
    formData.append('description', editForm.value.description);
    formData.append('price', editForm.value.price);
    formData.append('status', editForm.value.status);
    if (selectedFile.value) {
      formData.append('image', selectedFile.value);
    }

    if (editForm.value.id) {
      // Cập nhật (dùng POST và trick _method=PUT để FormData hoạt động với Laravel)
      formData.append('_method', 'PUT');
      const res = await api.post(`/admin/combos/${editForm.value.id}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      if (res.data.success) {
        Swal.fire('Thành công', 'Cập nhật Combo thành công!', 'success');
      }
    } else {
      // Thêm mới
      const res = await api.post(`/admin/combos`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
      if (res.data.success) {
        Swal.fire('Thành công', 'Thêm Combo mới thành công!', 'success');
      }
    }
    closeModal();
    fetchCombos();
  } catch (err) {
    console.error(err);
    Swal.fire('Lỗi', 'Có lỗi xảy ra khi lưu Combo.', 'error');
  } finally {
    isSaving.value = false;
  }
};

const toggleStatus = async (combo) => {
  const newStatus = combo.status === 'active' ? 'inactive' : 'active';
  const confirmMsg = newStatus === 'active' ? 'Mở bán lại combo này?' : 'Tạm ngưng bán (hết hàng) combo này?';
  
  const result = await Swal.fire({
    title: confirmMsg,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#e50914',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Đồng ý',
    cancelButtonText: 'Hủy'
  });

  if (result.isConfirmed) {
    try {
      await api.put(`/admin/combos/${combo.id}`, { status: newStatus });
      fetchCombos();
      Swal.fire('Đã cập nhật', 'Cập nhật trạng thái thành công', 'success');
    } catch (err) {
      Swal.fire('Lỗi', 'Không thể cập nhật trạng thái', 'error');
    }
  }
};

const deleteCombo = async (id) => {
  const result = await Swal.fire({
    title: 'Xóa Combo này?',
    text: "Hành động này không thể hoàn tác!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#e50914',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Xóa ngay',
    cancelButtonText: 'Hủy'
  });

  if (result.isConfirmed) {
    try {
      await api.delete(`/admin/combos/${id}`);
      fetchCombos();
      Swal.fire('Đã xóa', 'Combo đã bị xóa khỏi hệ thống.', 'success');
    } catch (err) {
      Swal.fire('Lỗi', 'Không thể xóa Combo (có thể đã có giao dịch mua).', 'error');
    }
  }
};

onMounted(() => {
  fetchCombos();
});
</script>

<style scoped>
.admin-view {
  padding: 30px;
  background: transparent;
  color: #1e293b;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.admin-header h2 {
  font-size: 26px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 6px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.subtitle {
  color: var(--text-secondary);
  font-size: 14.5px;
}

.table-container {
  overflow-x: auto;
  border-radius: 16px;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
  padding: 8px 8px 16px;
}

.cinego-table {
  width: 100%;
  border-collapse: collapse;
}

.cinego-table th {
  text-align: left;
  padding: 14px 18px;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.cinego-table td {
  padding: 16px 18px;
  font-size: 14px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.03);
  vertical-align: middle;
}

.cinego-table tbody tr {
  transition: background 0.2s ease;
}

.cinego-table tbody tr:hover {
  background: rgba(216, 45, 139, 0.02);
}

.combo-img-thumb {
  width: 70px;
  height: 70px;
  object-fit: cover;
  border-radius: 10px;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.combo-name {
  font-weight: 700;
  color: #1e293b;
  font-size: 15px;
}

.combo-desc {
  color: #64748b;
  font-size: 13.5px;
  max-width: 250px;
  line-height: 1.5;
}

.combo-price {
  font-weight: 800;
  color: var(--accent-pink);
  font-size: 16px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}
.status-badge.active {
  background: #edfcf5;
  color: var(--accent-mint);
  border: 1px solid rgba(16, 185, 129, 0.2);
}
.status-badge.inactive {
  background: #fee2e2;
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.actions-col {
  white-space: nowrap;
}

.btn-action {
  padding: 6px 12px;
  border-radius: 8px;
  border: none;
  background: #f1f5f9;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  margin-right: 8px;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  justify-content: center;
}

.btn-action.edit:hover { background: #eff6ff; color: #3b82f6; }
.btn-action.toggle:hover { background: #fefce8; color: #eab308; }
.btn-action.delete:hover { background: #fef2f2; color: #ef4444; }

/* Modal Form Styles */
.modal-overlay {
  position: fixed; inset: 0; z-index: 1000;
  background: rgba(15, 6, 8, 0.5); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  padding: 20px; overflow-y: auto;
}

.modal-content {
  background: #fff;
  border-radius: 20px;
  padding: 30px;
  width: 100%;
  max-width: 500px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-title {
  font-size: 20px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 24px;
  text-align: center;
  border-bottom: 2px solid rgba(216, 45, 139, 0.1);
  padding-bottom: 12px;
}

.combo-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.form-group label {
  font-size: 14px;
  font-weight: 700;
  color: #475569;
}
.required {
  color: #ef4444;
}

.cinego-input {
  padding: 12px 16px;
  border: 1.5px solid #ececf1;
  border-radius: 10px;
  font-size: 14.5px;
  color: #1e293b;
  background: #fcfcfd;
  transition: all 0.2s;
  outline: none;
}
.cinego-input:focus {
  border-color: var(--accent-pink);
  background: #fff;
  box-shadow: 0 0 0 4px rgba(216, 45, 139, 0.08);
}
.cinego-input.is-invalid {
  border-color: #ef4444;
  background: #fef2f2;
}
.cinego-input.is-invalid:focus {
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
}

.error-msg {
  color: #dc2626;
  font-size: 13px;
  font-weight: 700;
  margin-top: 4px;
}

.img-preview {
  margin-top: 10px;
  width: 120px;
  height: 120px;
  border-radius: 10px;
  overflow: hidden;
  border: 1px dashed rgba(0, 0, 0, 0.15);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}
.img-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 10px;
}

.btn-outline {
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 700;
  border: 1.5px solid #e2e8f0;
  background: #fff;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-outline:hover {
  background: #f8fafc;
  color: #1e293b;
  border-color: #cbd5e1;
}

.btn-cinego {
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 700;
  background: linear-gradient(135deg, var(--accent-pink), var(--accent-violet));
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(216, 45, 139, 0.25);
  transition: all 0.2s;
}
.btn-cinego:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(216, 45, 139, 0.35);
}
.btn-cinego:disabled {
  opacity: 0.7;
  cursor: not-allowed;
  transform: none;
}
</style>
