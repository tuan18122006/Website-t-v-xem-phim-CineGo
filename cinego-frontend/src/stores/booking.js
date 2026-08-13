import { defineStore } from 'pinia';
import api from '../api/axios';

const getStorage = (key, defaultValue = null) => {
  try {
    const item = sessionStorage.getItem(key);
    return item ? JSON.parse(item) : defaultValue;
  } catch (e) {
    return defaultValue;
  }
};

const setStorage = (key, value) => {
  try {
    if (value === null || value === undefined) {
      sessionStorage.removeItem(key);
    } else {
      sessionStorage.setItem(key, JSON.stringify(value));
    }
  } catch (e) {
    console.error(`Error saving ${key} to sessionStorage`, e);
  }
};

export const useBookingStore = defineStore('booking', {
  state: () => ({
    selectedMovie: getStorage('cinego_movie', null),
    selectedShowtime: getStorage('cinego_showtime', null),
    selectedSeats: getStorage('cinego_seats', []),
    selectedCombos: getStorage('cinego_combos', []),
    appliedVoucher: getStorage('cinego_voucher', null),
    holdExpiresAt: getStorage('cinego_holdExpiresAt', null),
  }),

  getters: {
    subtotalSeats: (state) => {
      if (!state.selectedSeats) return 0;
      return state.selectedSeats.reduce((total, seat) => {
        const price = seat.price || (seat.type === "vip" ? 95000 : seat.type === "couple" ? 140000 : 75000);
        return total + price;
      }, 0);
    },

    subtotalCombos: (state) => {
      if (!state.selectedCombos) return 0;
      return state.selectedCombos.reduce((total, item) => {
        return total + (item.combo?.price || 0) * (item.quantity || 0);
      }, 0);
    },

    subtotal: (getters) => {
      return getters.subtotalSeats + getters.subtotalCombos;
    },

    discountAmount(state) {
      if (!state.appliedVoucher || typeof this.subtotal !== 'number') {
        return 0;
      }

      const voucher = state.appliedVoucher;
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
      const isSameMovie = this.selectedMovie && this.selectedMovie.id == movie.id;
      this.selectedMovie = movie;
      setStorage('cinego_movie', movie);

      // Quay lại ĐÚNG phim cũ → giữ nguyên suất chiếu, ghế đã chọn và bộ đếm giữ ghế
      if (isSameMovie) return;

      this.selectedShowtime = null;
      this.selectedSeats = [];
      this.selectedCombos = [];
      this.appliedVoucher = null;
      this.holdExpiresAt = null;

      setStorage('cinego_showtime', null);
      setStorage('cinego_seats', []);
      setStorage('cinego_combos', []);
      setStorage('cinego_voucher', null);
      setStorage('cinego_holdExpiresAt', null);
    },

    selectShowtime(showtime) {
      this.selectedShowtime = showtime;
      this.selectedSeats = [];
      this.selectedCombos = [];
      this.appliedVoucher = null;
      this.holdExpiresAt = null;

      setStorage('cinego_showtime', showtime);
      setStorage('cinego_seats', []);
      setStorage('cinego_combos', []);
      setStorage('cinego_voucher', null);
      setStorage('cinego_holdExpiresAt', null);
    },

    toggleSeat(seat) {
      const index = this.selectedSeats.findIndex(s => s.id === seat.id);
      if (index > -1) {
        this.selectedSeats.splice(index, 1);
      } else {
        this.selectedSeats.push(seat);
      }
      // Lưu lại danh sách ghế mới vào sessionStorage
      setStorage('cinego_seats', this.selectedSeats);
    },

    addCombo(combo) {
      const existing = this.selectedCombos.find(c => c.combo.id === combo.id);
      if (existing) {
        existing.quantity += 1;
      } else {
        this.selectedCombos.push({ combo, quantity: 1 });
      }
      setStorage('cinego_combos', this.selectedCombos);
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
      setStorage('cinego_combos', this.selectedCombos);
    },

    updateComboQuantity(comboId, quantity) {
      const item = this.selectedCombos.find(c => c.combo.id === comboId);
      if (item) {
        item.quantity = Math.max(0, parseInt(quantity) || 0);
        if (item.quantity === 0) {
          this.selectedCombos = this.selectedCombos.filter(c => c.combo.id !== comboId);
        }
      }
      setStorage('cinego_combos', this.selectedCombos);
    },

    applyVoucher(voucher) {
      this.appliedVoucher = voucher;
      setStorage('cinego_voucher', voucher);
    },

    removeVoucher() {
      this.appliedVoucher = null;
      setStorage('cinego_voucher', null);
    },

    setHoldExpiry(minutes = 3) {
      const expiry = Date.now() + minutes * 60 * 1000;
      this.holdExpiresAt = expiry;
      setStorage('cinego_holdExpiresAt', expiry);
    },

    clearBooking() {
      this.selectedMovie = null;
      this.selectedShowtime = null;
      this.selectedSeats = [];
      this.selectedCombos = [];
      this.appliedVoucher = null;
      this.holdExpiresAt = null;

      // Xóa hết bộ nhớ phiên làm việc
      sessionStorage.removeItem('cinego_movie');
      sessionStorage.removeItem('cinego_showtime');
      sessionStorage.removeItem('cinego_seats');
      sessionStorage.removeItem('cinego_combos');
      sessionStorage.removeItem('cinego_voucher');
      sessionStorage.removeItem('cinego_holdExpiresAt');
    }
  }
});