<template>
  <div class="admin-movies-view-container">
    <div class="glass-panel list-card">
      <div class="header-row">
        <h2 class="title-cine"><Image :size="15" style="vertical-align:-2px" /> Quản Lý Banner Trang Chủ</h2>
        <button @click="openCreateBannerModal" class="btn-primary-cine">+ Thêm Banner</button>
      </div>

      <div v-if="bannerLoading" class="loading-state">
        <div class="spinner-cine"></div>
        <p>Đang tải danh sách banner...</p>
      </div>
      <div v-else class="movies-table-wrapper">
        <table class="movies-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Phim Liên Kết</th>
              <th>Trạng Thái</th>
              <th style="text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="banner in banners" :key="banner.id" class="table-row">
              <td class="cell-id">#{{ banner.id }}</td>
              <td><strong>{{ banner.movie?.title }}</strong></td>
              <td>
                <button class="status-pill-cine" 
                        :class="banner.is_active ? 'active' : 'ended'" 
                        @click="toggleBannerStatus(banner.id)"
                        style="cursor: pointer; border: none;">
                  {{ banner.is_active ? 'Đang bật' : 'Đã tắt' }}
                </button>
              </td>
              <td class="cell-actions">
                <div class="action-buttons-group">
                  <button @click="openEditBannerModal(banner)" class="btn-ghost edit">Sửa</button>
                  <button @click="deleteBanner(banner.id)" class="btn-ghost delete">Xóa</button>
                </div>
              </td>
            </tr>
            <tr v-if="banners.length === 0">
              <td colspan="4" class="empty-state">Chưa có banner nào.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- BANNER MODAL -->
    <div v-if="showBannerModal" class="modal-backdrop" @click.self="closeBannerModal">
      <div class="modal-content-cine" style="max-width: 500px;">
        <div class="modal-header">
          <h3 class="modal-title-cine"><template v-if="isEdit"><SquarePen :size="20" /> Sửa Banner</template><template v-else><Image :size="20" /> Thêm Banner Mới</template></h3>
          <button @click="closeBannerModal" class="btn-close-modal">✕</button>
        </div>

        <form @submit.prevent="saveBanner" class="movie-form" novalidate>
          <div class="form-group-large">
            <label class="form-label-large">Chọn Phim Liên Kết *</label>
            <select v-model="bannerForm.movie_id" class="form-input-large select-cine" required>
              <option value="" disabled>-- Chọn một bộ phim --</option>
              <option v-for="m in movies" :key="m.id" :value="m.id">{{ m.title }}</option>
            </select>
          </div>

          <div class="modal-footer-cine">
            <button type="button" @click="closeBannerModal" class="btn-secondary-cine">Hủy</button>
            <button type="submit" class="btn-primary-cine" :disabled="bannerSubmitting || !bannerForm.movie_id">
              {{ bannerSubmitting ? 'Đang lưu...' : 'Lưu Banner' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { toast, confirmDialog } from '../../utils/alert';
import api from '../../api/axios';
import { Image, SquarePen } from 'lucide-vue-next';

const banners = ref([]);
const movies = ref([]);
const bannerLoading = ref(false);
const showBannerModal = ref(false);
const isEdit = ref(false);
const currentBannerId = ref(null);
const bannerSubmitting = ref(false);
const bannerForm = ref({ movie_id: '' });

const getPosterUrl = (url) => {
  if (!url) return 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&w=100&q=80';
  if (url.startsWith('http')) return url;
  if (url.startsWith('blob:')) return url;
  const cleanPath = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${cleanPath}`;
};

const fetchBanners = async () => {
  bannerLoading.value = true;
  try {
    const res = await api.get('/admin/banners');
    banners.value = res.data.data;
  } catch (e) {
    console.error('Lỗi tải banners:', e);
  } finally {
    bannerLoading.value = false;
  }
};

const fetchMovies = async () => {
  try {
    const response = await api.get('/admin/movies');
    movies.value = response.data.data || response.data;
  } catch (err) {
    console.error('Lỗi tải danh sách phim:', err);
  }
};

const openCreateBannerModal = () => {
  isEdit.value = false;
  currentBannerId.value = null;
  bannerForm.value.movie_id = '';
  showBannerModal.value = true;
};

const openEditBannerModal = (banner) => {
  isEdit.value = true;
  currentBannerId.value = banner.id;
  bannerForm.value.movie_id = banner.movie_id;
  showBannerModal.value = true;
};

const closeBannerModal = () => {
  showBannerModal.value = false;
};

const handleBannerFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    bannerFile.value = file;
    bannerPreview.value = URL.createObjectURL(file);
  }
};

const saveBanner = async () => {
  bannerSubmitting.value = true;
  try {
    const payload = { movie_id: bannerForm.value.movie_id };
    
    if (isEdit.value) {
      await api.put(`/admin/banners/${currentBannerId.value}`, payload);
      toast('Cập nhật banner thành công!');
    } else {
      await api.post('/admin/banners', payload);
      toast('Thêm banner thành công!');
    }
    
    closeBannerModal();
    await fetchBanners();
  } catch (e) {
    console.error(e);
    toast('Lỗi khi lưu banner', 'error');
  } finally {
    bannerSubmitting.value = false;
  }
};

const toggleBannerStatus = async (id) => {
  try {
    await api.patch(`/admin/banners/${id}/toggle`);
    toast('Đã cập nhật trạng thái banner');
    await fetchBanners();
  } catch (e) {
    toast('Lỗi cập nhật', 'error');
  }
};

const deleteBanner = async (id) => {
  if (await confirmDialog('Bạn muốn xóa banner này?')) {
    try {
      await api.delete(`/admin/banners/${id}`);
      toast('Đã xóa banner');
      await fetchBanners();
    } catch (e) {
      toast('Lỗi khi xóa', 'error');
    }
  }
};

onMounted(async () => {
  await fetchMovies();
  await fetchBanners();
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
}
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
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
@keyframes spin { 100% { transform: rotate(360deg); } }
.movies-table-wrapper {
  width: 100%;
  overflow-x: auto;
}
.movies-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
.movies-table th, .movies-table td {
  padding: 16px;
  border-bottom: 1px solid #e2e8f0;
  font-size: 15px;
}
.movies-table th {
  background-color: #f8fafc;
  color: #475569;
  font-weight: 800;
}
.table-row:hover {
  background-color: #fffafb;
}
.cell-id { font-weight: 800; color: #e50914; }
.status-pill-cine {
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 800;
  color: #ffffff;
}
.status-pill-cine.active { background-color: #10b981; }
.status-pill-cine.ended { background-color: #94a3b8; }
.action-buttons-group {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}
.btn-ghost {
  background: transparent;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #3b82f6;
  font-weight: 600;
  cursor: pointer;
  padding: 6px 12px;
  transition: all 0.2s ease;
}
.btn-ghost:hover {
  background: #f1f5f9;
}
.btn-ghost.delete { 
  color: #ef4444; 
  border-color: #fca5a5;
}
.btn-ghost.delete:hover {
  background: #fef2f2;
}
.btn-ghost.edit {
  color: #f59e0b;
  border-color: #fcd34d;
}
.btn-ghost.edit:hover {
  background: #fffbeb;
}
.empty-state { text-align: center; color: #64748b; font-style: italic; }
.modal-backdrop {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.6);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-content-cine {
  background: #ffffff;
  border-radius: 16px;
  padding: 24px;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 16px;
}
.modal-title-cine { font-size: 20px; font-weight: 800; color: #1e293b; }
.btn-close-modal { font-size: 24px; background: none; border: none; cursor: pointer; color: #64748b; }
.form-group-large { margin-bottom: 20px; }
.form-label-large { display: block; font-weight: 700; color: #334155; margin-bottom: 8px; }
.form-input-large, .select-cine {
  width: 100%;
  padding: 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 15px;
  box-sizing: border-box;
}
.modal-footer-cine { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; }
</style>
