<template>
  <div class="admin-movies-view-container">
    <div class="glass-panel header-card">
      <div class="header-content">
        <h2 class="title-cine"><Pencil :size="15" style="vertical-align:-2px" /> Cấu hình ghế: {{ roomName }}</h2>
        <div class="action-buttons">
          <button @click="goBack" class="btn-secondary-cine">Quay lại</button>
          <button @click="saveSeats" class="btn-primary-cine">Lưu Thay Đổi</button>
        </div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar-card glass-panel">
      <div class="toolbar-info">
        <div class="toolbar-stats">
          <span><Circle :size="15" fill="currentColor" style="color: #22c55e" /> Thường: <strong>{{ seatStats.standard }}</strong></span>
          <span><Circle :size="15" fill="currentColor" style="color: #ef4444" /> VIP: <strong>{{ seatStats.vip }}</strong></span>
          <span><Heart :size="15" fill="currentColor" style="color: #ec4899" /> Đôi: <strong>{{ seatStats.couple }}</strong></span>
          <span><Circle :size="15" style="color: #e2e8f0" /> Trống/Xóa: <strong>{{ seatStats.hiddenOrDeleted }}</strong></span>
        </div>
        <div style="margin-top: 8px;">Đang chọn: <strong>{{ currentSelectedIds.length }} ghế</strong></div>
      </div>
      <div class="toolbar-actions" :class="{ disabled: currentSelectedIds.length === 0 }">
        <button @click="changeType('standard')" class="tool-btn standard-btn">Trở thành Ghế Thường</button>
        <button @click="changeType('vip')" class="tool-btn vip-btn">Trở thành VIP</button>
        <button @click="changeType('couple')" class="tool-btn couple-btn">Trở thành Ghế Đôi</button>
        <button @click="changeType('hidden')" class="tool-btn hidden-btn">Khoảng Trống</button>
        <button @click="changeType('deleted')" class="tool-btn deleted-btn">Xóa hẳn Ô ghế</button>
        <button @click="addGapCol" class="tool-btn gap-col-btn">Chèn Lối đi Dọc</button>
        <button @click="addGapRow" class="tool-btn gap-row-btn">Chèn Lối đi Ngang</button>
      </div>
      <button @click="removeGaps" class="tool-btn gap-clear-btn" v-if="layout.gap_cols.length || layout.gap_rows.length">Xóa toàn bộ Lối đi</button>
    </div>

    <div class="glass-panel editor-card">
      <div v-if="loading" class="loading-state">Đang tải sơ đồ rạp...</div>
      <div v-else class="seat-map-wrapper">
        <!-- CHUYỀN SỰ KIỆN LÊN ĐỂ CẬP NHẬT TOOLBAR -->
        <SeatMap 
          ref="seatMapRef"
          :seats="seats" 
          :layout="layout"
          mode="admin" 
          @selection-changed="handleSelectionChanged"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import SeatMap from '../../components/SeatMap.vue';
import api from '../../api/axios';
import { toast } from '../../utils/alert';
import { Pencil, Circle, Heart } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const roomId = route.params.id;
const seats = ref([]);
const layout = ref({ gap_cols: [], gap_rows: [] });
const roomName = ref('...');
const loading = ref(true);

const seatMapRef = ref(null);
const currentSelectedIds = ref([]);

const fetchRoomDetails = async () => {
  try {
    const res = await api.get(`/admin/rooms/${roomId}`);
    roomName.value = res.data.data.room.name;
    layout.value = res.data.data.room.layout || { gap_cols: [], gap_rows: [] };
    seats.value = res.data.data.seats;
    loading.value = false;
  } catch (err) { console.error(err); }
};

const handleSelectionChanged = (selectedIds) => {
  currentSelectedIds.value = selectedIds;
};

// Compute số lượng ghế
const seatStats = computed(() => {
  let standard = 0, vip = 0, couple = 0, hiddenOrDeleted = 0;
  seats.value.forEach(s => {
    if (s.type === 'standard') standard++;
    else if (s.type === 'vip') vip++;
    else if (s.type === 'couple') couple++;
    else if (s.type === 'hidden' || s.type === 'deleted') hiddenOrDeleted++;
  });
  return { standard, vip, couple, hiddenOrDeleted };
});

// Chuyển đổi loại ghế cho toàn bộ ghế đang quét chọn
const changeType = (targetType) => {
  if (currentSelectedIds.value.length === 0) return;

  // Sắp xếp các ghế được chọn từ trái sang phải (theo row và number)
  // để đảm bảo logic gộp ghế (từ trái qua phải) hoạt động đúng kể cả khi người dùng quét chuột ngược từ phải sang trái.
  const sortedSelectedIds = [...currentSelectedIds.value].sort((a, b) => {
    let seatA = seats.value.find(s => s.id === a);
    let seatB = seats.value.find(s => s.id === b);
    if (!seatA || !seatB) return 0;
    if (seatA.row === seatB.row) return seatA.number - seatB.number;
    return seatA.row.localeCompare(seatB.row);
  });

  if (targetType === 'standard' || targetType === 'vip' || targetType === 'hidden' || targetType === 'deleted') {
    sortedSelectedIds.forEach(id => {
      let seat = seats.value.find(s => s.id === id);
      if (seat) {
        // Nếu ghế này đang là ghế đôi, cần phục hồi ghế bị ẩn phía sau
        if (seat.type === 'couple') {
          let nextSeat = seats.value.find(s => s.row === seat.row && s.number === seat.number + 1);
          if (nextSeat && nextSeat.type === 'couple_hidden') {
            nextSeat.type = targetType;
          }
        }
        seat.type = targetType;
      }
    });
  } else if (targetType === 'couple') {
    // Thu thập các ghế phụ (hidden) của những ghế đôi đang được chọn để gộp chung vào mảng xử lý
    let expandedIds = new Set(sortedSelectedIds);
    sortedSelectedIds.forEach(id => {
      let seat = seats.value.find(s => s.id === id);
      if (seat && seat.type === 'couple') {
        let nextSeat = seats.value.find(s => s.row === seat.row && s.number === seat.number + 1);
        if (nextSeat && nextSeat.type === 'couple_hidden') {
          expandedIds.add(nextSeat.id);
        }
      }
    });

    // Chuyển toàn bộ vùng chọn (đã mở rộng) về Standard trước khi bắt cặp lại từ trái sang phải
    expandedIds.forEach(id => {
      let seat = seats.value.find(s => s.id === id);
      if (seat) seat.type = 'standard';
    });

    // Sắp xếp lại vùng chọn đã mở rộng
    let finalSortedIds = [...expandedIds].sort((a, b) => {
      let seatA = seats.value.find(s => s.id === a);
      let seatB = seats.value.find(s => s.id === b);
      if (!seatA || !seatB) return 0;
      if (seatA.row === seatB.row) return seatA.number - seatB.number;
      return seatA.row.localeCompare(seatB.row);
    });

    finalSortedIds.forEach(id => {
      let seat = seats.value.find(s => s.id === id);
      if (seat && seat.type !== 'couple' && seat.type !== 'couple_hidden') {
        let nextSeat = seats.value.find(s => s.row === seat.row && s.number === seat.number + 1);
        if (nextSeat) {
          // Chỉ ghép cặp nếu ghế kế tiếp cũng nằm trong vùng chọn (khi chọn nhiều ghế)
          if (finalSortedIds.length === 1 || finalSortedIds.includes(nextSeat.id)) {
            seat.type = 'couple';
            nextSeat.type = 'couple_hidden';
          }
        } else {
          // Báo lỗi nếu cố tình click lẻ 1 ghế ở cuối hàng
          if (finalSortedIds.length === 1) {
            toast(`Ghế ${seat.row}${seat.number} không thể gộp thành ghế đôi vì nằm ở cuối hàng.`, "warning");
          }
        }
      }
    });
  }

  // Bỏ chọn (Reset lại mảng)
  if (seatMapRef.value) {
    seatMapRef.value.clearSelection();
  }
};

const addGapCol = () => {
  if (currentSelectedIds.value.length === 0) return;
  const cols = new Set();
  currentSelectedIds.value.forEach(id => {
    const seat = seats.value.find(s => s.id === id);
    if(seat) cols.add(seat.number);
  });
  cols.forEach(c => {
    if(!layout.value.gap_cols.includes(c)) layout.value.gap_cols.push(c);
  });
  if (seatMapRef.value) seatMapRef.value.clearSelection();
};

const addGapRow = () => {
  if (currentSelectedIds.value.length === 0) return;
  const rows = new Set();
  currentSelectedIds.value.forEach(id => {
    const seat = seats.value.find(s => s.id === id);
    if(seat) rows.add(seat.row);
  });
  rows.forEach(r => {
    if(!layout.value.gap_rows.includes(r)) layout.value.gap_rows.push(r);
  });
  if (seatMapRef.value) seatMapRef.value.clearSelection();
};

const removeGaps = () => {
  layout.value.gap_cols = [];
  layout.value.gap_rows = [];
};

const saveSeats = async () => {
  if (seatStats.value.standard + seatStats.value.vip + seatStats.value.couple === 0) {
    toast("Rạp chiếu phải có ít nhất 1 ghế hợp lệ để mở bán!", "error");
    return;
  }

  try {
    await api.put(`/admin/rooms/${roomId}/update-seat-map`, {
      seats: seats.value.map(s => ({ id: s.id, type: s.type })),
      layout: layout.value
    });
    toast("Lưu sơ đồ thành công!");
    router.push({ name: 'admin-dashboard' }); 
  } catch (err) { 
    const msg = err.response?.data?.message || "Lưu thất bại!";
    toast(msg, "error"); 
  }
};

const goBack = () => router.push({ name: 'admin-dashboard' });

onMounted(fetchRoomDetails);
</script>

<style scoped>
.header-card { margin-bottom: 20px; padding: 20px; }
.header-content { display: flex; justify-content: space-between; align-items: center; }

/* TOOLBAR */
.toolbar-card {
  margin-bottom: 20px; padding: 15px 20px;
  display: flex; justify-content: space-between; align-items: center;
  background: linear-gradient(to right, #1e293b, #0f172a);
}
.toolbar-info {
  font-size: 14px; color: #f8fafc;
}
.toolbar-stats { display: flex; gap: 12px; font-size: 13px; }
.toolbar-stats span { display: inline-flex; align-items: center; gap: 4px; }
.toolbar-info strong { color: #fbbf24; font-size: 15px; }

.toolbar-actions { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
.toolbar-actions.disabled { opacity: 0.5; pointer-events: none; filter: grayscale(100%); }

.tool-btn {
  padding: 10px 15px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; color: white;
  transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.3); font-size: 13px;
}
.tool-btn:hover { transform: translateY(-2px); }

.standard-btn { background: linear-gradient(145deg, #4b5563, #374151); border: 1px solid #6b7280; }
.vip-btn { background: linear-gradient(145deg, #ef4444, #b91c1c); border: 1px solid #f87171; }
.couple-btn { background: linear-gradient(145deg, #ec4899, #be185d); border: 1px solid #f472b6; }
.hidden-btn { background: transparent; border: 2px dashed #64748b; color: #cbd5e1; }
.deleted-btn { background: rgba(239, 68, 68, 0.2); border: 1px solid #fca5a5; color: #fca5a5; }
.gap-col-btn { background: linear-gradient(145deg, #3b82f6, #1d4ed8); border: 1px solid #60a5fa; }
.gap-row-btn { background: linear-gradient(145deg, #8b5cf6, #6d28d9); border: 1px solid #a78bfa; }
.gap-clear-btn { background: linear-gradient(145deg, #f59e0b, #d97706); border: 1px solid #fbbf24; margin-left: 20px;}

.editor-card { padding: 30px; overflow-x: auto; min-height: 500px; }
.seat-map-wrapper { display: flex; justify-content: center; }
.action-buttons { display: flex; gap: 10px; }
.btn-secondary-cine { background: #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
.btn-primary-cine { background-color: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
</style>