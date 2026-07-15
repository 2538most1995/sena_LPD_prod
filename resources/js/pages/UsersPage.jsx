import React, { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Dialog, DialogActions, DialogBody, DialogContent, DialogSurface, DialogTitle, Field, Input } from '../ui';
import { AddRegular, DeleteRegular, EditRegular, SearchRegular } from '../ui/icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError, queryString, toFormData } from '../api';
import DataTable from '../components/DataTable';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const empty = { school_id: '', password: '', display_name: '', school_name: '', teacher_name: '', position: '', address_line: '', subdistrict: '', district: 'เสนา', province: 'พระนครศรีอยุธยา', postal_code: '13110', phone: '', latitude: '', longitude: '', role: 'subdistrict_admin', status: 'active', parent_id: '', photo: null };
const roleLabel = { super_admin: 'Super Admin', district_admin: 'Admin ระดับอำเภอ', subdistrict_admin: 'Admin ระดับตำบล' };

export default function UsersPage() {
    const { user, apiBase } = useOutletContext();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [dialog, setDialog] = useState(null);
    const [form, setForm] = useState(empty);
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const query = useQuery({ queryKey: ['users', search], queryFn: () => apiRequest(`${apiBase}/users${queryString({ search, per_page: 100 })}`) });
    const refs = useQuery({ queryKey: ['references'], queryFn: () => apiRequest(`${apiBase}/references`) });
    const save = useMutation({
        mutationFn: () => apiRequest(dialog?.id ? `${apiBase}/users/${dialog.id}` : `${apiBase}/users`, { method: 'POST', body: toFormData(form) }),
        onSuccess: (result) => { queryClient.invalidateQueries({ queryKey: ['users'] }); setDialog(null); setFeedback(result.message); setError(''); },
        onError: (requestError) => setError(firstError(requestError)),
    });
    const deactivate = useMutation({ mutationFn: (id) => apiRequest(`${apiBase}/users/${id}`, { method: 'DELETE' }), onSuccess: (result) => { queryClient.invalidateQueries({ queryKey: ['users'] }); setFeedback(result.message); }, onError: (requestError) => setError(firstError(requestError)) });
    const openCreate = () => { setForm({ ...empty, role: user.role === 'district_admin' ? 'subdistrict_admin' : 'district_admin', parent_id: user.role === 'district_admin' ? user.id : '' }); setDialog({}); setError(''); };
    const openEdit = (account) => { const next = { ...empty }; Object.keys(next).forEach((key) => { next[key] = key === 'password' ? '' : (account[key] ?? ''); }); setForm(next); setDialog(account); setError(''); };
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const columns = useMemo(() => [
        { header: 'สถานศึกษา', cell: ({ row }) => <div className="primary-cell"><strong>{row.original.school_name}</strong><span>{row.original.school_id}</span></div> },
        { header: 'ผู้รับผิดชอบ', cell: ({ row }) => <div className="primary-cell"><strong>{row.original.teacher_name}</strong><span>{row.original.position}</span></div> },
        { header: 'สิทธิ์', accessorKey: 'role', cell: ({ getValue }) => roleLabel[getValue()] },
        { header: 'สังกัด', cell: ({ row }) => row.original.parent?.school_name || 'ส่วนกลาง' },
        { header: 'สถานะ', accessorKey: 'status', cell: ({ getValue }) => <StatusBadge value={getValue()} /> },
        { header: '', id: 'actions', cell: ({ row }) => <div className="row-actions"><Button appearance="subtle" icon={<EditRegular />} aria-label="แก้ไขบัญชี" onClick={() => openEdit(row.original)} /><Button appearance="subtle" icon={<DeleteRegular />} aria-label="ระงับบัญชี" disabled={row.original.id === user.id || row.original.status === 'inactive'} onClick={() => window.confirm(`ยืนยันระงับบัญชี ${row.original.school_name}`) && deactivate.mutate(row.original.id)} /></div> },
    ], [user.id]);
    return <>
        <PageHeader eyebrow="บริหารสิทธิ์และสังกัด" title="ผู้ดูแลระบบ" description="จัดการ Super Admin, Admin ระดับอำเภอ และ Admin ระดับตำบลตามสายการอนุมัติ" actions={<Button appearance="primary" icon={<AddRegular />} onClick={openCreate}>เพิ่มบัญชี</Button>} />
        <SuccessMessage message={feedback} /><ErrorMessage message={error || query.error?.message} />
        <section className="content-card"><div className="filter-row"><Input contentBefore={<SearchRegular />} placeholder="ค้นหารหัส ชื่อสถานศึกษา หรือผู้รับผิดชอบ" value={search} onChange={(_, data) => setSearch(data.value)} /><span>{query.data?.total ?? 0} บัญชี</span></div><DataTable columns={columns} data={query.data?.data ?? []} loading={query.isLoading} /></section>
        <Dialog open={dialog !== null} onOpenChange={(_, data) => !data.open && setDialog(null)}><DialogSurface className="wide-dialog extra-wide"><form onSubmit={(event) => { event.preventDefault(); save.mutate(); }}><DialogBody><DialogTitle>{dialog?.id ? 'แก้ไขบัญชีผู้ดูแล' : 'เพิ่มบัญชีผู้ดูแล'}</DialogTitle><DialogContent className="form-stack scroll-form"><ErrorMessage message={error} />
            <div className="form-grid two"><Field label="รหัสสถานศึกษา" required><Input value={form.school_id} onChange={(_, data) => update('school_id', data.value)} /></Field><Field label={dialog?.id ? 'รหัสผ่านใหม่ (ไม่เปลี่ยนให้เว้นว่าง)' : 'รหัสผ่าน'} required={!dialog?.id}><Input type="password" value={form.password} onChange={(_, data) => update('password', data.value)} /></Field></div>
            <div className="form-grid two"><Field label="ชื่อที่แสดง" required><Input value={form.display_name} onChange={(_, data) => update('display_name', data.value)} /></Field><Field label="ชื่อสถานศึกษา" required><Input value={form.school_name} onChange={(_, data) => update('school_name', data.value)} /></Field></div>
            <div className="form-grid two"><Field label="ชื่อและนามสกุลผู้รับผิดชอบ" required><Input value={form.teacher_name} onChange={(_, data) => update('teacher_name', data.value)} /></Field><Field label="ตำแหน่ง"><Input value={form.position} onChange={(_, data) => update('position', data.value)} /></Field></div>
            <div className="form-grid three"><Field label="ระดับสิทธิ์" required><select className="native-select" value={form.role} disabled={user.role === 'district_admin'} onChange={(event) => update('role', event.target.value)}>{user.role === 'super_admin' ? <><option value="super_admin">Super Admin</option><option value="district_admin">Admin ระดับอำเภอ</option></> : null}<option value="subdistrict_admin">Admin ระดับตำบล</option></select></Field><Field label="สถานะ" required><select className="native-select" value={form.status} onChange={(event) => update('status', event.target.value)}><option value="active">เปิดใช้งาน</option><option value="inactive">ปิดใช้งาน</option></select></Field>{user.role === 'super_admin' && form.role === 'subdistrict_admin' ? <Field label="สังกัดอำเภอ" required><select className="native-select" value={form.parent_id} onChange={(event) => update('parent_id', event.target.value)}><option value="">เลือกอำเภอ</option>{refs.data?.data?.district_admins?.map((item) => <option key={item.id} value={item.id}>{item.school_name}</option>)}</select></Field> : <span />}</div>
            <Field label="ที่อยู่"><Input value={form.address_line} onChange={(_, data) => update('address_line', data.value)} /></Field>
            <div className="form-grid four"><Field label="ตำบล"><Input value={form.subdistrict} onChange={(_, data) => update('subdistrict', data.value)} /></Field><Field label="อำเภอ"><Input value={form.district} onChange={(_, data) => update('district', data.value)} /></Field><Field label="จังหวัด"><Input value={form.province} onChange={(_, data) => update('province', data.value)} /></Field><Field label="รหัสไปรษณีย์"><Input value={form.postal_code} onChange={(_, data) => update('postal_code', data.value)} /></Field></div>
            <div className="form-grid three"><Field label="โทรศัพท์"><Input value={form.phone} onChange={(_, data) => update('phone', data.value)} /></Field><Field label="ละติจูด"><Input value={String(form.latitude)} onChange={(_, data) => update('latitude', data.value)} /></Field><Field label="ลองจิจูด"><Input value={String(form.longitude)} onChange={(_, data) => update('longitude', data.value)} /></Field></div>
            <Field label="รูปภาพผู้รับผิดชอบ"><input className="native-file" type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => update('photo', event.target.files[0] ?? null)} /></Field>
        </DialogContent><DialogActions><Button type="button" onClick={() => setDialog(null)}>ยกเลิก</Button><Button type="submit" appearance="primary" disabled={save.isPending}>{save.isPending ? 'กำลังบันทึก' : 'บันทึกบัญชี'}</Button></DialogActions></DialogBody></form></DialogSurface></Dialog>
    </>;
}
