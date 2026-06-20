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

    <div class="glass-panel editor-card">
      <div v-if="loading" class="loading-state">Đang tải sơ đồ rạp...</div>
      <div v-else class="seat-map-wrapper">
        <SeatMap :seats="seats" mode="admin" @seat-clicked="handleSeatClick" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import SeatMap from '../../components/SeatMap.vue';
import api from '../../api/axios';

const route = useRoute();
const router = useRouter();
const roomId = route.params.id;
const seats = ref([]);
const roomName = ref('...');
const loading = ref(true);

const fetchRoomDetails = async () => {
  try {
    const res = await api.get(`/admin/rooms/${roomId}`);
    roomName.value = res.data.data.room.name;
    seats.value = res.data.data.seats;
    loading.value = false;
  } catch (err) { console.error(err); }
};

const handleSeatClick = (seat) => {
  const types = ['standard', 'vip', 'couple', 'hidden'];
  let idx = types.indexOf(seat.type);
  seat.type = types[(idx + 1) % types.length];
};

const saveSeats = async () => {
  try {
    await api.put(`/admin/rooms/${roomId}/update-seat-map`, {
      seats: seats.value.map(s => ({ id: s.id, type: s.type }))
    });
    alert("Lưu sơ đồ thành công!");
    
    router.push({ name: 'admin-dashboard' }); 
  } catch (err) { alert("Lưu thất bại!"); }
};

const goBack = () => router.push({ name: 'admin-dashboard' });

onMounted(fetchRoomDetails);
</script>

<style scoped>
.header-card { margin-bottom: 20px; padding: 20px; }
.header-content { display: flex; justify-content: space-between; align-items: center; }
.editor-card { padding: 30px; overflow-x: auto; min-height: 500px; }
.seat-map-wrapper { display: flex; justify-content: center; }
.action-buttons { display: flex; gap: 10px; }
.btn-secondary-cine { background: #e2e8f0; color: #475569; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
.btn-primary-cine { background-color: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; }
</style>