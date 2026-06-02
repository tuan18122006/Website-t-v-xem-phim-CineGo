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
      return state.selectedSeats.reduce((total, seat) => {
        // Lấy giá từ seat.price_config hoặc mặc định dựa vào loại ghế
        const price = seat.price || 
                      (seat.type === 'vip' ? 95000 : seat.type === 'couple' ? 140000 : 75000);
        return total + price;
      }, 0);
    },
    
    subtotalCombos: (state) => {
      return state.selectedCombos.reduce((total, item) => {
        return total + (item.combo.price * item.quantity);
      }, 0);
    },
    
    subtotal: (getters) => {
      return getters.subtotalSeats + getters.subtotalCombos;
    },
    
    discountAmount: (state, getters) => {
      if (!state.appliedVoucher) return 0;
      const { discount_type, discount_value, min_spend, max_discount } = state.appliedVoucher;
      
      if (getters.subtotal < min_spend) return 0;
      
      let discount = 0;
      if (discount_type === 'percentage') {
        discount = (getters.subtotal * discount_value) / 100;
        if (max_discount && discount > max_discount) {
          discount = max_discount;
        }
      } else {
        discount = discount_value;
      }
      return Math.min(discount, getters.subtotal);
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
