import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Spinner } from '../ui';
import { ArrowRightRegular, ArrowSyncRegular, BookRegular, CheckmarkCircleRegular, ClipboardTaskListLtrRegular, PeopleRegular } from '../ui/icons';
import { useNavigate, useOutletContext } from 'react-router-dom';
import { apiRequest } from '../api';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage } from '../components/Feedback';

const thaiDate = (value) => value ? new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium' }).format(new Date(value)) : 'ไม่ระบุ';

export default function DashboardPage() {
    const { user, apiBase } = useOutletContext();
    const navigate = useNavigate();
    const query = useQuery({ queryKey: ['dashboard'], queryFn: () => apiRequest(`${apiBase}/dashboard`) });
    const data = query.data?.data;

    if (query.isLoading) return <div className="state-panel tall"><Spinner label="กำลังเตรียมภาพรวมระบบ" /></div>;

    const stats = [
        ['หลักสูตรทั้งหมด', data?.totals?.courses ?? 0, BookRegular, '/courses'],
        ['กลุ่มกิจกรรม', data?.totals?.projects ?? 0, ClipboardTaskListLtrRegular, '/projects'],
        ['ผู้เรียน', data?.totals?.students ?? 0, PeopleRegular, '/students'],
        [user.role === 'subdistrict_admin' ? 'รายการรออนุมัติ' : 'รอพิจารณา', (data?.approvals?.courses_pending ?? 0) + (data?.approvals?.projects_pending ?? 0), CheckmarkCircleRegular, user.role === 'subdistrict_admin' ? '/courses' : '/approvals'],
    ];

    return (
        <>
            <PageHeader
                eyebrow="ภาพรวมระบบ"
                title={`สวัสดี ${user.teacher_name || user.display_name}`}
                description="ติดตามงานหลักสูตร การจัดตั้งกลุ่ม และรายการที่ต้องดำเนินการจากจุดเดียว"
                actions={<Button icon={<ArrowSyncRegular />} onClick={() => query.refetch()}>อัปเดตข้อมูล</Button>}
            />
            <ErrorMessage message={query.error?.message} />
            <section className="stat-grid">
                {stats.map(([label, value, Icon, to], index) => (
                    <Card
                        key={label}
                        className="stat-card"
                        data-tone={index + 1}
                        role="link"
                        tabIndex={0}
                        onClick={() => navigate(to)}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                navigate(to);
                            }
                        }}
                    >
                        <span className="stat-icon"><Icon /></span>
                        <div className="stat-copy"><span>{label}</span><strong>{value.toLocaleString('th-TH')}</strong></div>
                        <ArrowRightRegular className="stat-arrow" />
                    </Card>
                ))}
            </section>
            <section className="dashboard-grid">
                <Card className="panel-card">
                    <div className="panel-heading"><div><h2>หลักสูตรล่าสุด</h2><p>รายการที่มีการแก้ไขหรือส่งอนุมัติล่าสุด</p></div><Button appearance="subtle" onClick={() => navigate('/courses')}>ดูทั้งหมด</Button></div>
                    <div className="compact-list">
                        {(data?.recent_courses ?? []).map((course) => (
                            <button key={course.id} onClick={() => navigate('/courses')}>
                                <span><strong>{course.name}</strong><small>{course.creator?.school_name || course.owner}</small></span>
                                <StatusBadge value={course.approval_status} />
                            </button>
                        ))}
                        {!data?.recent_courses?.length ? <div className="empty-inline">ยังไม่มีหลักสูตร</div> : null}
                    </div>
                </Card>
                <Card className="panel-card">
                    <div className="panel-heading"><div><h2>กิจกรรมที่กำลังมาถึง</h2><p>เรียงตามวันเริ่มกิจกรรม</p></div><Button appearance="subtle" onClick={() => navigate('/projects')}>ดูทั้งหมด</Button></div>
                    <div className="compact-list">
                        {(data?.upcoming ?? []).map((project) => (
                            <button key={project.id} onClick={() => navigate(`/projects?project=${project.id}`)}>
                                <span><strong>{project.title}</strong><small>{thaiDate(project.start_date)} · {project.place}</small></span>
                                <StatusBadge value={project.approval_status} />
                            </button>
                        ))}
                        {!data?.upcoming?.length ? <div className="empty-inline">ยังไม่มีกิจกรรมที่กำลังมาถึง</div> : null}
                    </div>
                </Card>
            </section>
        </>
    );
}
