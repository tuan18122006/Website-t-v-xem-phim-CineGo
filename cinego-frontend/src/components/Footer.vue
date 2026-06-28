<template>
  <!-- FOOTER CINEGO CHUYÊN NGHIỆP -->
  <footer class="cinego-footer">
    <div class="footer-cols">
      <div class="footer-col">
        <h4>MUA VÉ XEM PHIM</h4>
        <ul>
          <li><router-link to="/mua-ve">Lịch chiếu phim</router-link></li>
          <li><a href="#">Phim chiếu rạp</a></li>
          <li><a href="#">Review phim</a></li>
          <li><a href="#">Top phim</a></li>
          <li><a href="#">Blog phim</a></li>
        </ul>
      </div>
      
      <div class="footer-col">
        <h4>DỊCH VỤ NỔI BẬT</h4>
        <ul>
          <li><a href="#" class="featured-link">🎬 Vé xem phim</a></li>
          <li><a href="#">🚗 Trải nghiệm ghế đôi</a></li>
          <li><a href="#">🚌 Phòng chiếu IMAX</a></li>
          <li><a href="#">🔊 Đặt bắp nước Online</a></li>
          <li><a href="#">❤️ Sự kiện thành viên</a></li>
          <li><a href="#">📱 Đặt vé theo đoàn</a></li>
          <li><a href="#">💳 Ví CineGo Voucher</a></li>
          <li><a href="#">💰 Ưu đãi học sinh</a></li>
          <li><a href="#">📡 Tin tức điện ảnh</a></li>
        </ul>
      </div>

      <div class="footer-col address-col">
        <h4>CHĂM SÓC KHÁCH HÀNG ⓘ</h4>
        <p><strong>Hồ Chí Minh:</strong> Tầng 6, Tòa nhà Phú Mỹ Hưng, Số 08 đường Hoàng Văn Thái, P. Tân Phú</p>
        <p><strong>Hà Nội:</strong> Tầng 20, Tòa nhà Peakview Tower, Số 36 Phố Hoàng Cầu, P. Ô Chợ Dừa</p>
        <p><strong>Đà Nẵng:</strong> Tầng 3, Tòa nhà DMT, Số 484-486 đường 2/9, P. Hòa Cường</p>
        <p class="mt-15"><strong>Thời gian làm việc:</strong> Thứ 2 - Thứ 6 (trừ lễ, Tết) | Sáng: 9h00 - 11h30 | Chiều: 13h00 - 16h30</p>
        <p><strong>Hotline:</strong> 1900 5454 41 <em>(Phí 1.000đ/phút)</em></p>
        <p><strong>Email:</strong> hotro@cinego.vn</p>
      </div>

      <div class="footer-col partner-col">
        <h4>HỢP TÁC DOANH NGHIỆP</h4>
        <p><strong>Hotline:</strong> 1900 636 652 <em>(Phí 1.000đ/phút)</em></p>
        <p><strong>Email:</strong> merchant.care@cinego.vn</p>
        <p><strong>Website:</strong> business.cinego.vn</p>
        
        <h4 class="mt-20">LIÊN HỆ TRUYỀN THÔNG</h4>
        <p><strong>Email:</strong> pr@cinego.vn</p>
        
        <div class="social-links">
          <span class="social-circle fb">f</span>
          <span class="social-circle in">in</span>
          <span class="social-circle yt">▶</span>
        </div>
        
        <div class="app-badges">
          <div class="badge-sim">App Store</div>
          <div class="badge-sim">Google Play</div>
        </div>
      </div>
    </div>
    
    <div class="footer-bottom">
      <div class="bottom-container">
        <div class="cinego-corp-info">
          <div class="cinego-corp-logo">CineGo</div>
          <div class="cinego-corp-text">
            <h5>CÔNG TY CỔ PHẦN TRUYỀN THÔNG ĐIỆN ẢNH CINEGO</h5>
            <p>Trụ sở chính: Tầng 6, Tòa nhà Phú Mỹ Hưng, Số 8, đường Hoàng Văn Thái, Phường Tân Phú, Thành phố Hồ Chí Minh</p>
            <p>Tên thương hiệu: <strong>CineGo Cinema</strong></p>
            <p>&copy; Copyright CineGo Cinema 2026</p>
          </div>
        </div>
        <div class="cinego-mediator">
          <p>Hệ thống đặt vé và dịch vụ rạp chiếu phim trực thuộc <strong>CineGo Cinema System - Hệ thống đặt vé chuẩn rạp chiếu phim hiện đại:</strong></p>
          <p>- Dịch vụ Đặt vé Online</p>
          <p>- Dịch vụ hỗ trợ sự kiện, hội nghị</p>
          <p>- Dịch vụ ưu đãi bắp nước combo tiện lợi</p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
// Reusable client layout footer
</script>

<style scoped>
.cinego-footer {
  background-color: #1c1817;
  color: #a0aec0;
  padding: 60px 0 0 0;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  width: 100%;
}

.footer-cols {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 40px;
  padding: 0 24px 40px 24px;
}

@media (max-width: 768px) {
  .footer-cols {
    grid-template-columns: 1fr;
    gap: 30px;
  }
}

.footer-col h4 {
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
  margin-bottom: 20px;
  letter-spacing: 0.05em;
}

.footer-col ul {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-col ul li a {
  font-size: 13px;
  color: #cbd5e0;
  transition: var(--transition-smooth);
}

.footer-col ul li a:hover {
  color: var(--accent-pink);
}

.featured-link {
  color: var(--accent-pink) !important;
  font-weight: 700;
}

.address-col p, .partner-col p {
  font-size: 13px;
  line-height: 1.6;
  margin-bottom: 12px;
  color: #cbd5e0;
}

.address-col strong, .partner-col strong {
  color: #ffffff;
}

.mt-15 { margin-top: 15px; }
.mt-20 { margin-top: 20px; }

.social-links {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

.social-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #2d3748;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 14px;
  cursor: pointer;
  transition: var(--transition-smooth);
}

.social-circle:hover {
  background-color: var(--accent-pink);
}

.fb { font-family: sans-serif; }
.yt { color: #e53e3e !important; }

.app-badges {
  display: flex;
  gap: 10px;
  margin-top: 24px;
}

.badge-sim {
  background-color: #2d3748;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
}

/* FOOTER BOTTOM */
.footer-bottom {
  background-color: #120f0e;
  padding: 30px 0;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.bottom-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 40px;
}

@media (max-width: 768px) {
  .bottom-container {
    flex-direction: column;
    gap: 20px;
  }
}

.cinego-corp-info {
  display: flex;
  gap: 16px;
  flex: 1;
}

.cinego-corp-logo {
  background-color: var(--accent-pink);
  color: white;
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 16px;
  font-family: var(--font-display);
  letter-spacing: -0.05em;
  flex-shrink: 0;
}

.cinego-corp-text h5 {
  color: white;
  font-size: 13px;
  margin-bottom: 6px;
}

.cinego-corp-text p {
  font-size: 12px;
  color: #a0aec0;
  line-height: 1.5;
  margin-bottom: 4px;
}

.cinego-mediator {
  flex: 1;
  max-width: 500px;
}

.cinego-mediator p {
  font-size: 12px;
  color: #a0aec0;
  line-height: 1.5;
  margin-bottom: 4px;
}
</style>
