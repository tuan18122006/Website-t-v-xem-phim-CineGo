<template>
  <div class="cg-chat">
    <!-- Nút bong bóng -->
    <transition name="cg-pop">
      <button v-if="!open" class="cg-fab" @click="toggle" aria-label="Mở trợ lý CineGo">
        <span class="cg-fab__icon">💬</span>
        <span class="cg-fab__pulse"></span>
      </button>
    </transition>

    <!-- Khung chat -->
    <transition name="cg-slide">
      <div v-if="open" class="cg-panel">
        <!-- Header -->
        <div class="cg-header">
          <div class="cg-header__brand">
            <span class="cg-header__bot">🎬</span>
            <div>
              <strong>Trợ lý CineGo</strong>
              <span class="cg-header__status">● Trực tuyến</span>
            </div>
          </div>
          <button class="cg-header__close" @click="toggle" aria-label="Đóng">✕</button>
        </div>

        <!-- Danh sách tin nhắn -->
        <div ref="listEl" class="cg-body">
          <div v-for="(m, i) in messages" :key="i" class="cg-msg" :class="m.role === 'user' ? 'is-user' : 'is-bot'">
            <div v-if="m.role === 'bot'" class="cg-msg__avatar">🎬</div>
            <div class="cg-msg__bubble" v-html="format(m.text)"></div>
          </div>

          <div v-if="loading" class="cg-msg is-bot">
            <div class="cg-msg__avatar">🎬</div>
            <div class="cg-msg__bubble cg-typing"><span></span><span></span><span></span></div>
          </div>
        </div>

        <!-- Gợi ý nhanh -->
        <div v-if="messages.length <= 1" class="cg-suggest">
          <button v-for="s in suggestions" :key="s" @click="quickSend(s)">{{ s }}</button>
        </div>

        <!-- Ô nhập -->
        <form class="cg-input" @submit.prevent="send">
          <input
            v-model="draft"
            type="text"
            placeholder="Nhập câu hỏi… (VD: phim nào đang chiếu?)"
            :disabled="loading"
          />
          <button type="submit" :disabled="loading || !draft.trim()" aria-label="Gửi">➤</button>
        </form>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import api from '../api/axios';

const open = ref(false);
const draft = ref('');
const loading = ref(false);
const listEl = ref(null);

const messages = ref([
  { role: 'bot', text: 'Xin chào! 🎬 Mình là **Trợ lý CineGo**. Mình có thể giúp bạn tra **phim đang chiếu**, **lịch chiếu**, **giá vé**, **combo bắp nước** và hướng dẫn đặt vé. Bạn cần gì nào?' },
]);

const suggestions = [
  'Phim nào đang chiếu?',
  'Giá vé bao nhiêu?',
  'Có combo bắp nước gì?',
  'Cách đặt vé thế nào?',
];

const toggle = () => {
  open.value = !open.value;
  if (open.value) scrollDown();
};

const scrollDown = () => {
  nextTick(() => {
    if (listEl.value) listEl.value.scrollTop = listEl.value.scrollHeight;
  });
};

const quickSend = (text) => {
  draft.value = text;
  send();
};

const send = async () => {
  const text = draft.value.trim();
  if (!text || loading.value) return;

  messages.value.push({ role: 'user', text });
  draft.value = '';
  loading.value = true;
  scrollDown();

  // Gửi kèm lịch sử gần nhất (bỏ tin chào mở đầu, giới hạn ~10 lượt)
  const history = messages.value
    .slice(1, -1)
    .slice(-10)
    .map((m) => ({ role: m.role, text: m.text }));

  try {
    const res = await api.post('/chatbot', { message: text, history });
    messages.value.push({ role: 'bot', text: res.data.reply || 'Xin lỗi, mình chưa trả lời được.' });
  } catch (e) {
    console.error('Chatbot error:', e);
    messages.value.push({ role: 'bot', text: 'Có lỗi kết nối rồi 😢. Bạn thử lại sau nhé!' });
  } finally {
    loading.value = false;
    scrollDown();
  }
};

// Định dạng nhẹ: escape HTML rồi cho phép **đậm** + xuống dòng + gạch đầu dòng
const format = (raw) => {
  let s = String(raw)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
  s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  s = s.replace(/^[\s]*[*-]\s+/gm, '• ');
  s = s.replace(/\n/g, '<br>');
  return s;
};
</script>

<style scoped>
.cg-chat { position: fixed; right: 22px; bottom: 22px; z-index: 3000; font-family: inherit; }

/* Nút bong bóng */
.cg-fab {
  position: relative;
  width: 60px; height: 60px; border-radius: 50%;
  border: none; cursor: pointer;
  background: linear-gradient(135deg, #e50914, #9b000e);
  box-shadow: 0 10px 28px rgba(229, 9, 20, 0.45);
  display: grid; place-items: center;
  transition: transform 0.2s;
}
.cg-fab:hover { transform: scale(1.08); }
.cg-fab__icon { font-size: 26px; }
.cg-fab__pulse {
  position: absolute; inset: 0; border-radius: 50%;
  border: 2px solid #e50914; animation: cg-pulse 1.8s ease-out infinite;
}
@keyframes cg-pulse { 0% { transform: scale(1); opacity: 0.7; } 100% { transform: scale(1.6); opacity: 0; } }

/* Khung chat */
.cg-panel {
  width: 370px; max-width: calc(100vw - 32px);
  height: 540px; max-height: calc(100vh - 100px);
  background: #fff; border-radius: 18px; overflow: hidden;
  display: flex; flex-direction: column;
  box-shadow: 0 24px 60px rgba(15, 6, 8, 0.32);
  border: 1px solid rgba(0, 0, 0, 0.06);
}

/* Header */
.cg-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 16px; color: #fff;
  background: linear-gradient(120deg, #9b000e, #e50914);
}
.cg-header__brand { display: flex; align-items: center; gap: 10px; }
.cg-header__bot {
  width: 38px; height: 38px; border-radius: 50%;
  background: rgba(255, 255, 255, 0.18); display: grid; place-items: center; font-size: 20px;
}
.cg-header__brand strong { display: block; font-size: 15px; }
.cg-header__status { font-size: 11px; opacity: 0.85; }
.cg-header__close {
  border: none; background: rgba(255, 255, 255, 0.18); color: #fff;
  width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-size: 13px;
}
.cg-header__close:hover { background: rgba(255, 255, 255, 0.32); }

/* Body */
.cg-body {
  flex: 1; overflow-y: auto; padding: 16px;
  display: flex; flex-direction: column; gap: 12px;
  background: #faf7f8;
}
.cg-msg { display: flex; gap: 8px; align-items: flex-end; max-width: 92%; }
.cg-msg.is-user { align-self: flex-end; flex-direction: row-reverse; }
.cg-msg.is-bot { align-self: flex-start; }
.cg-msg__avatar {
  width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
  display: grid; place-items: center; font-size: 15px;
  background: linear-gradient(135deg, #e50914, #9b000e);
}
.cg-msg__bubble {
  padding: 10px 13px; border-radius: 14px; font-size: 13.5px; line-height: 1.5;
  word-break: break-word;
}
.cg-msg.is-bot .cg-msg__bubble {
  background: #fff; color: #1e293b; border: 1px solid #eee;
  border-bottom-left-radius: 4px;
}
.cg-msg.is-user .cg-msg__bubble {
  background: linear-gradient(135deg, #e50914, #9b000e); color: #fff;
  border-bottom-right-radius: 4px;
}
.cg-msg__bubble :deep(strong) { font-weight: 700; }

/* Typing */
.cg-typing { display: flex; gap: 4px; align-items: center; }
.cg-typing span {
  width: 7px; height: 7px; border-radius: 50%; background: #cbd5e1;
  animation: cg-blink 1.2s infinite both;
}
.cg-typing span:nth-child(2) { animation-delay: 0.2s; }
.cg-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes cg-blink { 0%, 80%, 100% { opacity: 0.3; } 40% { opacity: 1; } }

/* Gợi ý nhanh */
.cg-suggest { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 14px 6px; background: #faf7f8; }
.cg-suggest button {
  border: 1px solid #f3c9cd; background: #fff; color: #9b000e;
  font-size: 12px; font-weight: 600; padding: 6px 11px; border-radius: 999px; cursor: pointer;
}
.cg-suggest button:hover { background: #fde2e5; }

/* Input */
.cg-input {
  display: flex; align-items: center; gap: 8px; padding: 12px;
  border-top: 1px solid #eee; background: #fff;
}
.cg-input input {
  flex: 1; border: 1.5px solid #ececf1; border-radius: 999px;
  padding: 10px 14px; font-size: 13.5px; outline: none;
}
.cg-input input:focus { border-color: #e50914; }
.cg-input button {
  width: 40px; height: 40px; border-radius: 50%; border: none; flex-shrink: 0;
  background: linear-gradient(135deg, #e50914, #9b000e); color: #fff;
  font-size: 15px; cursor: pointer;
}
.cg-input button:disabled { opacity: 0.5; cursor: not-allowed; }

/* Transitions */
.cg-pop-enter-active, .cg-pop-leave-active { transition: transform 0.2s, opacity 0.2s; }
.cg-pop-enter-from, .cg-pop-leave-to { transform: scale(0); opacity: 0; }
.cg-slide-enter-active { transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s; }
.cg-slide-leave-active { transition: transform 0.2s, opacity 0.2s; }
.cg-slide-enter-from, .cg-slide-leave-to { transform: translateY(20px) scale(0.96); opacity: 0; }

@media (max-width: 480px) {
  .cg-chat { right: 14px; bottom: 14px; }
  .cg-panel { height: calc(100vh - 90px); }
}
</style>
