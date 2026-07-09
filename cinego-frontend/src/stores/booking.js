import { defineStore } from 'pinia';
import api from '../api/axios';

export const useBookingStore = defineStore('booking', {
  state: () => ({
    selectedMovie: null,
    selectedShowtime: null,
    selectedSeats: [], // Mảng chứa các đối tượng seat
    selectedCombos: [], // Mảng chứa các combo đã chọn: { combo, quantity }
    appliedVoucher: null,
    holdExpiresAt: null, // Hạn giữ ghế (timestamp)
  }),

  getters: {
    subtotalSeats: (state) => {
      // Đảm bảo state.selectedSeats luôn là một mảng
      if (!state.selectedSeats) return 0;
      return state.selectedSeats.reduce((total, seat) => {
        const price = seat.price || (seat.type === "vip" ? 95000 : seat.type === "couple" ? 140000 : 75000);
        return total + price;
      }, 0);
    },

    subtotalCombos: (state) => {
      // Đảm bảo state.selectedCombos luôn là một mảng
      if (!state.selectedCombos) return 0;
      return state.selectedCombos.reduce((total, item) => {
        return total + (item.combo?.price || 0) * (item.quantity || 0);
      }, 0);
    },

    subtotal: (getters) => {
      return getters.subtotalSeats + getters.subtotalCombos;
    },

    discountAmount(state) {
      // 1. Kiểm tra nếu chưa có voucher hoặc subtotal không hợp lệ
      if (!state.appliedVoucher || typeof this.subtotal !== 'number') {
        return 0;
      }

      const voucher = state.appliedVoucher;

      // 2. Sử dụng Optional Chaining (?) để tránh lỗi nếu voucher thiếu thuộc tính
      const minSpend = voucher.min_spend || 0;
      if (this.subtotal < minSpend) {
        return 0;
      }

      let discount = 0;
      if (voucher.discount_type === "percentage") {
        discount = (this.subtotal * (voucher.discount_value || 0)) / 100;
        if (voucher.max_discount && discount > voucher.max_discount) {
          discount = voucher.max_discount;
        }
      } else {
        discount = voucher.discount_value || 0;
      }

      return Math.min(discount, this.subtotal);
    },

    totalAmount: (getters) => {
      const total = getters.subtotal - getters.discountAmount;
      return Math.max(0, total);
    }
  },

  actions: {
    selectMovie(movie) {
      this.selectedMovie = movie;
      // Reset khi chọn phim mới
      this.selectedShowtime = null;
      this.selectedSeats = [];
      this.selectedCombos = [];
      this.appliedVoucher = null;
      this.holdExpiresAt = null;
    },

    selectShowtime(showtime) {
      this.selectedShowtime = showtime;
      this.selectedSeats = [];
      this.selectedCombos = [];
      this.appliedVoucher = null;
      this.holdExpiresAt = null;
    },

    toggleSeat(seat) {
      const index = this.selectedSeats.findIndex(s => s.id === seat.id);
      if (index > -1) {
        this.selectedSeats.splice(index, 1);
      } else {
        this.selectedSeats.push(seat);
      }
    },

    addCombo(combo) {
      const existing = this.selectedCombos.find(c => c.combo.id === combo.id);
      if (existing) {
        existing.quantity += 1;
      } else {
        this.selectedCombos.push({ combo, quantity: 1 });
      }
    },

    removeCombo(combo) {
      const index = this.selectedCombos.findIndex(c => c.combo.id === combo.id);
      if (index > -1) {
        const item = this.selectedCombos[index];
        if (item.quantity > 1) {
          item.quantity -= 1;
        } else {
          this.selectedCombos.splice(index, 1);
        }
      }
    },

    updateComboQuantity(comboId, quantity) {
      const item = this.selectedCombos.find(c => c.combo.id === comboId);
      if (item) {
        item.quantity = Math.max(0, parseInt(quantity) || 0);
        if (item.quantity === 0) {
          this.selectedCombos = this.selectedCombos.filter(c => c.combo.id !== comboId);
        }
      }
    },

    applyVoucher(voucher) {
      this.appliedVoucher = voucher;
    },

    removeVoucher() {
      this.appliedVoucher = null;
    },

    setHoldExpiry(minutes = 10) {
      this.holdExpiresAt = Date.now() + minutes * 60 * 1000;
    },

    clearBooking() {
      this.selectedMovie = null;
      this.selectedShowtime = null;
      this.selectedSeats = [];
      this.selectedCombos = [];
      this.appliedVoucher = null;
      this.holdExpiresAt = null;
    }
  }
});
