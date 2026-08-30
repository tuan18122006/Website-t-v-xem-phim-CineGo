<template>
  <div class="admin-loyalty-container" style="padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
      <h2 style="font-weight: 800; color: #1e293b; margin: 0;">QUẢN LÝ TÍCH ĐIỂM KHÁCH HÀNG</h2>
      
      <!-- Ô Tìm kiếm -->
      <input 
        v-model="searchQuery" 
        @input="fetchUsers"
        type="text" 
        placeholder="Tìm theo tên, email, sđt..." 
        style="padding: 8px 16px; border: 1px solid #cbd5e1; border-radius: 6px; width: 280px;"
      />
    </div>

    <!-- Đang tải danh sách -->
    <div v-if="listLoading" class="loading-state">
      <div class="spinner-cine"></div>
      <p>Đang tải danh sách khách hàng...</p>
    </div>

    <!-- Bảng danh sách Khách hàng -->
    <table v-else class="cinego-admin-table" style="width: 100%; border-collapse: collapse; text-align: left;">
      <thead>
        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 13px;">
          <th style="padding: 12px;">KHÁCH HÀNG</th>
          <th style="padding: 12px;">HẠNG THẺ</th>
          <th style="padding: 12px;">TỔNG CHI TIÊU</th>
          <th style="padding: 12px;">ĐIỂM HIỆN CÓ</th>
          <th style="padding: 12px; text-align: center;">HÀNH ĐỘNG</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in userList" :key="user.id" style="border-bottom: 1px solid #f1f5f9;">
          <td style="padding: 12px;">
            <div style="font-weight: 700; color: #0f172a;">{{ user.name }}</div>
            <div style="font-size: 12px; color: #64748b;">{{ user.email }}</div>
          </td>
          <td style="padding: 12px;">
            <span class="tier-badge" :class="tierInfo(user.membership_tier).cls">
              <Star :size="13" style="vertical-align:-2px" /> {{ tierInfo(user.membership_tier).label }}
            </span>
          </td>
          <td style="padding: 12px; font-weight: 600; color: #e11d48;">
            {{ formatPrice(user.total_spent) }} đ
          </td>
          <td style="padding: 12px; font-weight: 800; color: #059669;">
            {{ user.cine_points || 0 }} P
          </td>
          <td style="padding: 12px; text-align: center;">
            <button 
              @click="openAdjustModal(user)"
              style="background: #e11d48; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer;"
            >
              <Settings :size="15" style="vertical-align:-2px" /> Điều chỉnh điểm
            </button>
          </td>
        </tr>
        <tr v-if="userList.length === 0">
          <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">
            Không tìm thấy khách hàng nào.
          </td>
        </tr>
      </tbody>
    </table>

    <!-- MODAL CỘNG / TRỪ ĐIỂM -->
    <div v-if="isModalOpen" style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 999;">
      <div style="background: white; padding: 24px; border-radius: 12px; width: 420px; position: relative;">
        <h3 style="margin-top: 0; color: #e11d48; font-weight: 800;">ĐIỀU CHỈNH ĐIỂM THỦ CÔNG</h3>
        <p style="font-size: 14px; color: #475569; margin-bottom: 15px;">
          Khách hàng: <strong>{{ selectedUser?.name }}</strong> (Hiện có: <span style="color: #059669; font-weight: 700;">{{ selectedUser?.cine_points }} P</span>)
        </p>

        <form @submit.prevent="handleAdjustPoints">
          <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Hành động:</label>
            <select v-model="adjustForm.type" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;">
              <option value="add">Cộng thêm điểm</option>
              <option value="subtract">Trừ bớt điểm</option>
            </select>
          </div>

          <div style="margin-bottom: 12px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Số điểm:</label>
            <input 
              v-model.number="adjustForm.points" 
              type="number" 
              min="1" 
              required 
              style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;" 
            />
          </div>

          <div style="margin-bottom: 20px;">
            <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Lý do điều chỉnh:</label>
            <textarea 
              v-model="adjustForm.reason" 
              placeholder="Ví dụ: Tặng quà sinh nhật, Đền bù sự cố..." 
              required 
              rows="3" 
              style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px;"
            ></textarea>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button 
              type="button" 
              @click="isModalOpen = false" 
              style="background: #cbd5e1; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;"
            >
              Hủy
            </button>
            <button 
              type="submit" 
              :disabled="loading" 
              style="background: #e11d48; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; cursor: pointer;"
            >
              {{ loading ? 'Đang lưu...' : 'XÁC NHẬN' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { getAdminUsersLoyalty, adjustUserPoints } from '../../../src/api/adminLoyalty';
import { Star, Settings } from 'lucide-vue-next';
import { toast } from '../../utils/alert';

const userList = ref([]);
const searchQuery = ref('');
const isModalOpen = ref(false);
const selectedUser = ref(null);
const loading = ref(false);
const listLoading = ref(false);

const adjustForm = ref({
  type: 'add',
  points: 10,
  reason: ''
});

const formatPrice = (price) => new Intl.NumberFormat('vi-VN').format(price || 0);

const tierInfo = (tier) => {
  const raw = tier || 'Bronze';
  const t = raw.toLowerCase();
  const labels = {
    bronze: 'Đồng (Bronze)',
    silver: 'Bạc (Silver)',
    gold: 'Vàng (Gold)',
    diamond: 'Kim Cương (Diamond)',
    vip: 'VIP',
    standard: 'Standard'
  };
  const valid = ['bronze', 'silver', 'gold', 'diamond', 'vip', 'standard'];
  return { label: labels[t] || raw, cls: valid.includes(t) ? 'tier-' + t : 'tier-bronze' };
};

const fetchUsers = async () => {
  listLoading.value = true;
  try {
    const res = await getAdminUsersLoyalty({ search: searchQuery.value });
    userList.value = res.data.data || res.data; 
  } catch (err) {
    console.error("Lỗi lấy danh sách điểm khách hàng:", err);
  } finally {
    listLoading.value = false;
  }
};

const openAdjustModal = (user) => {
  selectedUser.value = user;
  adjustForm.value = { type: 'add', points: 10, reason: '' };
  isModalOpen.value = true;
};

const handleAdjustPoints = async () => {
  if (!selectedUser.value) return;
  loading.value = true;
  try {
    await adjustUserPoints(selectedUser.value.id, adjustForm.value);
    toast('Điều chỉnh điểm thành công!', 'success');
    isModalOpen.value = false;
    fetchUsers();
  } catch (err) {
    toast(err.response?.data?.message || 'Có lỗi xảy ra!', 'error');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
.tier-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
.tier-bronze { background: linear-gradient(135deg, #c98a52, #8c4f22); }
.tier-silver { background: linear-gradient(135deg, #a4b2c6, #4f5f76); }
.tier-gold { background: linear-gradient(135deg, #f6d365, #b8860b); }
.tier-diamond { background: linear-gradient(135deg, #67e8f9, #0891b2); }
.tier-vip { background: linear-gradient(135deg, #8b5cf6, #4c1d95); }
.tier-standard { background: linear-gradient(135deg, #94a3b8, #475569); }
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
</style>