<template>
  <div class="view-content">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h3 style="margin: 0 0 4px; font-size: 21px; font-weight: 800; color: #0f172a;">Thùng Rác</h3>
        <p style="margin: 0; font-size: 13px; color: #64748b;">
          Các dữ liệu đã xóa được lưu tạm ở đây. Bạn có thể khôi phục hoặc xóa vĩnh viễn.
        </p>
      </div>
      <div style="display: flex; gap: 10px;">
        <button class="btn-action" @click="loadData" :disabled="loading">
          <RefreshCw :size="15" style="vertical-align:-2px" /> Làm mới
        </button>
        <button class="btn-action delete" @click="handleEmptyAll" :disabled="loading || totalCount === 0">
          <Trash2 :size="15" style="vertical-align:-2px" /> Dọn sạch thùng rác
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-state">
      <div class="spinner-cine"></div>
      <p>Đang tải thùng rác...</p>
    </div>

    <!-- Tabs -->
    <template v-else>
      <div class="trash-tabs">
        <button
          v-for="t in trashTabs"
          :key="t.key"
          class="trash-tab"
          :class="{ active: activeTab === t.key }"
          @click="activeTab = t.key"
        >
          <component :is="t.icon" :size="15" style="vertical-align:-2px" />
          {{ t.label }}
          <span class="tab-badge">{{ data[t.key].length }}</span>
        </button>
      </div>

      <!-- Empty state -->
      <div v-if="data[activeTab].length === 0" class="empty-state">
        <Inbox :size="42" style="color: #cbd5e1;" />
        <p style="color: #94a3b8; margin: 10px 0 0;">Thùng rác trống — không có dữ liệu đã xóa ở mục này.</p>
      </div>

      <!-- Items table -->
      <div v-else class="table-wrap">
        <table class="trash-table">
          <thead>
            <tr>
              <th class="col-info">THÔNG TIN</th>
              <th>NGÀY XÓA</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in data[activeTab]" :key="item.id">
              <td class="col-info item-cell">
                <div class="item-row">
                  <img
                    v-if="item.poster_url || item.avatar_url || item.image_url"
                    :src="getImageUrl(item.poster_url || item.avatar_url || item.image_url)"
                    class="item-thumb"
                    alt=""
                    @error="handleImageError"
                  />
                  <div v-else class="item-thumb placeholder">
                    <component :is="activeTabIcon" :size="20" style="color:#cbd5e1;" />
                  </div>
                  <div>
                    <div class="item-title">{{ displayInfo(item).title }}</div>
                    <div v-if="displayInfo(item).sub" class="item-sub">{{ displayInfo(item).sub }}</div>
                  </div>
                </div>
              </td>
              <td class="cell-muted">{{ formatDate(item.deleted_at) }}</td>
              <td>
                <div class="row-actions">
                  <button class="btn-action restore" @click="restoreItem(item)">
                    <RotateCcw :size="15" style="vertical-align:-2px" /> Khôi phục
                  </button>
                  <button class="btn-action delete" @click="forceDeleteItem(item)">
                    <Trash2 :size="15" style="vertical-align:-2px" /> Xóa vĩnh viễn
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { RefreshCw, Trash2, RotateCcw, Inbox, Film, Clock, Drama, Folder, Popcorn, Ticket } from 'lucide-vue-next';
import { getTrashItems, restoreTrashItem, forceDeleteTrashItem, emptyTrash } from '../../api/trash';
import { toast, confirmDialog } from '../../utils/alert';

const loading = ref(false);
const activeTab = ref('movies');
const data = reactive({
  movies: [],
  showtimes: [],
  actors: [],
  genres: [],
  combos: [],
  vouchers: [],
});

const trashTabs = [
  { key: 'movies', label: 'Phim', icon: Film },
  { key: 'showtimes', label: 'Suất chiếu', icon: Clock },
  { key: 'actors', label: 'Diễn viên', icon: Drama },
  { key: 'genres', label: 'Thể loại', icon: Folder },
  { key: 'combos', label: 'Combo', icon: Popcorn },
  { key: 'vouchers', label: 'Voucher', icon: Ticket },
];

const activeTabIcon = computed(() => trashTabs.find((t) => t.key === activeTab.value)?.icon || Film);

const totalCount = computed(() =>
  data.movies.length + data.showtimes.length + data.actors.length + data.genres.length +
  data.combos.length + data.vouchers.length
);

const formatDate = (value) => {
  if (!value) return '—';
  const d = new Date(value);
  if (isNaN(d)) return value;
  return d.toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' });
};

// Chuẩn hóa URL ảnh: trỏ đúng về host backend port 8000 cho ảnh relative (posters/..., avatars/...)
const getImageUrl = (url) => {
  if (!url) return '';
  if (url.startsWith('http')) return url;
  if (url.startsWith('blob:')) return url;
  const cleanPath = url.replace(/^(.*\/storage\/)/, '');
  return `http://127.0.0.1:8000/storage/${cleanPath}`;
};

const handleImageError = (event) => {
  event.target.style.display = 'none';
};

const loadData = async () => {
  loading.value = true;
  try {
    const res = await getTrashItems();
    const d = res.data.data || {};
    data.movies = d.movies || [];
    data.showtimes = d.showtimes || [];
    data.actors = d.actors || [];
    data.genres = d.genres || [];
    data.combos = d.combos || [];
    data.vouchers = d.vouchers || [];
  } catch (err) {
    toast(err.response?.data?.message || 'Không thể tải thùng rác.', 'error');
  } finally {
    loading.value = false;
  }
};

const restoreItem = async (item) => {
  const type = apiType(activeTab.value);
  const confirmed = await confirmDialog('Khôi phục mục này?', `Bạn có chắc muốn khôi phục "${displayInfo(item).title}"?`);
  if (!confirmed) return;
  try {
    await restoreTrashItem(type, item.id);
    toast('Khôi phục thành công.', 'success');
    loadData();
  } catch (err) {
    toast(err.response?.data?.message || 'Khôi phục thất bại.', 'error');
  }
};

const forceDeleteItem = async (item) => {
  const type = apiType(activeTab.value);
  const name = displayInfo(item).title;
  const confirmed = await confirmDialog('Xóa vĩnh viễn?', `Xóa VĨNH VIỄN "${name}"? Thao tác này không thể hoàn tác!`);
  if (!confirmed) return;
  try {
    await forceDeleteTrashItem(type, item.id);
    toast('Đã xóa vĩnh viễn.', 'success');
    loadData();
  } catch (err) {
    toast(err.response?.data?.message || 'Xóa vĩnh viễn thất bại.', 'error');
  }
};

// Map key tab (số nhiều) -> type API (số ít) khớp với backend
const apiType = (key) => ({
  movies: 'movie',
  showtimes: 'showtime',
  actors: 'actor',
  genres: 'genre',
  combos: 'combo',
  vouchers: 'voucher',
}[key] || key);

const displayInfo = (item) => {
  if (item.movie) {
    const t = `${item.movie.title || 'Phim'} - ${formatDate(item.start_time)}`;
    const sub = `Phòng ${item.room?.name || '?'}`;
    return { title: t, sub };
  }
  return { title: item.title || item.name || item.code || `#${item.id}`, sub: '' };
};

const handleEmptyAll = async () => {
  const confirmed = await confirmDialog('Dọn thùng rác?', 'Bạn có chắc muốn dọn sạch toàn bộ thùng rác? Các mục còn vé đã bán sẽ được giữ lại.');
  if (!confirmed) return;
  try {
    const res = await emptyTrash();
    toast(res.data.message || 'Đã dọn thùng rác.', 'success');
    loadData();
  } catch (err) {
    toast(err.response?.data?.message || 'Dọn thùng rác thất bại.', 'error');
  }
};

onMounted(loadData);
</script>

<style scoped>
.trash-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}
.trash-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}
.trash-tab:hover {
  border-color: #e50914;
  color: #e50914;
}
.trash-tab.active {
  background: #e50914;
  border-color: #e50914;
  color: #fff;
}
.tab-badge {
  background: #f1f5f9;
  color: #475569;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 7px;
}
.trash-tab.active .tab-badge {
  background: rgba(255, 255, 255, 0.25);
  color: #fff;
}
.table-wrap {
  overflow-x: auto;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.trash-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.trash-table th {
  background: #f8fafc;
  color: #64748b;
  font-size: 11px;
  letter-spacing: 0.5px;
  text-align: left;
  padding: 12px 16px;
  border-bottom: 1px solid #e2e8f0;
}
.trash-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
.trash-table tr:last-child td {
  border-bottom: none;
}
.item-row {
  display: flex;
  align-items: center;
  gap: 12px;
}
.item-thumb {
  width: 44px;
  height: 58px;
  object-fit: cover;
  border-radius: 6px;
  flex-shrink: 0;
}
.item-thumb.placeholder {
  background: #f1f5f9;
  display: flex;
  align-items: center;
  justify-content: center;
}
.item-title {
  font-weight: 700;
  color: #0f172a;
}
.item-sub {
  font-size: 12px;
  color: #64748b;
  margin-top: 3px;
}
.cell-muted {
  color: #64748b;
}
.row-actions {
  display: flex;
  gap: 8px;
}
.btn-action {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 7px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #334155;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.15s;
}
.btn-action:hover {
  border-color: #94a3b8;
  background: #f8fafc;
}
.btn-action.restore {
  background: #dbeafe;
  border-color: #bfdbfe;
  color: #1d4ed8;
}
.btn-action.restore:hover {
  background: #bfdbfe;
}
.btn-action.delete {
  background: #fee2e2;
  border-color: #fecaca;
  color: #b91c1c;
}
.btn-action.delete:hover {
  background: #fecaca;
}
.empty-state {
  text-align: center;
  padding: 50px 0;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
}
.loading-state {
  text-align: center;
  padding: 50px 0;
  color: #94a3b8;
}
.spinner-cine {
  width: 40px;
  height: 40px;
  margin: 0 auto 12px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #e50914;
  border-radius: 50%;
  animation: spin-cine 0.9s linear infinite;
}
@keyframes spin-cine {
  to {
    transform: rotate(360deg);
  }
}
</style>