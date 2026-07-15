import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Button, Card, Field } from '@fluentui/react-components';
import { DocumentBulletListRegular, OpenRegular } from '@fluentui/react-icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest } from '../api';
import PageHeader from '../components/PageHeader';

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

export default function ReportsPage() {
    const { apiBase } = useOutletContext();
    const [projectId, setProjectId] = useState('');
    const projects = useQuery({ queryKey: ['projects', 'report-list'], queryFn: () => apiRequest(`${apiBase}/projects?per_page=100`) });
    const openReport = (type, doc, needsProject = true) => {
        if (needsProject && !projectId) { window.alert('กรุณาเลือกกลุ่มกิจกรรมก่อนสร้างเอกสาร'); return; }
        const query = new URLSearchParams({ type, doc: String(doc) });
        if (projectId) query.set('project_id', projectId);
        window.open(`${apiBase}/reports/open?${query.toString()}`, '_blank', 'noopener,noreferrer');
    };
    return <>
        <PageHeader eyebrow="เอกสารทางราชการ" title="เอกสารและรายงาน PDF" description="สร้างแบบฟอร์ม พต. และเอกสารประกอบจากข้อมูลจริงของกลุ่มกิจกรรม" actions={<Button icon={<DocumentBulletListRegular />} onClick={() => openReport('blank', 0, false)}>แบบฟอร์มเปล่า พต.1 ถึง พต.22</Button>} />
        <section className="report-selector content-card"><Field label="เลือกกลุ่มกิจกรรมที่ต้องการสร้างเอกสาร"><select className="native-select" value={projectId} onChange={(event) => setProjectId(event.target.value)}><option value="">เลือกกลุ่มกิจกรรม</option>{projects.data?.data?.map((project) => <option key={project.id} value={project.id}>{project.title} · {project.course?.name}</option>)}</select></Field></section>
        <section className="report-section"><div className="section-heading"><h2>แบบฟอร์ม พต.1 ถึง พต.22</h2><span>{ptReports.length} แบบฟอร์ม</span></div><div className="report-grid">{ptReports.map((title, index) => <Card key={title} className="report-card"><span>{String(index + 1).padStart(2, '0')}</span><strong>{title}</strong><Button appearance="subtle" icon={<OpenRegular />} onClick={() => openReport('pt', index)}>เปิด PDF</Button></Card>)}</div></section>
        <section className="report-section"><div className="section-heading"><h2>เอกสารประกอบกิจกรรม</h2><span>{specialReports.length} รายการ</span></div><div className="report-grid">{specialReports.map(([type, doc, title], index) => <Card key={title} className="report-card"><span>{String(index + 1).padStart(2, '0')}</span><strong>{title}</strong><Button appearance="subtle" icon={<OpenRegular />} onClick={() => openReport(type, doc)}>เปิด PDF</Button></Card>)}</div></section>
    </>;
}
