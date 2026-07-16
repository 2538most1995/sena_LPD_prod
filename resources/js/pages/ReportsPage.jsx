import React, { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Card, Input } from '../ui';
import {
    AddRegular, ArrowLeftRegular, CalendarRegular, DeleteRegular, DocumentBulletListRegular,
    DocumentPdfRegular, EditRegular, EyeRegular, OpenRegular, PeopleRegular, SearchRegular,
} from '../ui/icons';
import { useNavigate, useOutletContext, useSearchParams } from 'react-router-dom';
import { apiRequest, firstError } from '../api';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const ptReports = [
    'พต.1 แบบสำรวจความต้องการ', 'พต.2 ใบสมัครผู้เรียน', 'พต.3 หลักสูตรฝึกอบรม', 'พต.4 หนังสือเชิญวิทยากร',
    'พต.5 แบบเขียนโครงการฝึกอบรม', 'พต.6 บันทึกขออนุมัติโครงการ', 'พต.7 บันทึกขออนุมัติจัดฝึกอบรม',
    'พต.8 ทะเบียนรายชื่อผู้เรียน', 'พต.9(1) บัญชีลงเวลาผู้เรียน', 'พต.9 บัญชีลงเวลาวิทยากร',
    'พต.10 แบบประเมินผล', 'พต.11 บันทึกการนิเทศ', 'พต.12 แบบประเมินความพึงพอใจ',
    'พต.13 แบบรายงานผลการฝึกอบรม', 'พต.14 ใบรับรองผ่านการฝึกอบรม', 'พต.15 หนังสือส่งหลักฐานเบิกจ่าย',
    'พต.16 บันทึกขอเบิกจ่าย', 'พต.17 สรุปงบหน้าเบิกเงิน', 'พต.18 ใบสำคัญรับเงิน',
    'พต.19 ใบสำคัญรับเงินและแบบ KTB', 'พต.20 แบบติดตามผู้ผ่านการอบรม', 'พต.21 ประกาศแหล่งเรียนรู้',
    'พต.22 ข้อตกลงความร่วมมือ',
];
const specialReports = [
    ['open', 0, 'บันทึกขออนุมัติจัดฝึกอบรม (เปิดกลุ่ม)'],
    ['open', 2, 'หนังสือเชิญวิทยากรและแบบตอบรับ'],
    ['time', 1, 'ใบลงเวลาผู้เรียนฉบับเปล่า'],
    ['time', 2, 'ใบลงเวลาผู้เรียน 2 วัน'],
    ['time', 3, 'ใบลงเวลาวิทยากรฉบับเปล่า'],
    ['photo', 0, 'รายงานภาพวัสดุ'],
    ['photo', 1, 'รายงานภาพกิจกรรม'],
];
const thaiDate = (value) => value ? new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium' }).format(new Date(value)) : 'ไม่ระบุ';

export default function ReportsPage() {
    const { user, apiBase } = useOutletContext();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [searchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const projectId = searchParams.get('project') || '';
    const projects = useQuery({ queryKey: ['projects', 'report-list'], queryFn: () => apiRequest(`${apiBase}/projects?per_page=100`) });
    const selectedProject = projects.data?.data?.find((project) => String(project.id) === String(projectId));
    const canManage = (project) => user.role === 'super_admin' || Number(project.created_by) === Number(user.id);

    const remove = useMutation({
        mutationFn: (id) => apiRequest(`${apiBase}/projects/${id}`, { method: 'DELETE' }),
        onSuccess: (result) => {
            queryClient.invalidateQueries({ queryKey: ['projects'] });
            setFeedback(result.message);
            setError('');
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const visibleProjects = useMemo(() => {
        const term = search.trim().toLocaleLowerCase('th-TH');
        return (projects.data?.data ?? []).filter((project) => {
            const matchesStatus = status === 'all' || project.approval_status === status;
            const haystack = `${project.title} ${project.course?.name ?? ''} ${project.place ?? ''}`.toLocaleLowerCase('th-TH');
            return matchesStatus && (!term || haystack.includes(term));
        });
    }, [projects.data, search, status]);

    const groupedProjects = useMemo(() => [
        ['approved', 'พร้อมจัดทำเอกสาร', 'กลุ่มที่อนุมัติแล้วและพร้อมสร้างเอกสารจากข้อมูลจริง'],
        ['pending', 'รอการอนุมัติ', 'ตรวจสอบข้อมูลได้ แต่ควรรอการอนุมัติก่อนใช้เอกสาร'],
        ['revision', 'ส่งกลับแก้ไข', 'แก้ไขรายละเอียดกลุ่มแล้วส่งให้อำเภอพิจารณาอีกครั้ง'],
    ].map(([key, title, description]) => ({ key, title, description, items: visibleProjects.filter((project) => project.approval_status === key) }))
        .filter((group) => status === 'all' ? group.items.length > 0 : group.key === status), [visibleProjects, status]);

    const openReport = (type, doc, needsProject = true) => {
        if (needsProject && !selectedProject) return;
        const query = new URLSearchParams({ type, doc: String(doc) });
        if (selectedProject) query.set('project_id', selectedProject.id);
        window.open(`${apiBase}/reports/open?${query.toString()}`, '_blank', 'noopener,noreferrer');
    };

    const openDocuments = (project) => {
        setFeedback('');
        setError('');
        navigate(`/reports?project=${encodeURIComponent(project.id)}`);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    if (projectId && selectedProject) {
        return (
            <>
                <PageHeader
                    eyebrow="ศูนย์จัดการเอกสาร"
                    title={selectedProject.title}
                    description={`${selectedProject.course?.name ?? 'ไม่ระบุหลักสูตร'} · ${thaiDate(selectedProject.start_date)} ถึง ${thaiDate(selectedProject.end_date)}`}
                    actions={<Button icon={<ArrowLeftRegular />} onClick={() => navigate('/reports')}>กลับรายการกลุ่ม</Button>}
                />
                <section className="report-workspace-summary">
                    <div className="report-workspace-copy"><span>กลุ่มที่กำลังจัดการ</span><strong>{selectedProject.title}</strong><small>{selectedProject.place || 'ยังไม่ได้ระบุสถานที่'}</small></div>
                    <div><PeopleRegular /><span>ผู้เรียน</span><strong>{selectedProject.students_count ?? 0} คน</strong></div>
                    <div><CalendarRegular /><span>ปีงบประมาณ</span><strong>{selectedProject.fiscal_year}</strong></div>
                    <div><DocumentPdfRegular /><span>เอกสารพร้อมใช้</span><strong>{ptReports.length + specialReports.length} รายการ</strong></div>
                </section>
                <section className="report-document-panel">
                    <div className="report-document-intro">
                        <div><span><DocumentBulletListRegular /></span><div><h2>แบบฟอร์ม พต.1 ถึง พต.22</h2><p>สร้างเอกสารราชการโดยดึงข้อมูลกลุ่ม ผู้เรียน วิทยากร และงบประมาณอัตโนมัติ</p></div></div>
                        <Button icon={<DocumentBulletListRegular />} onClick={() => openReport('blank', 0, false)}>ชุดแบบฟอร์มเปล่า</Button>
                    </div>
                    <div className="report-grid">{ptReports.map((title, index) => <Card key={title} className="report-card" style={{ '--index': index % 6 }}><span>{String(index + 1).padStart(2, '0')}</span><strong>{title}</strong><Button appearance="subtle" icon={<OpenRegular />} onClick={() => openReport('pt', index)}>เปิด PDF</Button></Card>)}</div>
                </section>
                <section className="report-document-panel">
                    <div className="report-document-intro"><div><span><DocumentPdfRegular /></span><div><h2>เอกสารประกอบและรายงานผล</h2><p>ใบลงเวลา หนังสือเชิญ และรายงานภาพที่ใช้ระหว่างดำเนินกิจกรรม</p></div></div><small>{specialReports.length} รายการ</small></div>
                    <div className="report-grid">{specialReports.map(([type, doc, title], index) => <Card key={title} className="report-card" style={{ '--index': index % 6 }}><span>{String(index + 1).padStart(2, '0')}</span><strong>{title}</strong><Button appearance="subtle" icon={<OpenRegular />} onClick={() => openReport(type, doc)}>เปิด PDF</Button></Card>)}</div>
                </section>
            </>
        );
    }

    return (
        <>
            <PageHeader eyebrow="ศูนย์เอกสารทางราชการ" title="จัดการเอกสารแยกตามกลุ่ม" description="เลือกกลุ่มกิจกรรมก่อน จึงจะแสดงแบบฟอร์มและรายงาน PDF ที่สัมพันธ์กับข้อมูลของกลุ่มนั้น" actions={<Button appearance="primary" icon={<AddRegular />} onClick={() => navigate('/projects', { state: { createProject: true } })}>สร้างกลุ่มใหม่</Button>} />
            <SuccessMessage message={feedback} />
            <ErrorMessage message={error || projects.error?.message} />
            <section className="report-manager-toolbar content-card">
                <div><Input contentBefore={<SearchRegular />} placeholder="ค้นหาชื่อกลุ่ม หลักสูตร หรือสถานที่" value={search} onChange={(_, data) => setSearch(data.value)} /></div>
                <div className="report-status-filter">
                    {[['all', 'ทั้งหมด'], ['approved', 'อนุมัติแล้ว'], ['pending', 'รออนุมัติ'], ['revision', 'ส่งกลับแก้ไข']].map(([value, label]) => <button key={value} type="button" className={status === value ? 'is-active' : ''} onClick={() => setStatus(value)}>{label}</button>)}
                </div>
                <span>{visibleProjects.length} กลุ่ม</span>
            </section>

            {projects.isLoading ? <section className="state-panel content-card">กำลังโหลดรายการกลุ่มกิจกรรม</section> : groupedProjects.length ? groupedProjects.map((group) => (
                <section className="report-group-section" key={group.key}>
                    <div className="report-group-heading"><div><h2>{group.title}</h2><p>{group.description}</p></div><span>{group.items.length} กลุ่ม</span></div>
                    <div className="report-project-grid">{group.items.map((project) => (
                        <Card className="report-project-card" key={project.id}>
                            <div className="report-project-card-top"><StatusBadge value={project.approval_status} /><span>ปี {project.fiscal_year}</span></div>
                            <div className="report-project-title"><h3>{project.title}</h3><p>{project.course?.name || 'ไม่ระบุหลักสูตร'}</p></div>
                            <dl className="report-project-meta"><div><dt>ช่วงดำเนินการ</dt><dd>{thaiDate(project.start_date)} ถึง {thaiDate(project.end_date)}</dd></div><div><dt>ผู้เรียน</dt><dd>{project.students_count ?? 0} คน</dd></div><div><dt>สถานที่</dt><dd>{project.place || 'ไม่ระบุ'}</dd></div></dl>
                            <div className="report-project-actions">
                                <Button appearance="primary" icon={<DocumentPdfRegular />} onClick={() => openDocuments(project)}>จัดการเอกสาร</Button>
                                <Button appearance="subtle" icon={<EyeRegular />} aria-label="ดูข้อมูลกลุ่ม" onClick={() => navigate(`/projects/${project.id}`)} />
                                {canManage(project) ? <><Button appearance="subtle" icon={<EditRegular />} aria-label="แก้ไขกลุ่ม" onClick={() => navigate('/projects', { state: { editProject: project } })} /><Button appearance="subtle" icon={<DeleteRegular />} aria-label="ลบกลุ่ม" disabled={remove.isPending} onClick={() => window.confirm(`ยืนยันลบ ${project.title}`) && remove.mutate(project.id)} /></> : null}
                            </div>
                        </Card>
                    ))}</div>
                </section>
            )) : <section className="state-panel empty-state content-card"><strong>ไม่พบกลุ่มกิจกรรม</strong><span>ลองเปลี่ยนคำค้นหาหรือตัวกรองสถานะ หรือสร้างกลุ่มกิจกรรมใหม่</span></section>}
        </>
    );
}
