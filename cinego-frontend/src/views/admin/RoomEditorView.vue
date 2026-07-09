<template>
  <div class="admin-movies-view-container">
    <div class="glass-panel header-card">
      <div class="header-content">
        <h2 class="title-cine">✏️ Cấu hình ghế: {{ roomName }}</h2>
        <div class="action-buttons">
          <button @click="goBack" class="btn-secondary-cine">Quay lại</button>
          <button @click="saveSeats" class="btn-primary-cine">Lưu Thay Đổi</button>
        </div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar-card glass-panel">
      <div class="toolbar-info">
        Đang chọn: <strong>{{ currentSelectedIds.length }} ghế</strong>
      </div>
      <div class="toolbar-actions" :class="{ disabled: currentSelectedIds.length === 0 }">
        <button @click="changeType('standard')" class="tool-btn standard-btn">Trở thành Ghế Thường</button>
        <button @click="changeType('vip')" class="tool-btn vip-btn">Trở thành VIP</button>
        <button @click="changeType('couple')" class="tool-btn couple-btn">Trở thành Ghế Đôi</button>
        <button @click="changeType('hidden')" class="tool-btn hidden-btn">Khoảng Trống Bỏ Đi</button>
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
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import SeatMap from '../../components/SeatMap.vue';
import api from '../../api/axios';
import { toast } from '../../utils/alert';

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

// Thuật toán dọn dẹp các "Ghế Đôi Nửa Cứng" bị mồ côi
const cleanupCoupleHidden = () => {
  // 1. Quét tìm tất cả ghế 'couple', ép ghế liền kề bên phải thành 'couple_hidden'
  for (let i = 0; i < seats.value.length; i++) {
    let seat = seats.value[i];
    if (seat.type === 'couple') {
      let nextSeat = seats.value.find(s => s.row === seat.row && s.number === seat.number + 1);
      if (!nextSeat) {
        seat.type = 'standard'; // Không có cặp -> Trở thành ghế thường
      } else if (nextSeat.type !== 'couple_hidden') {
        nextSeat.type = 'couple_hidden'; // Ép ghế cạnh tàng hình để nhường chỗ cho span 2
      }
    }
  }
  
  // 2. Quét tìm tất cả 'couple_hidden', nếu ghế bên trái nó KHÔNG phải 'couple' -> Nó bị mồ côi
  for (let i = 0; i < seats.value.length; i++) {
    let seat = seats.value[i];
    if (seat.type === 'couple_hidden') {
      let prevSeat = seats.value.find(s => s.row === seat.row && s.number === seat.number - 1);
      if (!prevSeat || prevSeat.type !== 'couple') {
        seat.type = 'standard'; // Phục hồi lại thành ghế thường
      }
    }
  }
};

// Chuyển đổi loại ghế cho toàn bộ ghế đang quét chọn
const changeType = (targetType) => {
  if (currentSelectedIds.value.length === 0) return;

  if (targetType === 'standard' || targetType === 'vip' || targetType === 'hidden') {
    currentSelectedIds.value.forEach(id => {
      let seat = seats.value.find(s => s.id === id);
      if (seat) seat.type = targetType;
    });
  } 
  else if (targetType === 'couple') {
    // Thuật toán gộp Ghế Đôi (Phải ghép 2 ghế liền kề nhau trên 1 hàng)
    let seatsByRow = {};
    
    // Gom nhóm các ghế đang được chọn theo hàng (row)
    currentSelectedIds.value.forEach(id => {
      let seat = seats.value.find(s => s.id === id);
      if (seat) {
        if (!seatsByRow[seat.row]) seatsByRow[seat.row] = [];
        seatsByRow[seat.row].push(seat);
      }
    });

    for (const row in seatsByRow) {
      // Sắp xếp theo số ghế từ bé đến lớn
      let rowSeats = seatsByRow[row].sort((a, b) => a.number - b.number);
      
      for (let i = 0; i < rowSeats.length; i++) {
        // Kiểm tra xem ghế kế tiếp trong mảng quét chọn có liền kề ngoài đời thực hay ko (số thứ tự chênh lệch 1)
        if (i + 1 < rowSeats.length && rowSeats[i+1].number === rowSeats[i].number + 1) {
          rowSeats[i].type = 'couple';
          rowSeats[i+1].type = 'couple_hidden';
          i++; // Nhảy cóc qua ghế đã ghép
        } else {
          rowSeats[i].type = 'standard'; // Ghế lẻ không có cặp
        }
      }
    }
  }

  // Chạy hàm sửa chữa để khôi phục ghế tàng hình hoặc sửa lỗi
  cleanupCoupleHidden();

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
  font-size: 16px; color: #f8fafc;
}
.toolbar-info strong { color: #fbbf24; font-size: 18px; }

.toolbar-actions { display: flex; gap: 10px; }
.toolbar-actions.disabled { opacity: 0.5; pointer-events: none; filter: grayscale(100%); }

.tool-btn {
  padding: 10px 15px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; color: white;
  transition: transform 0.2s; box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}
.tool-btn:hover { transform: translateY(-2px); }

.standard-btn { background: linear-gradient(145deg, #4b5563, #374151); border: 1px solid #6b7280; }
.vip-btn { background: linear-gradient(145deg, #ef4444, #b91c1c); border: 1px solid #f87171; }
.couple-btn { background: linear-gradient(145deg, #ec4899, #be185d); border: 1px solid #f472b6; }
.hidden-btn { background: transparent; border: 2px dashed #64748b; color: #cbd5e1; }
.gap-col-btn { background: linear-gradient(145deg, #3b82f6, #1d4ed8); border: 1px solid #60a5fa; }
.gap-row-btn { background: linear-gradient(145deg, #8b5cf6, #6d28d9); border: 1px solid #a78bfa; }
.gap-clear-btn { background: linear-gradient(145deg, #f59e0b, #d97706); border: 1px solid #fbbf24; margin-left: 20px;}

.editor-card { padding: 30px; overflow-x: auto; min-height: 500px; }
.seat-map-wrapper { display: flex; justify-content: center; }
.action-buttons { display: flex; gap: 10px; }
.btn-secondary-cine { background: #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
.btn-primary-cine { background-color: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
</style>