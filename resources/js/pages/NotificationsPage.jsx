import React from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Card } from '../ui';
import { AlertRegular, CheckmarkRegular } from '../ui/icons';
import { useNavigate, useOutletContext } from 'react-router-dom';
import { apiRequest } from '../api';
import PageHeader from '../components/PageHeader';
import { ErrorMessage } from '../components/Feedback';

const thaiDateTime = (value) => new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));

export default function NotificationsPage() {
    const { apiBase } = useOutletContext();
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const query = useQuery({ queryKey: ['notifications'], queryFn: () => apiRequest(`${apiBase}/notifications?per_page=100`) });
    const markAll = useMutation({ mutationFn: () => apiRequest(`${apiBase}/notifications/read-all`, { method: 'POST' }), onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }) });
    const open = async (item) => {
        if (!item.is_read) await apiRequest(`${apiBase}/notifications/${item.id}/read`, { method: 'POST' });
        queryClient.invalidateQueries({ queryKey: ['notifications'] });
        if (item.link) navigate(item.link);
    };
    const unread = query.data?.data?.filter((item) => !item.is_read).length ?? 0;
    return <>
        <PageHeader eyebrow="ศูนย์การแจ้งเตือน" title="การแจ้งเตือน" description="ติดตามคำขอที่เข้ามา ผลการอนุมัติ และรายการที่ต้องดำเนินการ" actions={<Button icon={<CheckmarkRegular />} disabled={!unread || markAll.isPending} onClick={() => markAll.mutate()}>อ่านทั้งหมด</Button>} />
        <ErrorMessage message={query.error?.message} />
        <section className="notification-list">{query.data?.data?.map((item) => <Card key={item.id} className={`notification-card ${item.is_read ? '' : 'unread'}`} onClick={() => open(item)} tabIndex={0}><span className="notification-icon"><AlertRegular /></span><div><strong>{item.title}</strong><p>{item.message}</p><small>{thaiDateTime(item.created_at)}</small></div>{!item.is_read ? <span className="unread-dot" aria-label="ยังไม่ได้อ่าน" /> : null}</Card>)}{!query.isLoading && !query.data?.data?.length ? <div className="state-panel empty-state"><strong>ไม่มีการแจ้งเตือน</strong><span>เมื่อมีคำขอหรือผลการอนุมัติ ระบบจะแสดงที่หน้านี้</span></div> : null}</section>
    </>;
}
