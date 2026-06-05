<template>
  <div class="dashboard-tab-content">
    
    <div class="glass-panel detailed-report" style="margin-bottom: 25px; padding: 25px; border-radius: 16px;">
      <h3 class="card-title" style="margin-bottom: 20px; font-size: 16px; font-weight: 700; border-left: 4px solid var(--accent-pink); padding-left: 10px;">
        {{ isEditing ? '📝 Cập Nhật Thể Loại' : '✨ Thêm Thể Loại Mới' }}
      </h3>
      
      <form @submit.prevent="saveGenre" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 8px;">
          <label style="font-size: 13px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Tên thể loại</label>
          <input 
            v-model="form.name" 
            type="text" 
            style="width: 100%; border: 1px solid rgba(0,0,0,0.08); padding: 12px 16px; border-radius: 8px; outline: none; font-size: 14px; background-color: #fdfbfd;"
            placeholder="Nhập tên thể loại phim..." 
            required 
          />
        </div>

        <div style="flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 8px;">
          <label style="font-size: 13px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Slug tự sinh</label>
          <input 
            :value="generateSlug(form.name)"
            type="text" 
            style="width: 100%; border: 1px solid rgba(0,0,0,0.05); padding: 12px 16px; border-radius: 8px; outline: none; font-size: 14px; background-color: #f1f5f9; color: #64748b; font-family: monospace;"
            readonly
            placeholder="slug-duong-dan-tinh" 
          />
        </div>
        
        <div style="display: flex; gap: 10px;">
          <button 
            type="submit" 
            class="btn-export" 
            style="background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 15px rgba(216, 45, 139, 0.2);"
          >
            {{ isEditing ? 'Cập nhật' : 'Lưu lại' }}
          </button>
          
          <button 
            v-if="isEditing" 
            type="button" 
            @click="resetForm" 
            class="btn-logout-sidebar"
            style="padding: 12px 20px; border-radius: 8px; font-weight: 600; margin: 0;"
          >
            Hủy
          </button>
        </div>
      </form>
    </div>

    <div class="glass-panel detailed-report" style="padding: 25px; border-radius: 16px;">
      <div class="report-header" style="margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #1e293b;">Danh Sách Thể Loại</h3>
      </div>
      
      <div style="overflow-x: auto;">
        <table class="report-table">
          <thead>
            <tr>
              <th style="width: 70px; text-align: center; font-weight: 700;">ID</th>
              <th style="font-weight: 700;">Tên thể loại</th>
              <th style="font-weight: 700;">Slug</th>
              <th style="font-weight: 700;">Thời gian cập nhật</th>
              <th style="width: 160px; text-align: center; font-weight: 700;">Hành Động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="genre in genres" :key="genre.id">
              <td class="font-bold text-pink" style="text-align: center; font-weight: 700; font-size: 14px;">
                #{{ genre.id }}
              </td>
              <td style="font-weight: 700; color: #1e293b; font-size: 14px;">
                {{ genre.name }}
              </td>
              <td>
                <span class="method-badge" style="background-color: rgba(216, 45, 139, 0.05); color: var(--accent-pink); font-family: monospace; font-size: 12px; padding: 4px 10px; border-radius: 6px;">
                  {{ genre.slug }}
                </span>
              </td>
              <td style="color: #64748b; font-size: 13px;">
                {{ formatDate(genre.updated_at) }}
              </td>
              <td style="text-align: center;">
                <div style="display: flex; justify-content: center; gap: 8px;">
                  <button 
                    @click="editGenre(genre)" 
                    class="btn-export" 
                    style="padding: 6px 14px; font-size: 12px; border-color: #dd6b20; color: #dd6b20; font-weight: 700; border-radius: 6px;"
                  >
                    Sửa
                  </button>
                  <button 
                    @click="deleteGenre(genre.id)" 
                    class="btn-logout-sidebar" 
                    style="padding: 6px 14px; font-size: 12px; font-weight: 600; border-radius: 6px; margin: 0;"
                  >
                    Xóa
                  </button>
                </div>
              </td>
            </tr>

            <tr v-if="genres.length === 0">
              <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px;">
                📭 Chưa có dữ liệu thể loại phim.
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
import axios from 'axios'; // Đã sửa sạch lỗi import bậy bạ ở đây

const genres = ref([]);
const isEditing = ref(false);
const editingId = ref(null);
const form = ref({ name: '' });

const API_URL = 'http://127.0.0.1:8000/api/admin/genres';

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

const getAuthConfig = () => {
  const token = localStorage.getItem('cinego_token');
  return {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  };
};

const formatDate = (dateString) => {
  if (!dateString) return 'Chưa có thông tin';
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
  try {
    const response = await axios.get(API_URL, getAuthConfig());
    if (response.data && response.data.data) {
      genres.value = response.data.data;
    } else {
      genres.value = response.data;
    }
  } catch (error) {
    console.error('Lỗi tải danh sách:', error);
    if (error.response?.status === 401) {
      alert('Hết phiên làm việc hoặc chưa đăng nhập tài khoản hợp lệ! Vui lòng đăng nhập lại.');
    }
  }
};

const saveGenre = async () => {
  try {
    const payload = {
      name: form.value.name,
      slug: generateSlug(form.value.name)
    };

    if (isEditing.value) {
      await axios.put(`${API_URL}/${editingId.value}`, payload, getAuthConfig());
      alert('🎉 Cập nhật thể loại thành công!');
    } else {
      await axios.post(API_URL, payload, getAuthConfig());
      alert('🎉 Thêm thể loại mới thành công!');
    }
    resetForm();
    fetchGenres();
  } catch (error) {
    console.error(error.response);
    alert(error.response?.data?.message || 'Có lỗi xảy ra khi lưu dữ liệu!');
  }
};

const editGenre = (genre) => {
  isEditing.value = true;
  editingId.value = genre.id;
  form.value.name = genre.name;
};

const deleteGenre = async (id) => {
  if (confirm('⚠️ Bạn có chắc chắn muốn xóa thể loại này không?')) {
    try {
      await axios.delete(`${API_URL}/${id}`, getAuthConfig());
      alert('🗑️ Đã xóa thể loại thành công!');
      fetchGenres();
    } catch (error) {
      console.error(error.response);
      alert('Không thể xóa thể loại này.');
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
input:focus {
  border-color: var(--accent-pink) !important;
  box-shadow: 0 0 0 3px rgba(216, 45, 139, 0.1);
  background-color: #ffffff !important;
}
</style>