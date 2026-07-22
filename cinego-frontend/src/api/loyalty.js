import api from './axios';

export const getLoyaltyProfile = () => {
  return api.get('/loyalty/profile');
};

export const getRedeemableVouchers = () => {
  return api.get('/loyalty/vouchers');
};

export const getRedeemableCombos = () => {
  return api.get('/loyalty/combos');
};

export const redeemVoucher = (voucherId) => {
  return api.post(`/loyalty/redeem-voucher/${voucherId}`);
};

export const redeemCombo = (comboId) => {
  return api.post(`/loyalty/redeem-combo/${comboId}`);
};