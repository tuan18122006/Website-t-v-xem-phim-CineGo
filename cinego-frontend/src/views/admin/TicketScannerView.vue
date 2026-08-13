<template>
  <div class="scan-tab glass-panel" style="margin-top: 20px;">
    <div class="scan-container">
      <div class="scan-icon-wrapper">
        <span class="scan-icon"><Camera :size="20" /></span>
      </div>
      <h3>Soát vé qua Mã QR</h3>
      <p>Nhập mã đặt vé bên dưới hoặc dùng thiết bị quét mã QR để quét trực tiếp vé của khách hàng.</p>
      
      <form class="scan-form" @submit.prevent="handleScan">
        <input 
          ref="scanInput" 
          v-model="scanCode" 
          type="text" 
          class="scan-input" 
          placeholder="Quét mã QR hoặc nhập mã vé (VD: CG-123456)..." 
        />
        <div class="scan-actions">
          <button type="submit" class="btn-scan" :disabled="!scanCode">Xác Nhận</button>
          <button type="button" class="btn-camera" @click="showCamera = !showCamera">
            {{ showCamera ? 'Tắt Camera' : 'Bật Camera' }}
          </button>
          <label class="btn-camera" style="display: flex; align-items: center; justify-content: center; cursor: pointer; margin: 0;">
            Tải Ảnh Lên
            <qrcode-capture @detect="onDetect" style="display: none;"></qrcode-capture>
          </label>
        </div>
      </form>

      <div v-if="showCamera" class="camera-wrapper">
        <qrcode-stream @detect="onDetect"></qrcode-stream>
      </div>

      <!-- Kết quả quét -->
      <div v-if="scanResult" class="scan-result" :class="scanResult.status">
        <h4 v-if="scanResult.status === 'success'"><CheckCircle2 :size="15" style="vertical-align:-2px" /> Vé Hợp Lệ!</h4>
        <h4 v-else><XCircle :size="15" style="vertical-align:-2px" /> Lỗi Xác Nhận!</h4>
        <p>{{ scanResult.message }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue';
import api from '../../api/axios';
import { Camera, CheckCircle2, XCircle } from 'lucide-vue-next';
import { QrcodeStream, QrcodeCapture } from 'vue-qrcode-reader';

const scanCode = ref('');
const scanInput = ref(null);
const scanResult = ref(null);
const showCamera = ref(false);

const onDetect = (detectedCodes) => {
  if (detectedCodes && detectedCodes.length > 0) {
    let rawValue = detectedCodes[0].rawValue;
    try {
      const url = new URL(rawValue);
      const scanParam = url.searchParams.get('scan');
      if (scanParam) rawValue = scanParam;
    } catch(e) {}
    
    scanCode.value = rawValue;
    showCamera.value = false;
    handleScan();
  }
};

const handleScan = async () => {
  if (!scanCode.value) return;
  scanResult.value = null;
  
  try {
    const res = await api.post('/staff/bookings/verify', { code: scanCode.value });
    scanResult.value = {
      status: 'success',
      message: `Soát vé thành công cho mã đơn ${scanCode.value}. Chúc quý khách xem phim vui vẻ!`
    };
  } catch (err) {
    scanResult.value = {
      status: 'error',
      message: err.response?.data?.message || 'Mã vé không hợp lệ hoặc đã được sử dụng.'
    };
  } finally {
    scanCode.value = '';
    if (scanInput.value) {
      scanInput.value.focus();
    }
  }
};

onMounted(() => {
  if (scanInput.value) {
    scanInput.value.focus();
  }
});
</script>

<style scoped>
/* QUÉT MÃ QR STYLES */
.scan-tab { padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
.scan-container { max-width: 600px; margin: 0 auto; text-align: center; }
.scan-icon-wrapper { width: 64px; height: 64px; background: #fde2ef; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; border: 2px dashed var(--accent-pink); }
.scan-icon { font-size: 32px; }
.scan-container h3 { font-size: 24px; color: #1e293b; margin-bottom: 8px; }
.scan-container p { color: #64748b; margin-bottom: 24px; }
.scan-form { display: flex; flex-direction: column; gap: 16px; }
.scan-input { width: 100%; padding: 14px 20px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 16px; text-align: center; font-weight: 600; transition: all 0.2s; }
.scan-input:focus { outline: none; border-color: var(--accent-pink); box-shadow: 0 0 0 4px rgba(216, 45, 139, 0.1); }
.scan-actions { display: flex; gap: 12px; justify-content: center; }
.btn-scan { background: var(--accent-pink); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.btn-scan:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-camera { background: white; color: var(--accent-pink); border: 1px solid var(--accent-pink); padding: 12px 24px; border-radius: 8px; font-weight: 700; cursor: pointer; }
.camera-wrapper { margin-top: 24px; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; }
.scan-result { margin-top: 24px; padding: 20px; border-radius: 10px; animation: slideUp 0.3s ease; }
.scan-result.success { background: #ecfdf5; border: 1px solid #10b981; color: #065f46; }
.scan-result.error { background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; }
.scan-result h4 { font-size: 18px; margin-bottom: 4px; }
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
