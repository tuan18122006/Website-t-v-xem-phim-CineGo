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
        
        this.token = token;
        this.user = user;
        
        localStorage.setItem('cinego_token', token);
        localStorage.setItem('cinego_user', JSON.stringify(user));
        
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Đăng nhập thất bại. Vui lòng kiểm tra lại thông tin!';
        // Fallback mock nếu API chưa sẵn sàng hoặc đang test giao diện
        if (err.message.includes('Network Error') || err.response?.status === 404) {
          console.warn('Backend not running or route not found. Using Mock Login for demo.');
          let mockUser = { id: 1, name: 'Khách hàng Demo', email: email, role: 'customer' };
          if (email.includes('admin')) {
            mockUser = { id: 99, name: 'Quản trị viên', email: email, role: 'admin' };
          }
          
          this.token = 'mock_sanctum_token_123456';
          this.user = mockUser;
          
          localStorage.setItem('cinego_token', this.token);
          localStorage.setItem('cinego_user', JSON.stringify(this.user));
          return true;
        }
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
          password_confirmation 
        });
        const { token, user } = response.data;
        
        this.token = token;
        this.user = user;
        
        localStorage.setItem('cinego_token', token);
        localStorage.setItem('cinego_user', JSON.stringify(user));
        
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Đăng ký tài khoản thất bại!';
        throw err;
      } finally {
        this.loading = false;
      }
    },
    
    async logout() {
      try {
        if (this.token && !this.token.startsWith('mock_')) {
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
        if (this.token.startsWith('mock_')) return;
        const response = await api.get('/user');
        this.user = response.data;
        localStorage.setItem('cinego_user', JSON.stringify(this.user));
      } catch (err) {
        console.error('Fetch user error:', err);
      }
    }
  }
});
