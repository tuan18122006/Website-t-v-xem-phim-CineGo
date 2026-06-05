import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

// Lazy loading views
const Home = () => import('../views/client/HomeView.vue');
const MovieDetail = () => import('../views/client/MovieDetailView.vue');
const SeatSelection = () => import('../views/client/SeatSelectionView.vue');
const Payment = () => import('../views/client/PaymentView.vue');
const Login = () => import('../views/client/LoginView.vue');
const Register = () => import('../views/client/RegisterView.vue');
const QuickBooking = () => import('../views/client/QuickBookingView.vue');

const AdminDashboard = () => import('../views/admin/DashboardView.vue');

const routes = [
  // Client Routes
  {
    path: '/',
    name: 'home',
    component: Home
  },
  {
    path: '/mua-ve',
    name: 'quick-booking',
    component: QuickBooking
  },
  {
    path: '/movie/:id',
    name: 'movie-detail',
    component: MovieDetail
  },
  {
    path: '/booking/seats',
    name: 'seat-selection',
    component: SeatSelection,
    meta: { requiresAuth: true }
  },
  {
    path: '/booking/payment',
    name: 'payment',
    component: Payment,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'login',
    component: Login
  },
  {
    path: '/register',
    name: 'register',
    component: Register
  },

  // Admin Routes
  {
    path: '/admin',
    redirect: '/admin/dashboard'
  },
  {
    path: '/admin/dashboard',
    name: 'admin-dashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/genres',
    name: 'admin-GenreManagement',
    component: () => import('../views/admin/GenreManagement.vue') 
  },
  {
    path: '/admin/movies',
    name: 'admin-MoviesView',
    component: () => import('../views/admin/MoviesView.vue')
  },
  
  // Wildcard redirect
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  }
});

// Navigation Guards: Bảo vệ các trang cần Đăng nhập & Quyền Admin
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  // Xác định xem trang yêu cầu đăng nhập không
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  // Xác định xem trang yêu cầu quyền cụ thể không (ví dụ: admin)
  const requiredRole = to.meta.role;
  
  if (requiresAuth && !authStore.isAuthenticated) {
    // Nếu chưa đăng nhập -> chuyển về Login
    next({ name: 'login', query: { redirect: to.fullPath } });
  } else if (requiresAuth && requiredRole) {
    // Nếu đã đăng nhập nhưng cần check quyền
    if (requiredRole === 'admin' && !authStore.isAdmin) {
      // Không có quyền Admin -> chuyển về Trang chủ
      next({ name: 'home' });
    } else {
      next();
    }
  } else {
    // Cho đi tiếp nếu không yêu cầu gì đặc biệt
    next();
  }
});

export default router;
