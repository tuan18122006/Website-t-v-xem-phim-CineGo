import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('cinego_user')) || null,
    token: localStorage.getItem('cinego_token') || null,
    loading: false,
    error: null
  }),
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    isAdmin: (state) => state.user?.role === 'admin',
    isStaff: (state) => state.user?.role === 'staff' || state.user?.role === 'admin'
  },
  
  actions: {
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/login', { email, password });
        const { token, user } = response.data;
        this._persist(token, user);
        return true;
      } catch (err) {
        this.error = this._extractError(err, 'Đăng nhập thất bại. Vui lòng thử lại!');
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async register(name, email, password, password_confirmation) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/register', {
          name,
          email,
          password,
          password_confirmation,
        });
        const { token, user } = response.data;
        this._persist(token, user);
        return true;
      } catch (err) {
        this.error = this._extractError(err, 'Đăng ký tài khoản thất bại. Vui lòng thử lại!');
        throw err;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        if (this.token) {
          await api.post('/logout');
        }
      } catch (err) {
        console.error('Logout error on backend:', err);
      } finally {
        this.token = null;
        this.user = null;
        localStorage.removeItem('cinego_token');
        localStorage.removeItem('cinego_user');
      }
    },

    async fetchUser() {
      if (!this.token) return;
      try {
        const response = await api.get('/me');
        this.user = response.data;
        localStorage.setItem('cinego_user', JSON.stringify(this.user));
      } catch (err) {
        console.error('Fetch user error:', err);
      }
    },

    // Lưu token + user vào state và localStorage
    _persist(token, user) {
      this.token = token;
      this.user = user;
      localStorage.setItem('cinego_token', token);
      localStorage.setItem('cinego_user', JSON.stringify(user));
    },

    // Trích thông báo lỗi thân thiện từ response của Laravel
    _extractError(err, fallback) {
      const data = err.response?.data;
      if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
      }
      if (data?.message) return data.message;
      if (err.message?.includes('Network Error')) {
        return 'Không kết nối được máy chủ. Vui lòng kiểm tra kết nối và thử lại!';
      }
      return fallback;
    }
  }
});
