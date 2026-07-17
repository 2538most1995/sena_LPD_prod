import React, { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Dialog, DialogActions, DialogBody, DialogContent, DialogSurface, DialogTitle, Field, Input, Textarea } from '../ui';
import { AddRegular, DeleteRegular, DownloadRegular, EditRegular, SearchRegular, UploadRegular } from '../ui/icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError, queryString } from '../api';
import DataTable from '../components/DataTable';
import PageHeader from '../components/PageHeader';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const studentEmpty = { prefix: 'นาย', first_name: '', last_name: '', gender: 'ชาย', id_card: '', birthday: '', education: '', career: '', target_group: '', annual_income: '', address: '', phone: '', registered_at: '' };
const lecturerEmpty = { prefix: 'นาย', first_name: '', last_name: '', id_card: '', birthday: '', education: '', career: '', address: '', phone: '', registered_at: '', expertise: '' };

export default function PeoplePage({ type }) {
    const isStudent = type === 'students';
    const { user, apiBase } = useOutletContext();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [dialog, setDialog] = useState(null);
    const [form, setForm] = useState(isStudent ? studentEmpty : lecturerEmpty);
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const query = useQuery({ queryKey: [type, search], queryFn: () => apiRequest(`${apiBase}/${type}${queryString({ search, per_page: 100 })}`) });
    const save = useMutation({
        mutationFn: () => apiRequest(dialog?.id ? `${apiBase}/${type}/${dialog.id}` : `${apiBase}/${type}`, { method: dialog?.id ? 'PUT' : 'POST', body: form }),
        onSuccess: (result) => { queryClient.invalidateQueries({ queryKey: [type] }); queryClient.invalidateQueries({ queryKey: ['references'] }); setDialog(null); setFeedback(result.message); setError(''); },
        onError: (requestError) => setError(firstError(requestError)),
    });
    const remove = useMutation({
        mutationFn: (id) => apiRequest(`${apiBase}/${type}/${id}`, { method: 'DELETE' }),
        onSuccess: (result) => { queryClient.invalidateQueries({ queryKey: [type] }); setFeedback(result.message); },
        onError: (requestError) => setError(firstError(requestError)),
    });
    const importStudents = useMutation({
        mutationFn: () => {
            const body = new FormData();
            body.append('file', importFile);
            return apiRequest(`${apiBase}/students/import`, { method: 'POST', body });
        },
        onSuccess: (result) => {
            queryClient.invalidateQueries({ queryKey: ['students'] });
            queryClient.invalidateQueries({ queryKey: ['references'] });
            queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setImportOpen(false);
            setImportFile(null);
            setFeedback(result.message);
            setError('');
        },
        onError: (requestError) => setError(firstError(requestError)),
    });
    const openCreate = () => { setForm({ ...(isStudent ? studentEmpty : lecturerEmpty) }); setDialog({}); setError(''); };
    const openEdit = (person) => {
        const next = { ...(isStudent ? studentEmpty : lecturerEmpty) };
        Object.keys(next).forEach((key) => { next[key] = person[key] ?? ''; });
        next.birthday = person.birthday?.slice(0, 10) ?? '';
        next.registered_at = person.registered_at?.slice(0, 10) ?? '';
        setForm(next); setDialog(person); setError('');
    };
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const canManage = (person) => user.role === 'super_admin' || Number(person.created_by) === Number(user.id);
    const columns = useMemo(() => [
        { header: 'ชื่อและนามสกุล', cell: ({ row }) => <div className="primary-cell"><strong>{row.original.prefix}{row.original.first_name} {row.original.last_name}</strong><span>เลขบัตร {row.original.id_card}</span></div> },
        ...(user.role !== 'subdistrict_admin' ? [{ header: 'หน่วยงาน', cell: ({ row }) => row.original.creator?.school_name || 'ข้อมูลเดิมไม่ระบุหน่วยงาน' }] : []),
        { header: isStudent ? 'กลุ่มเป้าหมาย' : 'ความเชี่ยวชาญ', accessorKey: isStudent ? 'target_group' : 'expertise' },
        { header: 'อาชีพ', accessorKey: 'career' },
        { header: 'โทรศัพท์', accessorKey: 'phone' },
        { header: '', id: 'actions', cell: ({ row }) => <div className="row-actions">{canManage(row.original) ? <><Button appearance="subtle" icon={<EditRegular />} aria-label="แก้ไข" onClick={() => openEdit(row.original)} /><Button appearance="subtle" icon={<DeleteRegular />} aria-label="ลบ" onClick={() => window.confirm(`ยืนยันลบ ${row.original.first_name} ${row.original.last_name}`) && remove.mutate(row.original.id)} /></> : <span className="muted-text">ดูข้อมูลเท่านั้น</span>}</div> },
    ], [isStudent, user.id, user.role]);

    return <>
        <PageHeader eyebrow={isStudent ? 'ฐานข้อมูลผู้รับบริการ' : 'ทะเบียนบุคลากร'} title={isStudent ? 'ผู้เรียน' : 'วิทยากร'} description={isStudent ? (user.role === 'subdistrict_admin' ? 'ข้อมูลผู้เรียนของตำบลนี้ สำหรับลงทะเบียนเข้ากลุ่มกิจกรรมของหน่วยงาน' : 'ภาพรวมข้อมูลผู้เรียน แยกตามตำบลและหน่วยงานเจ้าของข้อมูล') : (user.role === 'subdistrict_admin' ? 'ข้อมูลวิทยากรของตำบลนี้ สำหรับเลือกใช้ในกลุ่มกิจกรรมของหน่วยงาน' : 'ภาพรวมข้อมูลวิทยากร แยกตามตำบลและหน่วยงานเจ้าของข้อมูล')} actions={<>{isStudent ? <><Button component="a" href={`${apiBase}/students/import-template`} appearance="secondary" icon={<DownloadRegular />}>ดาวน์โหลดเทมเพลต</Button><Button appearance="secondary" icon={<UploadRegular />} onClick={() => { setImportOpen(true); setImportFile(null); setError(''); }}>นำเข้า Excel</Button></> : null}<Button appearance="primary" icon={<AddRegular />} onClick={openCreate}>เพิ่ม{isStudent ? 'ผู้เรียน' : 'วิทยากร'}</Button></>} />
        <SuccessMessage message={feedback} /><ErrorMessage message={(!dialog && !importOpen ? error : '') || query.error?.message} />
        <section className="content-card"><div className="filter-row"><Input contentBefore={<SearchRegular />} placeholder={`ค้นหา${isStudent ? 'ผู้เรียน' : 'วิทยากร'} ชื่อ เลขบัตร หรือโทรศัพท์`} value={search} onChange={(_, data) => setSearch(data.value)} /><span>{query.data?.total ?? 0} รายการ</span></div><DataTable columns={columns} data={query.data?.data ?? []} loading={query.isLoading} emptyTitle={`ยังไม่มี${isStudent ? 'ผู้เรียน' : 'วิทยากร'}`} /></section>
        <Dialog open={dialog !== null} onOpenChange={(_, data) => { if (!data.open) { setDialog(null); setError(''); } }}><DialogSurface className="wide-dialog"><form onSubmit={(event) => { event.preventDefault(); save.mutate(); }}><DialogBody><DialogTitle>{dialog?.id ? 'แก้ไข' : 'เพิ่ม'}{isStudent ? 'ผู้เรียน' : 'วิทยากร'}</DialogTitle><DialogContent className="form-stack scroll-form"><ErrorMessage message={error} />
            <div className="form-grid three"><Field label="คำนำหน้า" required><select className="native-select" value={form.prefix} onChange={(event) => update('prefix', event.target.value)}>{['นาย', 'นาง', 'นางสาว'].map((value) => <option key={value}>{value}</option>)}</select></Field><Field label="ชื่อ" required><Input value={form.first_name} onChange={(_, data) => update('first_name', data.value)} /></Field><Field label="นามสกุล" required><Input value={form.last_name} onChange={(_, data) => update('last_name', data.value)} /></Field></div>
            <div className="form-grid two"><Field label="เลขประจำตัวประชาชน" required><Input value={form.id_card} onChange={(_, data) => update('id_card', data.value)} maxLength={20} /></Field><Field label="วันเกิด"><Input type="date" value={form.birthday} onChange={(_, data) => update('birthday', data.value)} /></Field></div>
            {isStudent ? <div className="form-grid two"><Field label="เพศ" required><select className="native-select" value={form.gender} onChange={(event) => update('gender', event.target.value)}>{['ชาย', 'หญิง', 'ไม่ระบุ'].map((value) => <option key={value}>{value}</option>)}</select></Field><Field label="กลุ่มเป้าหมาย"><Input value={form.target_group} onChange={(_, data) => update('target_group', data.value)} /></Field></div> : <Field label="ความเชี่ยวชาญ" required><Input value={form.expertise} onChange={(_, data) => update('expertise', data.value)} /></Field>}
            <div className="form-grid two"><Field label="การศึกษา"><Input value={form.education} onChange={(_, data) => update('education', data.value)} /></Field><Field label="อาชีพ"><Input value={form.career} onChange={(_, data) => update('career', data.value)} /></Field></div>
            <div className="form-grid two"><Field label="โทรศัพท์"><Input value={form.phone} onChange={(_, data) => update('phone', data.value)} /></Field><Field label="วันที่ขึ้นทะเบียน"><Input type="date" value={form.registered_at} onChange={(_, data) => update('registered_at', data.value)} /></Field></div>
            {isStudent ? <Field label="รายได้ต่อปี"><Input type="number" min="0" value={String(form.annual_income)} onChange={(_, data) => update('annual_income', data.value)} /></Field> : null}
            <Field label="ที่อยู่"><Textarea rows={3} value={form.address} onChange={(_, data) => update('address', data.value)} /></Field>
        </DialogContent><DialogActions><Button type="button" onClick={() => { setDialog(null); setError(''); }}>ยกเลิก</Button><Button type="submit" appearance="primary" disabled={save.isPending}>{save.isPending ? 'กำลังบันทึก' : 'บันทึกข้อมูล'}</Button></DialogActions></DialogBody></form></DialogSurface></Dialog>
        <Dialog open={isStudent && importOpen} onOpenChange={(_, data) => !data.open && setImportOpen(false)}><DialogSurface className="wide-dialog"><form onSubmit={(event) => { event.preventDefault(); importStudents.mutate(); }}><DialogBody><DialogTitle>นำเข้าผู้เรียนจาก Excel</DialogTitle><DialogContent className="form-stack"><div className="import-dialog-intro"><strong>ใช้งานง่ายใน 3 ขั้นตอน</strong><ol><li>ดาวน์โหลดเทมเพลต แล้วอ่านชีต <b>“เริ่มที่นี่”</b></li><li>กรอกข้อมูลจริงในชีต <b>“นำเข้าผู้เรียน”</b> ตั้งแต่แถวที่ 2</li><li>บันทึกไฟล์ แล้วเลือกไฟล์ด้านล่างเพื่อนำเข้า</li></ol><span>มีตัวอย่างที่ถูกต้องในชีต “ตัวอย่างข้อมูล” ซึ่งระบบจะไม่นำเข้ามา</span></div><ErrorMessage message={error} /><Button component="a" href={`${apiBase}/students/import-template`} appearance="secondary" icon={<DownloadRegular />}>ดาวน์โหลดเทมเพลต Excel</Button><Field label="เลือกไฟล์ที่กรอกแล้ว" required hint="รองรับ .xlsx หรือ .xls สูงสุด 1,000 คน และไม่เกิน 5 MB"><label className={`import-file-picker${importFile ? ' has-file' : ''}`}><input type="file" accept=".xlsx,.xls" required onChange={(event) => setImportFile(event.target.files[0] ?? null)} /><UploadRegular /><span><strong>{importFile ? importFile.name : 'คลิกเพื่อเลือกไฟล์ Excel'}</strong><small>{importFile ? 'พร้อมนำเข้าข้อมูล' : 'รองรับไฟล์ .xlsx และ .xls'}</small></span></label></Field></DialogContent><DialogActions><Button type="button" onClick={() => setImportOpen(false)}>ยกเลิก</Button><Button type="submit" appearance="primary" icon={<UploadRegular />} disabled={!importFile || importStudents.isPending}>{importStudents.isPending ? 'กำลังตรวจสอบและนำเข้า' : 'ตรวจสอบและนำเข้า'}</Button></DialogActions></DialogBody></form></DialogSurface></Dialog>
    </>;
}
