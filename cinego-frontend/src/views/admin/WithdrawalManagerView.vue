<template>
  <div class="wdm-container">
    <div class="wdm-header">
      <h2 class="wdm-title">Duyệt Yêu Cầu Rút Tiền Ví</h2>
      <div class="wdm-filters">
        <button :class="{ active: statusFilter === '' }" @click="statusFilter = ''; fetchList()">Tất cả</button>
        <button :class="{ active: statusFilter === 'pending' }" @click="statusFilter = 'pending'; fetchList()">Chờ duyệt</button>
        <button :class="{ active: statusFilter === 'completed' }" @click="statusFilter = 'completed'; fetchList()">Đã chuyển</button>
        <button :class="{ active: statusFilter === 'rejected' }" @click="statusFilter = 'rejected'; fetchList()">Từ chối</button>
      </div>
    </div>

    <div v-if="loading" class="wdm-empty">Đang tải...</div>
    <div v-else-if="list.length === 0" class="wdm-empty">Không có yêu cầu nào.</div>

    <div v-else class="wdm-list">
      <div v-for="w in list" :key="w.id" class="wdm-item">
        <div class="wdm-item-left">
          <p class="wdm-amount">{{ formatCurrency(w.amount) }}</p>
          <p class="wdm-info">
            <strong>{{ w.user?.name }}</strong> ({{ w.user?.email || w.user?.phone || 'N/A' }})
          </p>
          <p class="wdm-info" v-if="w.bank_name !== 'QR Code'">{{ w.bank_name }} · TK {{ w.bank_account }} · {{ w.bank_holder }}</p>
          <div v-if="w.qr_image" style="margin-top: 10px; margin-bottom: 5px;">
            <img :src="getQrImageUrl(w.qr_image)" alt="QR Code" style="max-width: 150px; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;" @click="viewFullImage(w)" />
            <p style="font-size: 11px; color: #64748b; margin-top: 4px;">Click để xem ảnh lớn</p>
          </div>
          <p class="wdm-time">Yêu cầu lúc: {{ formatDateTime(w.created_at) }}</p>
          <p v-if="w.admin_note" class="wdm-note">Ghi chú: {{ w.admin_note }}</p>
        </div>

        <div class="wdm-item-right">
          <span class="wdm-badge" :class="'st-' + w.status">{{ statusLabel(w.status) }}</span>
          <div v-if="w.status === 'pending'" class="wdm-actions">
            <button @click="complete(w)" class="btn-wdm btn-ok">Đã chuyển khoản</button>
            <button @click="reject(w)" class="btn-wdm btn-no">Từ chối</button>
          </div>
          <p v-if="w.processed_at" class="wdm-time">Xử lý: {{ formatDateTime(w.processed_at) }}</p>
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="wdm-pagination">
      <button :disabled="pagination.current_page <= 1" @click="fetchList(pagination.current_page - 1)">‹ Trước</button>
      <span>Trang {{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchList(pagination.current_page + 1)">Sau ›</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Swal from "sweetalert2";
import api from "../../api/axios";

const list = ref([]);
const loading = ref(false);
const statusFilter = ref("");
const pagination = ref({ current_page: 1, last_page: 1 });

const statusLabel = (s) => {
  const labels = { pending: "Chờ duyệt", approved: "Đã duyệt", completed: "Đã chuyển khoản", rejected: "Bị từ chối" };
  return labels[s] || s;
};

const formatCurrency = (val) => parseInt(val || 0).toLocaleString("vi-VN") + "đ";

const formatDateTime = (dateStr) => {
  if (!dateStr) return "";
  return new Date(dateStr).toLocaleString("vi-VN");
};

const getQrImageUrl = (path) => {
  if (!path) return '';
  return `http://localhost:8000/storage/${path}`;
};

const viewFullImage = (w) => {
  const url = getQrImageUrl(w.qr_image);
  Swal.fire({
    title: 'Chi Tiết Giao Dịch Chuyển Khoản',
    html: `
      <div style="text-align: left; font-size: 14px; line-height: 1.6; color: #334155; margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
        <p style="margin:0 0 5px 0;"><strong>Khách hàng:</strong> ${w.user?.name} (${w.user?.email || w.user?.phone || 'N/A'})</p>
        <p style="margin:0 0 5px 0;"><strong>Số tiền rút:</strong> <span style="color:#ef4444; font-weight:bold; font-size:16px;">${formatCurrency(w.amount)}</span></p>
        <p style="margin:0 0 5px 0;"><strong>Ngân hàng:</strong> ${w.bank_name !== 'QR Code' ? w.bank_name : 'Không nhập (Quét QR)'}</p>
        <p style="margin:0 0 5px 0;"><strong>Số tài khoản:</strong> ${w.bank_account !== 'QR Code' ? w.bank_account : 'Không nhập (Quét QR)'}</p>
        <p style="margin:0;"><strong>Chủ tài khoản:</strong> ${w.bank_holder !== 'QR Code' ? w.bank_holder : 'Không nhập (Quét QR)'}</p>
      </div>
      <div>
        <p style="font-weight: 600; margin-bottom: 10px; color: #0f172a;">Ảnh QR Khách tải lên:</p>
        <img src="${url}" alt="QR Code" style="max-width: 100%; max-height: 50vh; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);" />
      </div>
    `,
    showConfirmButton: true,
    confirmButtonText: 'Đóng',
    confirmButtonColor: '#3b82f6',
    width: '550px',
  });
};

const fetchList = async (page = 1) => {
  loading.value = true;
  try {
    const params = { page };
    if (statusFilter.value) params.status = statusFilter.value;
    const res = await api.get("/admin/wallet/withdrawals", { params });
    list.value = res.data.data?.data || res.data.data || [];
    pagination.value = res.data.data || { current_page: 1, last_page: 1 };
  } catch (err) {
    Swal.fire("Lỗi", err.response?.data?.message || "Không tải được danh sách.", "error");
  } finally {
    loading.value = false;
  }
};

const complete = async (w) => {
  const { isConfirmed } = await Swal.fire({
    title: "Xác nhận đã chuyển khoản",
    html: `Bạn đã chuyển <b>${formatCurrency(w.amount)}</b> cho <b>${w.bank_holder}</b> (${w.bank_name} ${w.bank_account})?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Đã chuyển",
    cancelButtonText: "Hủy",
  });
  if (!isConfirmed) return;
  try {
    await api.post(`/admin/wallet/withdrawals/${w.id}/complete`);
    Swal.fire("Thành công", "Đã xác nhận hoàn tất chuyển khoản.", "success");
    fetchList();
  } catch (err) {
    Swal.fire("Lỗi", err.response?.data?.message || "Lỗi xử lý.", "error");
  }
};

const reject = async (w) => {
  const { value: note, isConfirmed } = await Swal.fire({
    title: "Từ chối yêu cầu rút tiền",
    html: `<p>Tiền sẽ được hoàn lại vào ví khách.</p><p><b>${formatCurrency(w.amount)}</b> cho ${w.bank_holder}</p>`,
    input: "text",
    inputLabel: "Lý do từ chối (tùy chọn)",
    inputPlaceholder: "VD: Sai số tài khoản",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Từ chối",
    cancelButtonText: "Hủy",
  });
  if (!isConfirmed) return;
  try {
    await api.post(`/admin/wallet/withdrawals/${w.id}/reject`, { admin_note: note || null });
    Swal.fire("Thành công", "Đã từ chối và hoàn tiền lại ví cho khách.", "success");
    fetchList();
  } catch (err) {
    Swal.fire("Lỗi", err.response?.data?.message || "Lỗi xử lý.", "error");
  }
};

onMounted(() => fetchList());
</script>

<style scoped>
.wdm-container {
  padding: 24px;
  background: #f8fafc;
  border-radius: 14px;
  color: #0f172a;
}
.wdm-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}
.wdm-title {
  font-size: 20px;
  font-weight: 800;
  margin: 0;
}
.wdm-filters {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.wdm-filters button {
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #475569;
  padding: 6px 14px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
.wdm-filters button.active {
  background: #e50914;
  border-color: #e50914;
  color: #fff;
}
.wdm-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.wdm-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px 20px;
  flex-wrap: wrap;
}
.wdm-amount {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 4px 0;
}
.wdm-info {
  margin: 2px 0;
  font-size: 13px;
  color: #334155;
}
.wdm-time {
  margin: 4px 0 0 0;
  font-size: 12px;
  color: #94a3b8;
}
.wdm-note {
  margin: 6px 0 0 0;
  font-size: 12px;
  color: #b45309;
  background: #fef3c7;
  padding: 4px 8px;
  border-radius: 6px;
  display: inline-block;
}
.wdm-item-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 8px;
}
.wdm-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 999px;
}
.st-pending { background: #fef3c7; color: #b45309; }
.st-approved { background: #dbeafe; color: #1d4ed8; }
.st-completed { background: #dcfce7; color: #166534; }
.st-rejected { background: #fee2e2; color: #991b1b; }
.wdm-actions {
  display: flex;
  gap: 8px;
}
.btn-wdm {
  border: none;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  color: #fff;
}
.btn-ok { background: #16a34a; }
.btn-ok:hover { background: #15803d; }
.btn-no { background: #ef4444; }
.btn-no:hover { background: #dc2626; }
.wdm-empty {
  text-align: center;
  padding: 40px;
  color: #94a3b8;
}
.wdm-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 14px;
  margin-top: 20px;
}
.wdm-pagination button {
  border: 1px solid #cbd5e1;
  background: #fff;
  padding: 6px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}
.wdm-pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>