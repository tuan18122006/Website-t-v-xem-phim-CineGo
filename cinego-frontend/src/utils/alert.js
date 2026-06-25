import Swal from 'sweetalert2';

// Thanh thông báo trượt xuống từ trên cùng
export const toast = (title, icon = 'success') => {
  Swal.fire({
    title,
    icon,
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
  });
};

// Hộp thoại xác nhận xóa ở giữa màn hình
export const confirmDialog = async (title = 'Bạn có chắc chắn?', text = 'Hành động này không thể hoàn tác!') => {
  const result = await Swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Đồng ý',
    cancelButtonText: 'Hủy'
  });
  return result.isConfirmed;
};
