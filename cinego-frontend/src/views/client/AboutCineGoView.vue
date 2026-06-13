<template>
  <div class="about-page-container">
    <!-- HERO SECTION -->
    <div class="about-hero">
      <div class="hero-glow"></div>
      <div class="hero-content">
        <span class="hero-subtitle">CINEGO LUXURY CINEMA</span>
        <h1 class="hero-title">Trải Nghiệm Điện Ảnh Thượng Lưu</h1>
        <p class="hero-desc">
          CineGo tự hào là hệ thống rạp chiếu phim cao cấp hàng đầu, mang đến tiêu chuẩn trình chiếu vượt trội, âm thanh sống động cùng dịch vụ ẩm thực rạp thượng hạng.
        </p>

        <!-- STATS BADGES -->
        <div class="brand-stats">
          <div class="brand-stat-item">
            <span class="stat-num">#1</span>
            <span class="stat-lbl">Hệ thống Rạp LUXURY</span>
          </div>
          <div class="stat-divider"></div>
          <div class="brand-stat-item">
            <span class="stat-num">100%</span>
            <span class="stat-lbl">Ghế Da VIP & Couple</span>
          </div>
          <div class="stat-divider"></div>
          <div class="brand-stat-item">
            <span class="stat-num">6M+</span>
            <span class="stat-lbl">Khách hàng tin dùng</span>
          </div>
          <div class="stat-divider"></div>
          <div class="brand-stat-item">
            <span class="stat-num">3D & IMAX</span>
            <span class="stat-lbl">Công nghệ tiên tiến</span>
          </div>
        </div>
      </div>
    </div>

    <!-- CORE FEATURES SECTION -->
    <div class="about-content-wrapper">
      <div class="feature-section">
        <div class="section-text">
          <span class="feature-tag">PHÒNG CHIẾU CAO CẤP</span>
          <h2>Phòng Chiếu LUXURY & IMAX Hiện Đại</h2>
          <p>
            Hệ thống phòng chiếu tại CineGo được trang bị màn chiếu kích thước lớn với độ nét cực cao 4K, hỗ trợ trình chiếu 2D, 3D và phòng chiếu công nghệ IMAX đỉnh cao. 
          </p>
          <p>
            Mỗi phòng chiếu đều được thiết kế cách âm tiêu chuẩn quốc tế tuyệt đối, kết hợp hệ thống âm thanh vòm Dolby Atmos tạo nên không gian âm thanh đa chiều trung thực và sống động tới từng chi tiết nhỏ.
          </p>
        </div>
        <div class="section-image">
          <img src="https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=600&q=80" alt="Phòng chiếu CineGo" class="about-section-img" />
        </div>
      </div>

      <div class="feature-section reverse">
        <div class="section-text">
          <span class="feature-tag">TIỆN NGHI THƯỢNG HẠNG</span>
          <h2>100% Ghế VIP bọc da và Ghế Đôi Couple rộng rãi</h2>
          <p>
            CineGo định nghĩa lại sự thoải mái khi xem phim bằng việc trang bị toàn bộ ghế ngồi bọc da cao cấp (VIP Leather seats) có thể ngả điện tiện lợi ở mọi phòng chiếu thường. 
          </p>
          <p>
            Đối với các cặp đôi, hàng ghế đôi Couple lớn (xếp tại cuối mỗi phòng) được thiết kế riêng tư, ấm cúng và êm ái, mang lại trải nghiệm xem phim hoàn toàn thư giãn như tại gia đình.
          </p>
        </div>
        <div class="section-image">
          <img src="https://images.unsplash.com/photo-1594909122845-11baa439b7bf?auto=format&fit=crop&w=600&q=80" alt="Ghế xem phim VIP" class="about-section-img" />
        </div>
      </div>

      <!-- MEMBER CARD LOYALTY CARD -->
      <div class="member-card-presentation glass-panel">
        <div class="mcp-grid">
          <div class="mcp-card-box">
            <!-- Simulated gold card with tilt rotation style -->
            <div class="sim-card tier-gold">
              <div class="sc-glow"></div>
              <div class="sc-header">
                <span>🛡️ CineGo Premium</span>
                <span class="sc-logo">CineGo Card</span>
              </div>
              <div class="sc-body">
                <span class="sc-number">•••• •••• •••• 8888</span>
                <span class="sc-name">CINEGO MEMBER</span>
              </div>
              <div class="sc-footer">
                <span>GOLD MEMBER</span>
                <span>🏆 PREMIUM</span>
              </div>
            </div>
          </div>

          <div class="mcp-text-box">
            <span class="feature-tag">CHƯƠNG TRÌNH THÀNH VIÊN</span>
            <h2>Thẻ Đặc Quyền Thành Viên CineGo Card</h2>
            <p>
              Đăng ký tài khoản thành viên CineGo ngay hôm nay để tích lũy điểm thưởng (Loyalty Points) sau mỗi lần mua vé và bắp nước. Nâng hạng thành viên (Bronze, Silver, Gold, Diamond) để mở khóa các đặc quyền hấp dẫn:
            </p>
            <ul class="privilege-list">
              <li>🎁 Tặng voucher giảm giá trực tiếp vào ví quà tặng vào ngày sinh nhật.</li>
              <li>🍿 Miễn phí đổi bắp nước size lớn vào các ngày lễ hội CineGo.</li>
              <li>🎫 Nhận vé xem phim miễn phí khi thăng hạng lên thành viên Vàng & Kim Cương.</li>
              <li>⚡ Ưu tiên xếp hàng mua vé và check-in phòng chiếu nhanh chóng tại quầy.</li>
            </ul>
            <div class="mcp-actions">
              <router-link to="/register" class="btn-register-brand">
                Đăng ký thành viên ngay
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.about-page-container {
  width: 100%;
  min-height: 100vh;
  background-color: #ffffff;
  padding-bottom: 60px;
}

/* HERO BANNER */
.about-hero {
  position: relative;
  background: linear-gradient(135deg, #1d0004 0%, #000000 100%);
  padding: 80px 24px;
  border-radius: 24px;
  overflow: hidden;
  margin-bottom: 50px;
  text-align: center;
  border: 1px solid rgba(229, 9, 20, 0.1);
  box-shadow: 0 10px 30px rgba(0,0,0,0.03);
}

.hero-glow {
  position: absolute;
  top: -50%;
  left: -25%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(229, 9, 20, 0.22) 0%, transparent 70%);
  filter: blur(60px);
  pointer-events: none;
}

.hero-content {
  max-width: 850px;
  margin: 0 auto;
}

.hero-subtitle {
  font-size: 11px;
  font-weight: 800;
  color: #e50914;
  letter-spacing: 2px;
  display: block;
  margin-bottom: 12px;
}

.hero-title {
  font-size: 38px;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 16px;
  letter-spacing: -1px;
}

.hero-desc {
  font-size: 16px;
  color: #cbd5e1;
  line-height: 1.6;
  margin-bottom: 36px;
}

.brand-stats {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 24px;
  background: rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 18px 30px;
  border-radius: 16px;
  max-width: 750px;
  margin: 0 auto;
  flex-wrap: wrap;
}

.brand-stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  min-width: 120px;
}

.stat-num {
  font-size: 24px;
  font-weight: 800;
  color: #e50914;
}

.stat-lbl {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 4px;
  white-space: nowrap;
}

.stat-divider {
  width: 1px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
  .stat-divider {
    display: none;
  }
}

/* CONTENT SECTIONS */
.about-content-wrapper {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 16px;
  display: flex;
  flex-direction: column;
  gap: 60px;
}

.feature-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
}

.feature-section.reverse {
  grid-template-columns: 1fr 1fr;
}

.feature-section.reverse .section-text {
  order: 2;
}

@media (max-width: 768px) {
  .feature-section, .feature-section.reverse {
    grid-template-columns: 1fr;
  }
  .feature-section.reverse .section-text {
    order: unset;
  }
}

.feature-tag {
  font-size: 11px;
  font-weight: 800;
  color: #e50914;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 8px;
  display: block;
}

.section-text h2 {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 16px 0;
  line-height: 1.3;
}

.section-text p {
  font-size: 14.5px;
  color: #475569;
  line-height: 1.65;
  margin-bottom: 16px;
}

.section-image {
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(0,0,0,0.04);
  height: 280px;
}

.about-section-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.feature-section:hover .about-section-img {
  transform: scale(1.03);
}

/* MEMBERSHIP CARD */
.member-card-presentation {
  background: #ffffff;
  border: 1px solid rgba(148, 163, 184, 0.12);
  border-radius: 24px;
  padding: 40px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.02);
  margin-top: 20px;
}

.mcp-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 40px;
  align-items: center;
}

@media (max-width: 768px) {
  .mcp-grid {
    grid-template-columns: 1fr;
  }
}

.mcp-card-box {
  display: flex;
  justify-content: center;
}

.sim-card {
  width: 280px;
  height: 170px;
  border-radius: 14px;
  padding: 20px;
  color: #ffffff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 10px 24px rgba(0,0,0,0.15);
  position: relative;
  overflow: hidden;
}

.sc-glow {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
  transform: rotate(25deg);
}

.tier-gold {
  background: linear-gradient(135deg, #ffd700, #b8860b);
}

.sc-header {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  font-weight: 800;
  z-index: 1;
}

.sc-logo {
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sc-body {
  display: flex;
  flex-direction: column;
  z-index: 1;
}

.sc-number {
  font-family: monospace;
  font-size: 16px;
  letter-spacing: 1.5px;
  margin-bottom: 4px;
}

.sc-name {
  font-size: 13px;
  font-weight: 700;
}

.sc-footer {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  font-weight: 850;
  border-top: 1px solid rgba(255,255,255,0.2);
  padding-top: 10px;
  z-index: 1;
}

.mcp-text-box h2 {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 16px 0;
}

.mcp-text-box p {
  font-size: 14.5px;
  color: #475569;
  line-height: 1.6;
  margin-bottom: 20px;
}

.privilege-list {
  list-style: none;
  padding: 0;
  margin: 0 0 28px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.privilege-list li {
  font-size: 13.5px;
  color: #475569;
  padding-left: 20px;
  position: relative;
}

.privilege-list li::before {
  content: '✦';
  position: absolute;
  left: 0;
  color: #e50914;
  font-weight: 800;
}

.btn-register-brand {
  background: #e50914;
  color: #ffffff;
  font-weight: 700;
  font-size: 13px;
  padding: 12px 28px;
  border-radius: 20px;
  box-shadow: 0 4px 12px rgba(229, 9, 20, 0.2);
  transition: all 0.2s;
  display: inline-block;
}

.btn-register-brand:hover {
  background: #ff121f;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(229, 9, 20, 0.3);
}
</style>
