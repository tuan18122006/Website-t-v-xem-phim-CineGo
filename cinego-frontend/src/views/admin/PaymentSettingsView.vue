<template>
  <div class="payment-settings-view">
    <div class="view-header">
      <h2 class="view-title">Cấu Hình Thanh Toán</h2>
      <p class="view-subtitle">Thiết lập tài khoản ngân hàng để nhận tiền qua mã VietQR</p>
    </div>

    <div class="settings-card glass-panel">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Đang tải cấu hình...</p>
      </div>

      <form v-else @submit.prevent="saveSettings" class="settings-form">
        <div class="form-group-custom">
          <label class="form-label-custom">Ngân hàng (Mã Ngân Hàng)</label>
          <input 
            type="text" 
            v-model="form.vietqr_bank_id" 
            class="cinego-input" 
            placeholder="VD: MB, VCB, TCB, VPB..." 
            required 
          />
          <small class="help-text">Nhập mã tên viết tắt của ngân hàng (Ví dụ: MB cho MBBank, VCB cho Vietcombank)</small>
        </div>

        <div class="form-group-custom">
          <label class="form-label-custom">Số tài khoản</label>
          <input 
            type="text" 
            v-model="form.vietqr_account_no" 
            class="cinego-input" 
            placeholder="Nhập số tài khoản ngân hàng" 
            required 
          />
        </div>

        <div class="form-group-custom">
          <label class="form-label-custom">Tên chủ tài khoản</label>
          <input 
            type="text" 
            v-model="form.vietqr_account_name" 
            class="cinego-input" 
            placeholder="VD: NGUYEN VAN A" 
            required 
          />
          <small class="help-text">Viết hoa không dấu, đúng như tên hiển thị trên thẻ/ứng dụng</small>
        </div>

        <div class="preview-section" v-if="form.vietqr_bank_id && form.vietqr_account_no">
          <h3 class="preview-title">Xem trước mã QR:</h3>
          <div class="qr-preview-wrapper">
            <img :src="previewUrl" alt="QR Preview" class="qr-preview-img" @error="handleImageError" />
          </div>
          <p class="preview-note">Quét thử mã QR trên bằng ứng dụng ngân hàng để kiểm tra xem đã lên đúng thông tin chưa.</p>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-cinego-main" :disabled="saving">
            {{ saving ? 'Đang lưu...' : 'LƯU CẤU HÌNH' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '../../api/axios';
import { toast } from '../../utils/alert';

const loading = ref(true);
const saving = ref(false);

const form = ref({
  vietqr_bank_id: '',
  vietqr_account_no: '',
  vietqr_account_name: ''
});

const previewUrl = computed(() => {
  const bank = form.value.vietqr_bank_id || 'MB';
  const acc = form.value.vietqr_account_no || '0';
  const name = form.value.vietqr_account_name || '';
  return `https://img.vietqr.io/image/${bank}-${acc}-compact2.png?amount=10000&addInfo=Test&accountName=${encodeURIComponent(name)}`;
});

const handleImageError = (e) => {
  console.error("Mã ngân hàng có thể không hợp lệ");
};

const fetchSettings = async () => {
  try {
    const res = await api.get('/settings/payment');
    if (res.data.success) {
      form.value = res.data.data;
    }
  } catch (error) {
    toast('Không thể tải cấu hình', 'error');
    console.error(error);
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const res = await api.post('/admin/settings/payment', form.value);
    if (res.data.success) {
      toast('Lưu cấu hình thành công', 'success');
    }
  } catch (error) {
    toast(error.response?.data?.message || 'Có lỗi khi lưu cấu hình', 'error');
    console.error(error);
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchSettings();
});
</script>

<style scoped>
.payment-settings-view {
  padding: 20px;
  color: #333;
}

.view-header {
  margin-bottom: 30px;
}

.view-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: var(--accent-pink, #d82d8b);
}

.view-subtitle {
  color: #64748b;
  margin: 0;
  font-size: 15px;
}

.settings-card {
  background: white;
  border-radius: 12px;
  padding: 30px;
  max-width: 600px;
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
}

.form-group-custom {
  margin-bottom: 20px;
}

.form-label-custom {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #334155;
  font-size: 14px;
}

.cinego-input {
  width: 100%;
  padding: 10px 15px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #333;
  font-size: 14px;
  transition: all 0.3s ease;
}

.cinego-input:focus {
  outline: none;
  border-color: var(--accent-pink, #d82d8b);
  background: white;
  box-shadow: 0 0 0 3px rgba(216, 45, 139, 0.1);
}

.help-text {
  display: block;
  margin-top: 6px;
  color: #888;
  font-size: 13px;
  font-style: italic;
}

.preview-section {
  margin-top: 30px;
  padding-top: 25px;
  border-top: 1px solid #e2e8f0;
}

.preview-title {
  font-size: 15px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #334155;
}

.qr-preview-wrapper {
  background: white;
  padding: 10px;
  border-radius: 12px;
  display: inline-block;
  margin-bottom: 10px;
  border: 1px solid #e2e8f0;
}

.qr-preview-img {
  max-width: 250px;
  height: auto;
  border-radius: 8px;
}

.preview-note {
  color: #ff9800;
  font-size: 14px;
}

.form-actions {
  margin-top: 25px;
  display: flex;
  justify-content: flex-end;
}

.btn-cinego-main {
  background: var(--accent-pink, #d82d8b);
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cinego-main:hover:not(:disabled) {
  background: var(--accent-violet, #8b5cf6);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(216, 45, 139, 0.2);
}

.btn-cinego-main:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-state {
  text-align: center;
  padding: 40px 0;
  color: #64748b;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--accent-pink, #d82d8b);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 15px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
