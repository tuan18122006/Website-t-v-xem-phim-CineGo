import api from './axios';

export const getTrashItems = () => api.get('/admin/trash');
export const restoreTrashItem = (type, id) => api.post(`/admin/trash/${type}/${id}/restore`);
export const forceDeleteTrashItem = (type, id) => api.delete(`/admin/trash/${type}/${id}`);
export const emptyTrash = () => api.delete('/admin/trash/empty');