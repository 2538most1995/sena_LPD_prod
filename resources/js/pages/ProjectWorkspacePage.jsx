import React, { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Card, Field, Input, Tab, TabList } from '@fluentui/react-components';
import { ArrowLeftRegular, DeleteRegular, DocumentPdfRegular, SaveRegular } from '@fluentui/react-icons';
import { useNavigate, useOutletContext, useParams } from 'react-router-dom';
import { apiRequest, firstError } from '../api';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

export default function ProjectWorkspacePage() {
    const { id } = useParams();
    const { apiBase } = useOutletContext();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [tab, setTab] = useState('overview');
    const [studentIds, setStudentIds] = useState([]);
    const [scores, setScores] = useState({});
    const [photos, setPhotos] = useState([]);
    const [photoType, setPhotoType] = useState('activity');
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const projectQuery = useQuery({ queryKey: ['project', id], queryFn: () => apiRequest(`${apiBase}/projects/${id}`) });
    const refs = useQuery({ queryKey: ['references'], queryFn: () => apiRequest(`${apiBase}/references`) });
    const project = projectQuery.data?.data;
    useEffect(() => {
        if (!project) return;
        setStudentIds(project.students?.map((student) => student.id) ?? []);
        const next = {};
        project.students?.forEach((student) => { next[student.id] = student.scores ?? { knowledge: 0, skill: 0, attribute: 0 }; });
        setScores(next);
    }, [project]);
    const refresh = () => queryClient.invalidateQueries({ queryKey: ['project', id] });
    const participantsMutation = useMutation({ mutationFn: () => apiRequest(`${apiBase}/projects/${id}/participants`, { method: 'PUT', body: { student_ids: studentIds } }), onSuccess: (result) => { refresh(); setFeedback(result.message); setError(''); }, onError: (requestError) => setError(firstError(requestError)) });
    const scoresMutation = useMutation({ mutationFn: () => apiRequest(`${apiBase}/projects/${id}/scores`, { method: 'PUT', body: { scores: studentIds.map((studentId) => ({ student_id: studentId, knowledge: Number(scores[studentId]?.knowledge ?? 0), skill: Number(scores[studentId]?.skill ?? 0), attribute: Number(scores[studentId]?.attribute ?? 0) })) } }), onSuccess: (result) => { refresh(); setFeedback(result.message); setError(''); }, onError: (requestError) => setError(firstError(requestError)) });
    const photosMutation = useMutation({ mutationFn: () => { const form = new FormData(); form.append('photo_type', photoType); Array.from(photos).forEach((file) => form.append('photos[]', file)); return apiRequest(`${apiBase}/projects/${id}/photos`, { method: 'POST', body: form }); }, onSuccess: (result) => { refresh(); setPhotos([]); setFeedback(result.message); setError(''); }, onError: (requestError) => setError(firstError(requestError)) });
    const deletePhoto = async (photoId) => { await apiRequest(`${apiBase}/projects/${id}/photos/${photoId}`, { method: 'DELETE' }); refresh(); };
    const toggleStudent = (studentId) => setStudentIds((current) => current.includes(studentId) ? current.filter((value) => value !== studentId) : [...current, studentId]);
    const updateScore = (studentId, key, value) => setScores((current) => ({ ...current, [studentId]: { ...(current[studentId] ?? {}), [key]: value } }));
    const openPt7 = () => window.open(`${apiBase}/reports/open?type=pt&doc=6&project_id=${id}`, '_blank', 'noopener,noreferrer');

    return <>
        <PageHeader eyebrow="พื้นที่จัดการกลุ่มกิจกรรม" title={project?.title || 'กำลังโหลดข้อมูล'} description={project ? `${project.course?.name} · ${project.place}` : ''} actions={<><Button icon={<ArrowLeftRegular />} onClick={() => navigate('/projects')}>กลับรายการ</Button><Button appearance="primary" icon={<DocumentPdfRegular />} onClick={openPt7}>สร้าง พต.7</Button></>} />
        <SuccessMessage message={feedback} /><ErrorMessage message={error || projectQuery.error?.message} />
        {project ? <><section className="project-summary"><Card><span>สถานะอนุมัติ</span><StatusBadge value={project.approval_status} /></Card><Card><span>ผู้เรียน</span><strong>{project.students?.length ?? 0} คน</strong></Card><Card><span>วิทยากร</span><strong>{project.lecturer ? `${project.lecturer.prefix}${project.lecturer.first_name} ${project.lecturer.last_name}` : 'ไม่ระบุ'}</strong></Card><Card><span>งบประมาณ</span><strong>{Number(project.total_budget).toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท</strong></Card></section>
        <TabList selectedValue={tab} onTabSelect={(_, data) => setTab(data.value)}><Tab value="overview">ข้อมูลกลุ่ม</Tab><Tab value="participants">ผู้เรียน</Tab><Tab value="scores">คะแนน</Tab><Tab value="photos">ภาพกิจกรรม</Tab></TabList>
        {tab === 'overview' ? <section className="content-card"><dl className="detail-grid large"><div><dt>รูปแบบการจัด</dt><dd>{project.format_type}</dd></div><div><dt>ปีงบประมาณ</dt><dd>{project.fiscal_year}</dd></div><div><dt>วันเริ่ม</dt><dd>{project.start_date?.slice(0, 10)}</dd></div><div><dt>วันสิ้นสุด</dt><dd>{project.end_date?.slice(0, 10)}</dd></div><div className="full"><dt>วัตถุประสงค์</dt><dd>{project.objective || 'ไม่ระบุ'}</dd></div><div className="full"><dt>สถานที่</dt><dd>{project.place} {project.address}</dd></div></dl></section> : null}
        {tab === 'participants' ? <section className="content-card"><div className="section-heading"><div><h2>เลือกผู้เรียนเข้ากลุ่ม</h2><p>เลือกได้หลายคนจากฐานข้อมูลผู้เรียน</p></div><Button appearance="primary" icon={<SaveRegular />} onClick={() => participantsMutation.mutate()} disabled={participantsMutation.isPending}>บันทึกรายชื่อ</Button></div><div className="selection-list">{refs.data?.data?.students?.map((student) => <label key={student.id}><input type="checkbox" checked={studentIds.includes(student.id)} onChange={() => toggleStudent(student.id)} /><span><strong>{student.prefix}{student.first_name} {student.last_name}</strong><small>{student.id_card}</small></span></label>)}</div></section> : null}
        {tab === 'scores' ? <section className="content-card"><div className="section-heading"><div><h2>บันทึกคะแนนผู้เรียน</h2><p>ความรู้ 20 คะแนน ทักษะ 40 คะแนน คุณลักษณะ 40 คะแนน</p></div><Button appearance="primary" icon={<SaveRegular />} onClick={() => scoresMutation.mutate()} disabled={!studentIds.length || scoresMutation.isPending}>บันทึกคะแนน</Button></div><div className="score-table"><div className="score-head"><span>ผู้เรียน</span><span>ความรู้</span><span>ทักษะ</span><span>คุณลักษณะ</span><span>รวม</span></div>{project.students?.map((student) => { const score = scores[student.id] ?? {}; const total = Number(score.knowledge || 0) + Number(score.skill || 0) + Number(score.attribute || 0); return <div className="score-row" key={student.id}><strong>{student.prefix}{student.first_name} {student.last_name}</strong><Input type="number" min="0" max="20" value={String(score.knowledge ?? 0)} onChange={(_, data) => updateScore(student.id, 'knowledge', data.value)} /><Input type="number" min="0" max="40" value={String(score.skill ?? 0)} onChange={(_, data) => updateScore(student.id, 'skill', data.value)} /><Input type="number" min="0" max="40" value={String(score.attribute ?? 0)} onChange={(_, data) => updateScore(student.id, 'attribute', data.value)} /><b>{total}</b></div>; })}{!project.students?.length ? <div className="state-panel empty-state"><strong>กรุณาเพิ่มผู้เรียนเข้ากลุ่มก่อนบันทึกคะแนน</strong></div> : null}</div></section> : null}
        {tab === 'photos' ? <section className="content-card"><div className="section-heading"><div><h2>ภาพวัสดุและภาพกิจกรรม</h2><p>อัปโหลดครั้งละไม่เกิน 4 ภาพ ภาพละไม่เกิน 5 MB</p></div></div><div className="photo-upload"><Field label="ประเภทภาพ"><select className="native-select" value={photoType} onChange={(event) => setPhotoType(event.target.value)}><option value="activity">ภาพกิจกรรม</option><option value="material">ภาพวัสดุ</option></select></Field><Field label="เลือกรูปภาพ"><input className="native-file" type="file" multiple accept="image/jpeg,image/png,image/webp" onChange={(event) => setPhotos(event.target.files)} /></Field><Button appearance="primary" disabled={!photos.length || photosMutation.isPending} onClick={() => photosMutation.mutate()}>อัปโหลดภาพ</Button></div><div className="photo-grid">{project.photos?.map((photo) => <figure key={photo.id}><img src={photo.url} alt={photo.caption || 'ภาพกิจกรรม'} /><figcaption>{photo.photo_type === 'material' ? 'ภาพวัสดุ' : 'ภาพกิจกรรม'}<Button appearance="subtle" icon={<DeleteRegular />} aria-label="ลบภาพ" onClick={() => window.confirm('ยืนยันลบภาพนี้') && deletePhoto(photo.id)} /></figcaption></figure>)}</div></section> : null}</> : null}
    </>;
}
