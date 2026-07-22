import axios from './axios'; // Sửa cú pháp import chuẩn

// ==========================================
// 1. API CLIENT (Dành cho khách hàng)
// ==========================================
// Lưu ý: Bỏ tiền tố '/api' ở đầu vì baseURL trong ./axios đã có sẵn '/api'
export const getLoyaltyProfile = () => 
  axios.get('/loyalty/profile');

export const getRedeemableVouchers = () => 
  axios.get('/loyalty/vouchers');

export const getRedeemableCombos = () => 
  axios.get('/loyalty/combos');

export const redeemVoucher = (voucherId) => 
  axios.post(`/loyalty/redeem-voucher/${voucherId}`);

export const redeemCombo = (comboId) => 
  axios.post(`/loyalty/redeem-combo/${comboId}`);


// ==========================================
// 2. API ADMIN (Dành cho Quản trị viên)
// ==========================================
// Lấy danh sách thành viên + thông tin tích điểm
export const getAdminUsersLoyalty = (params) => 
  axios.get('/admin/loyalty/users', { params });

// Lịch sử tích điểm của 1 user
export const getAdminUserHistories = (userId) => 
  axios.get(`/admin/loyalty/users/${userId}/histories`);

// Admin cộng/trừ điểm thủ công
export const adjustUserPoints = (userId, data) => 
  axios.post(`/admin/loyalty/users/${userId}/adjust-points`, data);

// Cập nhật cấu hình điểm cho Voucher
export const updateVoucherPoints = (voucherId, data) => 
  axios.patch(`/admin/vouchers/${voucherId}/loyalty`, data);