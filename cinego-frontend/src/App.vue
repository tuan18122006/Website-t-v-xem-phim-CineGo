<template>
  <div class="app-wrapper">
    <!-- Navbar/Footer chỉ hiện ở trang client; tự động ẩn ở khu vực quản trị (Admin & Staff) -->
    <Navbar v-if="!isBackofficeRoute" />

    <main :class="isBackofficeRoute ? 'admin-main-wrapper' : (route.meta.fullWidth ? 'main-content-wide' : 'main-content')">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <Footer v-if="!isBackofficeRoute" />

    <!-- Trợ lý AI CineGo (chỉ hiện ở trang khách) -->
    <ChatWidget v-if="!isBackofficeRoute" />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Navbar from './components/Navbar.vue';
import Footer from './components/Footer.vue';
import ChatWidget from './components/ChatWidget.vue';
import { useAuthStore } from './stores/auth';

const route = useRoute();
const authStore = useAuthStore();

// Khu vực quản trị dùng layout riêng (sidebar riêng), không dùng Navbar/Footer của khách
const isBackofficeRoute = computed(
  () => route.path.startsWith('/admin') || route.path.startsWith('/staff')
);

onMounted(() => {
  if (authStore.token) {
    authStore.fetchUser();
  }
});
</script>

<style>
.app-wrapper {
  display: flex;
  flex-direction: column;
  background: radial-gradient(circle at top, #fff0f2, #ffffff 75%);
  min-height: 100vh;
}

.main-content {
  flex: 1;
  max-width: 1200px;
  width: 100%;
  margin: 0 auto;
  padding: 40px 24px;
}

.main-content-wide {
  flex: 1;
  max-width: 100%;
  width: 100%;
  padding: 40px 24px;
}

.admin-main-wrapper {
  flex: 1;
  width: 100%;
}

/* Route transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
