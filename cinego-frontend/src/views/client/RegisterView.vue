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
          <label for="phone">Số điện thoại</label>
          <input 
            v-model="phone" 
            type="tel" 
            id="phone" 
            required 
            placeholder="0912345678"
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

      <div class="divider">
        <span>Hoặc</span>
      </div>

      <button @click="handleGoogleLogin" type="button" class="btn-google">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="20px" height="20px">
          <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
          <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
          <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
          <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
        </svg>
        Đăng ký bằng Google
      </button>

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
const phone = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref(null);

const handleGoogleLogin = () => {
  authStore.loginWithGoogle();
};

const handleRegister = async () => {
  const phoneRegex = /^(84|0[3|5|7|8|9])+([0-9]{8})\b$/;
  if (!phoneRegex.test(phone.value)) {
    error.value = 'Số điện thoại không hợp lệ (cần 10 chữ số).';
    return;
  }

  const passRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&_]{8,}$/;
  if (!passRegex.test(password.value)) {
    error.value = 'Mật khẩu phải có ít nhất 8 ký tự, bao gồm ít nhất 1 chữ cái và 1 chữ số!';
    return;
  }

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
      phone.value,
      null,
      password.value, 
      passwordConfirmation.value
    );
    if (success) {
      router.push('/');
    }
  } catch (err) {
    error.value = authStore.error || 'Đăng ký thất bại. Vui lòng kiểm tra lại!';
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
  color: var(--text-primary);
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
  display: flex;
  justify-content: center;
  align-items: center;
}

.divider {
  display: flex;
  align-items: center;
  text-align: center;
  color: var(--text-muted);
  font-size: 12px;
  margin: 10px 0;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid var(--border-glass);
}

.divider:not(:empty)::before {
  margin-right: .25em;
}

.divider:not(:empty)::after {
  margin-left: .25em;
}

.btn-google {
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
  padding: 12px;
  border: 2px solid rgba(255, 255, 255, 0.25);
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  font-family: inherit;
  font-size: 14px;
}

.btn-google:hover {
  background: rgba(255, 255, 255, 0.15);
  border-color: var(--accent-pink);
  box-shadow: 0 0 15px rgba(255, 0, 127, 0.3);
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
