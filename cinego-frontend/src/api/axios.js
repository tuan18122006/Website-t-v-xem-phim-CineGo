import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  timeout: 10000
});

// Request Interceptor: Tự động gắn Token vào Header nếu có
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('cinego_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response Interceptor: Xử lý lỗi tập trung (Ví dụ: hết hạn token)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Hết hạn đăng nhập hoặc không hợp lệ -> xóa token và chuyển về trang đăng nhập
      localStorage.removeItem('cinego_token');
      localStorage.removeItem('cinego_user');
      // Tùy chọn: reload lại trang hoặc dùng router chuyển hướng
      if (window.location.pathname.startsWith('/admin') || window.location.pathname === '/checkout') {
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);

export default api;
