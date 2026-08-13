<?php
$file = 'src/views/admin/ShowtimesView.vue';
$content = file_get_contents($file);
$old = '<label>Các khung giờ chiếu <i>*</i></label>
                  <div class="times-container">';
$new = '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label style="margin-bottom: 0;">Các khung giờ chiếu <i>*</i></label>
                    <button type="button" @click="addTimeSlot" class="btn-add-time" style="padding: 4px 10px; font-size: 0.85rem;">+ Thêm khung giờ</button>
                  </div>
                  <div class="times-container">';
$content = str_replace($old, $new, $content);

$old2 = '<button type="button" @click="addTimeSlot" class="btn-add-time">+ Thêm khung giờ</button>';
$content = str_replace($old2, '', $content);
file_put_contents($file, $content);
echo "Replaced";
