<template>
  <div class="app-wrapper">
    <!-- Navbar chỉ hiện ở các trang client, tự động ẩn ở trang quản lý Admin -->
    <Navbar v-if="!isAdminRoute" />
    
    <main :class="isAdminRoute ? 'admin-main-wrapper' : 'main-content'">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    
    <!-- Footer lớn chuyên nghiệp mặc định ở các trang client, tự động ẩn ở trang Admin -->
    <Footer v-if="!isAdminRoute" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import Navbar from './components/Navbar.vue';
import Footer from './components/Footer.vue';

const route = useRoute();
const isAdminRoute = computed(() => route.path.startsWith('/admin'));
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
