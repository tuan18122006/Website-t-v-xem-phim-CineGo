<template>
  <div class="auth-view">
    <div class="auth-card glass-panel">
      <h2 class="auth-title gradient-text-accent">Đăng Ký</h2>
      <p class="auth-subtitle">Tạo tài khoản CineGo mới để tham gia đặt vé và nhận ưu đãi</p>
      
      <div v-if="error" class="alert-error">
        {{ error }}
      </div>

      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="form-group">
          <label for="name">Họ và Tên</label>
          <input 
            v-model="name" 
            type="text" 
            id="name" 
            required 
            placeholder="Nguyễn Văn A"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="email">Địa chỉ Email</label>
          <input 
            v-model="email" 
            type="email" 
            id="email" 
            required 
            placeholder="nguyen.van.a@example.com"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="password">Mật khẩu</label>
          <input 
            v-model="password" 
            type="password" 
            id="password" 
            required 
            placeholder="••••••••"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="password_confirmation">Xác nhận mật khẩu</label>
          <input 
            v-model="passwordConfirmation" 
            type="password" 
            id="password_confirmation" 
            required 
            placeholder="••••••••"
            class="form-input"
          />
        </div>

        <button type="submit" :disabled="loading" class="btn-submit">
          <span v-if="loading" class="btn-spinner"></span>
          <span v-else>Đăng Ký Tài Khoản</span>
        </button>
      </form>

      <p class="auth-footer">
        Đã có tài khoản? 
        <router-link to="/login" class="auth-link">Đăng nhập</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref(null);

const handleRegister = async () => {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Mật khẩu xác nhận không khớp!';
    return;
  }
  
  loading.value = true;
  error.value = null;
  try {
    const success = await authStore.register(
      name.value, 
      email.value, 
      password.value, 
      passwordConfirmation.value
    );
    if (success) {
      router.push('/');
    }
  } catch (err) {
    error.value = authStore.error || 'Đăng ký thất bại. Vui lòng kiểm tra lại!';
    // Fallback Mock Register for demo UI
    if (err.message?.includes('Network Error') || err.response?.status === 404) {
      console.warn('Backend not running. Fallback to mock login.');
      await authStore.login(email.value, password.value);
      router.push('/');
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.auth-view {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px 0;
}

.auth-card {
  width: 100%;
  max-width: 460px;
  padding: 40px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.auth-title {
  font-size: 32px;
  text-align: center;
  font-weight: 800;
}

.auth-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
  text-align: center;
  line-height: 1.4;
  margin-top: -10px;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
}

.form-input {
  background: var(--bg-tertiary);
  color: red;
  border: 1px solid var(--border-glass);
  padding: 14px 18px;
  border-radius: var(--radius-md);
  font-size: 14px;
  transition: var(--transition-smooth);
}

.form-input:focus {
  outline: none;
  border-color: var(--accent-pink);
  box-shadow: var(--shadow-neon-pink);
}

.btn-submit {
  background: linear-gradient(135deg, var(--accent-pink) 0%, var(--accent-violet) 100%);
  color: white;
  padding: 14px;
  border: none;
  border-radius: var(--radius-md);
  font-weight: 700;
  cursor: pointer;
  box-shadow: var(--shadow-neon-pink);
  transition: var(--transition-bounce);
  margin-top: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.btn-submit:hover {
  transform: scale(1.02);
  box-shadow: 0 0 25px rgba(255, 0, 127, 0.5);
}

.btn-submit:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255,255,255,0.2);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s infinite linear;
}

.alert-error {
  background: rgba(230, 0, 0, 0.1);
  border: 1px solid rgba(230, 0, 0, 0.2);
  color: #ff5555;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  font-size: 13px;
  line-height: 1.4;
}

.auth-footer {
  text-align: center;
  font-size: 14px;
  color: var(--text-secondary);
}

.auth-link {
  color: var(--accent-pink);
  font-weight: 600;
  transition: var(--transition-smooth);
}

.auth-link:hover {
  text-decoration: underline;
  text-shadow: 0 0 10px var(--accent-pink-glow);
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
