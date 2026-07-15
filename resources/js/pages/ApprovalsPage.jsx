import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Card, Dialog, DialogActions, DialogBody, DialogContent, DialogSurface, DialogTitle, Field, Tab, TabList, Textarea } from '@fluentui/react-components';
import { CheckmarkCircleRegular, DocumentPdfRegular, DocumentWordRegular, DismissCircleRegular } from '@fluentui/react-icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError } from '../api';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const thaiDateTime = (value) => value ? new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'ไม่ระบุ';

function RequestCard({ type, item, apiBase, onReview, history = false }) {
    const isCourse = type === 'course';
    return <Card className="approval-card">
        <div className="approval-card-top"><div><span>{isCourse ? 'คำขอหลักสูตร' : 'คำขอจัดตั้งกลุ่ม'}</span><h3>{isCourse ? item.name : item.title}</h3></div><StatusBadge value={item.approval_status} /></div>
        <dl className="detail-grid">
            <div><dt>หน่วยงาน</dt><dd>{item.creator?.school_name || item.owner}</dd></div>
            <div><dt>{isCourse ? 'จำนวนชั่วโมง' : 'หลักสูตร'}</dt><dd>{isCourse ? `${item.hours} ชั่วโมง` : item.course?.name}</dd></div>
            {!isCourse ? <><div><dt>สถานที่</dt><dd>{item.place}</dd></div><div><dt>งบประมาณ</dt><dd>{Number(item.total_budget ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท</dd></div></> : null}
            <div><dt>{history ? 'วันที่พิจารณา' : 'วันที่ส่ง'}</dt><dd>{thaiDateTime(history ? item.reviewed_at : item.submitted_at)}</dd></div>
            {item.review_note ? <div className="full"><dt>หมายเหตุ</dt><dd>{item.review_note}</dd></div> : null}
        </dl>
        {isCourse && (item.word_attachment_path || item.pdf_attachment_path) ? <div className="attachment-list approval-files">{item.word_attachment_path ? <a href={`${apiBase}/courses/${item.id}/files/word`} target="_blank" rel="noreferrer"><DocumentWordRegular />เปิดไฟล์ Word</a> : null}{item.pdf_attachment_path ? <a href={`${apiBase}/courses/${item.id}/files/pdf`} target="_blank" rel="noreferrer"><DocumentPdfRegular />เปิดไฟล์ PDF</a> : null}</div> : null}
        {!history ? <div className="approval-actions"><Button appearance="secondary" icon={<DismissCircleRegular />} onClick={() => onReview(type, item, 'revision')}>ส่งกลับแก้ไข</Button><Button appearance="primary" icon={<CheckmarkCircleRegular />} onClick={() => onReview(type, item, 'approved')}>อนุมัติ</Button></div> : null}
    </Card>;
}

export default function ApprovalsPage() {
    const { apiBase } = useOutletContext();
    const queryClient = useQueryClient();
    const [tab, setTab] = useState('pending');
    const [review, setReview] = useState(null);
    const [note, setNote] = useState('');
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const query = useQuery({ queryKey: ['approvals'], queryFn: () => apiRequest(`${apiBase}/approvals`) });
    const mutation = useMutation({
        mutationFn: () => apiRequest(`${apiBase}/${review.type === 'course' ? 'courses' : 'projects'}/${review.item.id}/review`, { method: 'POST', body: { status: review.status, note } }),
        onSuccess: (result) => { queryClient.invalidateQueries({ queryKey: ['approvals'] }); queryClient.invalidateQueries({ queryKey: ['dashboard'] }); queryClient.invalidateQueries({ queryKey: ['notifications'] }); setReview(null); setNote(''); setFeedback(result.message); setError(''); },
        onError: (requestError) => setError(firstError(requestError)),
    });
    const openReview = (type, item, status) => { setReview({ type, item, status }); setNote(''); setError(''); };
    const data = query.data?.data;
    const pendingCount = (data?.pending?.courses?.length ?? 0) + (data?.pending?.projects?.length ?? 0);
    const historyCount = (data?.history?.courses?.length ?? 0) + (data?.history?.projects?.length ?? 0);
    const list = tab === 'pending' ? data?.pending : data?.history;

    return <>
        <PageHeader eyebrow="สำหรับ Admin ระดับอำเภอ" title="อนุมัติระดับอำเภอ" description="ตรวจหลักสูตร เอกสารแนบ และคำขอจัดตั้งกลุ่มจากตำบลในสังกัด" />
        <SuccessMessage message={feedback} /><ErrorMessage message={error || query.error?.message} />
        <section className="approval-summary"><div><strong>{pendingCount}</strong><span>รายการรอพิจารณา</span></div><div><strong>{historyCount}</strong><span>ประวัติการพิจารณา</span></div></section>
        <TabList selectedValue={tab} onTabSelect={(_, dataTab) => setTab(dataTab.value)}><Tab value="pending">รอพิจารณา ({pendingCount})</Tab><Tab value="history">ประวัติอนุมัติ ({historyCount})</Tab></TabList>
        <section className="approval-section"><div className="section-heading"><h2>หลักสูตร</h2><span>{list?.courses?.length ?? 0} รายการ</span></div><div className="approval-grid">{list?.courses?.map((item) => <RequestCard key={item.id} type="course" item={item} apiBase={apiBase} history={tab === 'history'} onReview={openReview} />)}{!list?.courses?.length ? <div className="state-panel empty-state"><strong>ไม่มีคำขอหลักสูตรในรายการนี้</strong></div> : null}</div></section>
        <section className="approval-section"><div className="section-heading"><h2>จัดตั้งกลุ่ม</h2><span>{list?.projects?.length ?? 0} รายการ</span></div><div className="approval-grid">{list?.projects?.map((item) => <RequestCard key={item.id} type="project" item={item} apiBase={apiBase} history={tab === 'history'} onReview={openReview} />)}{!list?.projects?.length ? <div className="state-panel empty-state"><strong>ไม่มีคำขอจัดตั้งกลุ่มในรายการนี้</strong></div> : null}</div></section>
        <Dialog open={review !== null} onOpenChange={(_, dataDialog) => !dataDialog.open && setReview(null)}><DialogSurface><form onSubmit={(event) => { event.preventDefault(); mutation.mutate(); }}><DialogBody><DialogTitle>{review?.status === 'approved' ? 'ยืนยันการอนุมัติ' : 'ส่งกลับให้แก้ไข'}</DialogTitle><DialogContent className="form-stack"><p>{review?.status === 'approved' ? `อนุมัติรายการ “${review?.item?.name || review?.item?.title}”` : 'ระบุรายละเอียดที่ต้องแก้ไขเพื่อให้ตำบลดำเนินการได้ถูกต้อง'}</p><ErrorMessage message={error} /><Field label="หมายเหตุ" required={review?.status === 'revision'}><Textarea rows={4} value={note} onChange={(_, dataNote) => setNote(dataNote.value)} /></Field></DialogContent><DialogActions><Button type="button" onClick={() => setReview(null)}>ยกเลิก</Button><Button type="submit" appearance="primary" disabled={mutation.isPending || (review?.status === 'revision' && !note.trim())}>{mutation.isPending ? 'กำลังบันทึก' : review?.status === 'approved' ? 'ยืนยันอนุมัติ' : 'ส่งกลับแก้ไข'}</Button></DialogActions></DialogBody></form></DialogSurface></Dialog>
    </>;
}
