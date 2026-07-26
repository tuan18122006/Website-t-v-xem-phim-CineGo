<template>
  <div class="auth-callback">
    <div class="spinner"></div>
    <p>Đang xử lý đăng nhập...</p>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

onMounted(() => {
  const token = route.query.token;
  const userStr = route.query.user;
  
  if (token && userStr) {
    try {
      const user = JSON.parse(decodeURIComponent(userStr));
      authStore.handleGoogleCallback(token, user);
      router.push('/');
    } catch (e) {
      router.push('/login?error=Lỗi+xử+lý+đăng+nhập+Google');
    }
  } else {
    router.push('/login?error=Lỗi+xác+thực+từ+Google');
  }
});
</script>

<style scoped>
.auth-callback {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 50vh;
  color: var(--text-primary);
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(255, 0, 127, 0.2);
  border-top-color: var(--accent-pink);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
