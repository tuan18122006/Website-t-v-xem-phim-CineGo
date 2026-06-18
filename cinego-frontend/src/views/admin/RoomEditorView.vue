<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Chỉnh sửa Sơ đồ Rạp</h1>
    
    <div v-if="loading">Đang tải sơ đồ ghế...</div>
    
    <SeatMap 
      v-else-if="seats.length > 0" 
      :seats="seats" 
      mode="admin" 
      @seat-clicked="handleSeatClick" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../api/axios';
import SeatMap from '@/components/SeatMap.vue';

const seats = ref([]);
const loading = ref(true);

const fetchRoomSeats = async () => {
  try {
    // Vì base URL đã được thiết lập là /api trong file axios của nhóm, 
    // bạn chỉ cần gọi từ 'admin/...' trở đi
    const response = await api.get('/admin/rooms/1'); 
    seats.value = response.data.data.seats;
    loading.value = false;
  } catch (error) {
    console.error("Lỗi khi tải sơ đồ ghế:", error);
    loading.value = false;
  }
};

const handleSeatClick = (seat) => {
  console.log("Admin vừa chọn ghế:", seat);
};

onMounted(() => {
  fetchRoomSeats();
});
</script>