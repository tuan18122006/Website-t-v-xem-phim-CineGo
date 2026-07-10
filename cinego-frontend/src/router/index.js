import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import ReviewMovies from '../views/client/ReviewMovies.vue';


// Lazy loading views
const Home = () => import("../views/client/HomeView.vue");
const MovieDetail = () => import("../views/client/MovieDetailView.vue");
const SeatSelection = () => import("../views/client/SeatSelectionView.vue");
const Payment = () => import("../views/client/PaymentView.vue");
const PaymentResult = () => import("../views/client/PaymentResultView.vue");
const Login = () => import("../views/client/LoginView.vue");
const Register = () => import("../views/client/RegisterView.vue");
const QuickBooking = () => import("../views/client/QuickBookingView.vue");
const ReviewPhim = () => import("../views/client/ReviewPhimView.vue");
const TopMovies = () => import("../views/client/TopMoviesView.vue");
const BlogPhim = () => import("../views/client/BlogPhimView.vue");
const AboutCineGo = () => import("../views/client/AboutCineGoView.vue");
const RoomManagement = () => import("../views/admin/RoomManagementView.vue");
const RoomEditor = () => import("../views/admin/RoomEditorView.vue");

const AdminDashboard = () => import('../views/admin/DashboardView.vue');

const routes = [
  // Client Routes
  {
    path: "/",
    name: "home",
    component: Home,
  },
  {
    path: "/mua-ve",
    name: "quick-booking",
    component: QuickBooking,
  },
  {
    path: "/movie/:id",
    name: "movie-detail",
    component: MovieDetail,
  },
  {
    path: '/top-movies',
    name: 'top-movies',
    component: TopMovies
  },
    {
    path: '/review-movies',
    name: 'review-movies',
    component: ReviewMovies
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import("../views/client/ProfileView.vue"),
    meta: { requiresAuth: true }
  },

  {
    path: '/booking/seats',
    name: 'seat-selection',
    component: SeatSelection,
    meta: { requiresAuth: true },
  },
  {
    path: "/booking/payment",
    name: "payment",
    component: Payment,
    meta: { requiresAuth: true },
  },
  {
    path: "/payment/result",
    name: "payment-result",
    component: PaymentResult,
  },
  {
    path: "/login",
    name: "login",
    component: Login,
  },
  {
    path: "/register",
    name: "register",
    component: Register,
  },
  {
    path: "/review-phim",
    name: "review-phim",
    component: ReviewPhim,
  },
  {
    path: "/top-phim",
    name: "top-phim",
    component: TopMovies,
  },
  {
    path: "/blog-phim",
    name: "blog-phim",
    component: BlogPhim,
  },
  {
    path: "/ve-cinego",
    name: "ve-cinego",
    component: AboutCineGo,
  },

  // Admin Routes
  {
    path: "/admin",
    redirect: "/admin/dashboard",
  },
  {
    path: "/admin/dashboard",
    name: "admin-dashboard",
    component: AdminDashboard,
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/admin/genres",
    name: "admin-GenreManagement",
    component: () => import("../views/admin/GenreManagement.vue"),
  },


{
  path: '/admin/rooms',
  name: 'admin-rooms', 
  component: () => import('../views/admin/RoomManagementView.vue'),
  meta: { requiresAuth: true, role: "admin" }
},
{
  path: '/admin/rooms/:id/edit', 
  name: 'admin-room-edit',
  component: () => import('../views/admin/RoomEditorView.vue'),
  meta: { requiresAuth: true, role: "admin" }
},

  {
    path: "/admin/movies",
    name: "admin-MoviesView",
    component: () => import("../views/admin/MoviesView.vue"),
  },
  {
    path: "/admin/combos",
    name: "admin-Combos",
    component: () => import("../views/admin/ComboManagementView.vue"),
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/admin/users",
    name: "admin-UserManagement",
    component: () => import("../views/admin/UserManagement.vue"),
    meta: { requiresAuth: true, role: "admin" },
  },
  {
    path: "/admin/vouchers",
    name: "admin-VoucherManagement",
    component: () => import("../views/admin/VoucherManager.vue"),
    meta: { requiresAuth: true, role: "admin" },
  },
  

  {
    path: "/staff",
    redirect: "/staff/dashboard",
  },
  {
    path: "/staff/dashboard",
    name: "staff-dashboard",
    component: () => import("../views/staff/StaffDashboardView.vue"),
    meta: { requiresAuth: true, role: "staff" },
  },

  // Wildcard redirect
  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

// Navigation Guards: Bảo vệ các trang cần Đăng nhập & Quyền Admin
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Xác định xem trang yêu cầu đăng nhập không
  const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
  // Xác định xem trang yêu cầu quyền cụ thể không (ví dụ: admin)
  const requiredRole = to.meta.role;

  if (requiresAuth && !authStore.isAuthenticated) {
    // Nếu chưa đăng nhập -> chuyển về Login
    next({ name: "login", query: { redirect: to.fullPath } });
  } else if (requiresAuth && requiredRole) {
    // Nếu đã đăng nhập nhưng cần check quyền
    if (requiredRole === "admin" && !authStore.isAdmin) {
      // Không có quyền Admin -> chuyển về Trang chủ
      next({ name: "home" });
    } else if (requiredRole === "staff" && (!authStore.isAdmin && !authStore.isStaff)) {
      // Không có quyền Staff hoặc Admin -> chuyển về Trang chủ
      next({ name: "home" });
    } else {
      next();
    }
  } else {
    // Cho đi tiếp nếu không yêu cầu gì đặc biệt
    next();
  }
});

export default router;
