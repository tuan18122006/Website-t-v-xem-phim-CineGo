<template>
  <div class="action-logs-tab-content">
    <div class="glass-panel">
      <div class="panel-header">
        <div>
          <h3>Lịch sử hoạt động</h3>
          <p>Theo dõi các thay đổi trên hệ thống: ai cập nhật, thời gian và nội dung.</p>
        </div>
        <button class="btn-export" @click="loadLogs"><RefreshCw :size="15" style="vertical-align:-2px" /> Tải lại</button>
      </div>

      <div class="logs-toolbar">
        <input v-model="search" type="text" placeholder="Tìm theo hành động, người dùng…" @keyup.enter="loadLogs" />
        <select v-model="actionFilter" @change="loadLogs">
          <option value="">Tất cả hành động</option>
          <option value="request_refund">Yêu cầu hoàn tiền</option>
          <option value="approve_refund">Phê duyệt hoàn tiền</option>
          <option value="reject_refund">Từ chối hoàn tiền</option>
          <option value="edit_movie">Chỉnh sửa phim</option>
          <option value="create_movie">Thêm phim</option>
          <option value="edit_showtime">Chỉnh sửa suất chiếu</option>
          <option value="create_showtime">Thêm suất chiếu</option>
          <option value="edit_price">Chỉnh sửa giá</option>
          <option value="change_role">Đổi vai trò</option>
          <option value="update_status">Cập nhật trạng thái</option>
        </select>
        <input v-model="fromDate" type="date" />
        <span>đến</span>
        <input v-model="toDate" type="date" />
        <button class="orders-filter-btn" @click="loadLogs">Lọc</button>
      </div>

      <div v-if="loading" class="lookup-state">
        <div class="lookup-spinner"></div>
        <p>Đang tải dữ liệu…</p>
      </div>

      <div v-else-if="logs.length === 0" class="lookup-state">
        <span class="lookup-state__art"><History :size="20" /></span>
        <h4>Chưa có lịch sử hoạt động</h4>
      </div>

      <div v-else class="orders-table-wrap">
        <table class="report-table lookup-table">
          <thead>
            <tr>
              <th>Thời gian</th>
              <th>Người thực hiện</th>
              <th>Hành động</th>
              <th>Đối tượng</th>
              <th>Chi tiết</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="log in logs" :key="log.id">
              <td>{{ formatDateTime(log.created_at) }}</td>
              <td>
                <div class="user-cell">
                  <span class="avatar-sm">{{ initials(log.user?.name) }}</span>
                  {{ log.user?.name || '—' }}
                </div>
              </td>
              <td><span class="action-badge" :class="'action-badge--' + log.action">{{ actionLabel(log.action) }}</span></td>
              <td>{{ log.target_type ? targetTypeLabel(log.target_type) + ' #' + log.target_id : '—' }}</td>
              <td>
                <button v-if="log.details && Object.keys(log.details).length" class="btn-detail" @click="showDetails(log)">Xem</button>
                <span v-else>—</span>
              </td>
              <td class="mono">{{ log.ip_address }}</td>
            </tr>
          </tbody>
        </table>

        <div class="pagination">
          <button :disabled="!prevPageUrl" @click="goPage(prevPageUrl)">Trước</button>
          <span>Trang {{ currentPage }} / {{ totalPages }}</span>
          <button :disabled="!nextPageUrl" @click="goPage(nextPageUrl)">Sau</button>
        </div>
      </div>
    </div>

    <transition name="modal-fade">
      <div v-if="showDetailModal" class="modal-overlay" @click.self="showDetailModal = false">
        <div class="modal-box">
          <div class="modal-box__head">
            <h3>Chi tiết thay đổi</h3>
            <button class="modal-close" @click="showDetailModal = false">&times;</button>
          </div>
          <div class="modal-box__body">
            <div class="detail-grid">
              <div class="detail-item">
                <span>Thời gian</span>
                <strong>{{ formatDateTime(selectedLog.created_at) }}</strong>
              </div>
              <div class="detail-item">
                <span>Người thực hiện</span>
                <strong>{{ selectedLog.user?.name }} ({{ selectedLog.user?.email }})</strong>
              </div>
              <div class="detail-item">
                <span>Hành động</span>
                <strong>{{ actionLabel(selectedLog.action) }}</strong>
              </div>
              <div class="detail-item">
                <span>Đối tượng</span>
                <strong>{{ targetTypeLabel(selectedLog.target_type) }} #{{ selectedLog.target_id }}</strong>
              </div>
              <div class="detail-item">
                <span>IP</span>
                <strong class="mono">{{ selectedLog.ip_address }}</strong>
              </div>
            </div>
            <div v-if="selectedLog.details" class="detail-json">
              <h4>Nội dung chi tiết</h4>
              <pre>{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/axios';
import { RefreshCw, History } from 'lucide-vue-next';

const logs = ref([]);
const loading = ref(false);
const search = ref('');
const actionFilter = ref('');
const fromDate = ref('');
const toDate = ref('');
const currentPage = ref(1);
const totalPages = ref(1);
const prevPageUrl = ref(null);
const nextPageUrl = ref(null);

const showDetailModal = ref(false);
const selectedLog = ref({});

const formatDateTime = (dt) => {
  if (!dt) return '—';
  const d = new Date(dt);
  return d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' });
};

const initials = (name) => {
  if (!name) return '?';
  const parts = name.split(' ');
  return (parts[0][0] + (parts[parts.length - 1][0] || '')).toUpperCase();
};

const actionLabel = (action) => {
  const map = {
    request_refund: 'Yêu cầu hoàn tiền',
    approve_refund: 'Phê duyệt hoàn tiền',
    reject_refund: 'Từ chối hoàn tiền',
    edit_movie: 'Chỉnh sửa phim',
    create_movie: 'Thêm phim',
    edit_showtime: 'Chỉnh sửa suất chiếu',
    create_showtime: 'Thêm suất chiếu',
    edit_price: 'Chỉnh sửa giá',
    change_role: 'Đổi vai trò',
    update_status: 'Cập nhật trạng thái',
  };
  return map[action] || action;
};

const targetTypeLabel = (type) => {
  const map = {
    bookings: 'Đơn hàng',
    movies: 'Phim',
    showtimes: 'Suất chiếu',
    users: 'Người dùng',
    pricing: 'Bảng giá',
    refunds: 'Hoàn tiền',
  };
  return map[type] || type;
};

const loadLogs = async (url) => {
  loading.value = true;
  try {
    const params = { per_page: 15 };
    if (search.value) params.search = search.value;
    if (actionFilter.value) params.action = actionFilter.value;
    if (fromDate.value) params.from_date = fromDate.value;
    if (toDate.value) params.to_date = toDate.value;

    const res = url ? await api.get(url) : await api.get('/admin/action-logs', { params });
    const data = res.data.data;
    logs.value = data.data;
    currentPage.value = data.current_page;
    totalPages.value = data.last_page;
    prevPageUrl.value = data.prev_page_url;
    nextPageUrl.value = data.next_page_url;
  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const goPage = (url) => {
  if (url) {
    const path = new URL(url).pathname + new URL(url).search;
    loadLogs(path);
  }
};

const showDetails = (log) => {
  selectedLog.value = log;
  showDetailModal.value = true;
};

onMounted(() => loadLogs());
</script>

<style scoped>
.action-logs-tab-content {
  padding: 0 0 40px;
}

.glass-panel {
  background: #fff;
  border-radius: 16px;
  padding: 28px 32px;
  box-shadow: 0 2px 12px rgba(0,0,0,.06);
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
}

.panel-header h3 {
  margin: 0 0 4px;
  font-size: 18px;
  color: #111827;
}

.panel-header p {
  margin: 0;
  font-size: 13px;
  color: #6b7280;
}

.btn-export {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #374151;
  font-size: 13px;
  cursor: pointer;
  transition: background .15s;
}

.btn-export:hover { background: #f3f4f6; }

.logs-toolbar {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.logs-toolbar input,
.logs-toolbar select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 13px;
  background: #fff;
}

.logs-toolbar input[type="text"] { width: 240px; }
.logs-toolbar span { color: #6b7280; font-size: 13px; }

.orders-filter-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 8px;
  background: #e71a0f;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.orders-filter-btn:hover { background: #c4140b; }

.lookup-state {
  text-align: center;
  padding: 48px 0;
  color: #6b7280;
}

.lookup-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #e71a0f;
  border-radius: 50%;
  animation: spin .8s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin { to { transform: rotate(360deg); } }

.lookup-state__art {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: #f3f4f6;
  margin-bottom: 12px;
}

.lookup-state h4 { margin: 0; font-size: 15px; color: #374151; }

.orders-table-wrap { overflow-x: auto; }

.report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.report-table th {
  text-align: left;
  padding: 10px 12px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  color: #6b7280;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: .3px;
}

.report-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f3f4f6;
  color: #374151;
}

.report-table tbody tr:hover { background: #fafafa; }

.user-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.avatar-sm {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #e71a0f;
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}

.action-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.action-badge--request_refund,
.action-badge--approve_refund,
.action-badge--reject_refund {
  background: #fef3c7;
  color: #92400e;
}

.action-badge--edit_movie,
.action-badge--create_movie,
.action-badge--edit_showtime,
.action-badge--create_showtime {
  background: #dbeafe;
  color: #1e40af;
}

.action-badge--edit_price { background: #ede9fe; color: #5b21b6; }
.action-badge--change_role { background: #fce7f3; color: #9d174d; }
.action-badge--update_status { background: #d1fae5; color: #065f46; }

.btn-detail {
  padding: 3px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #fff;
  color: #374151;
  font-size: 12px;
  cursor: pointer;
}

.btn-detail:hover { background: #f3f4f6; }

.mono { font-family: 'Fira Code', 'Consolas', monospace; font-size: 12px; }

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 16px;
  font-size: 13px;
  color: #6b7280;
}

.pagination button {
  padding: 6px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: #fff;
  color: #374151;
  font-size: 13px;
  cursor: pointer;
}

.pagination button:hover:not(:disabled) { background: #f3f4f6; }
.pagination button:disabled { opacity: .4; cursor: not-allowed; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-box {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 560px;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
}

.modal-box__head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-box__head h3 { margin: 0; font-size: 16px; }

.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: #f3f4f6;
  border-radius: 8px;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover { background: #e5e7eb; }

.modal-box__body { padding: 20px 24px; }

.detail-grid {
  display: grid;
  gap: 14px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-item span {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: .3px;
}

.detail-item strong {
  font-size: 14px;
  color: #111827;
}

.detail-json {
  margin-top: 16px;
}

.detail-json h4 {
  margin: 0 0 8px;
  font-size: 13px;
  color: #6b7280;
}

.detail-json pre {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
  font-size: 12px;
  font-family: 'Fira Code', 'Consolas', monospace;
  overflow-x: auto;
  margin: 0;
}

.modal-fade-enter-active,
.modal-fade-leave-active { transition: opacity .2s; }
.modal-fade-enter-from,
.modal-fade-leave-to { opacity: 0; }
</style>
