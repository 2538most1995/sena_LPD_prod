import React, { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Dialog, DialogActions, DialogBody, DialogContent, DialogSurface, DialogTitle, Field, Input, Textarea } from '../ui';
import { AddRegular, DeleteRegular, EditRegular, EyeRegular, SearchRegular } from '../ui/icons';
import { useNavigate, useOutletContext } from 'react-router-dom';
import { apiRequest, firstError, queryString } from '../api';
import DataTable from '../components/DataTable';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const currentThaiYear = new Date().getFullYear() + 543;
const emptyForm = {
    course_id: '', lecturer_id: '', title: '', objective: '', format_type: 'หลักสูตร 3-9 ชั่วโมง',
    attribute_type: '', activity_type: '', place: '', address: '', latitude: '', longitude: '',
    start_date: '', end_date: '', start_time: '09:00', end_time: '16:00', fiscal_year: currentThaiYear,
    status: 'ฉบับร่าง', lecturer_cost: 0, material_cost: 0, board_cost: 0, food_cost: 0,
    snack_cost: 0, place_cost: 0, transport_cost: 0, other_cost: 0,
};
const costs = [
    ['lecturer_cost', 'ค่าวิทยากร'], ['material_cost', 'ค่าวัสดุ'], ['board_cost', 'ค่าป้าย'], ['food_cost', 'ค่าอาหารกลางวัน'],
    ['snack_cost', 'ค่าอาหารว่าง'], ['place_cost', 'ค่าสถานที่'], ['transport_cost', 'ค่าพาหนะ'], ['other_cost', 'ค่าใช้จ่ายอื่น'],
];
const thaiDate = (value) => value ? new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium' }).format(new Date(value)) : 'ไม่ระบุ';

export default function ProjectsPage() {
    const { user, apiBase } = useOutletContext();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [dialog, setDialog] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [error, setError] = useState('');
    const [feedback, setFeedback] = useState('');
    const projects = useQuery({ queryKey: ['projects', search], queryFn: () => apiRequest(`${apiBase}/projects${queryString({ search, per_page: 100 })}`) });
    const refs = useQuery({ queryKey: ['references'], queryFn: () => apiRequest(`${apiBase}/references`) });

    const save = useMutation({
        mutationFn: () => apiRequest(dialog?.id ? `${apiBase}/projects/${dialog.id}` : `${apiBase}/projects`, { method: dialog?.id ? 'PUT' : 'POST', body: form }),
        onSuccess: (result) => {
            queryClient.invalidateQueries({ queryKey: ['projects'] });
            queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setDialog(null);
            setFeedback(result.message);
            setError('');
        },
        onError: (requestError) => setError(firstError(requestError)),
    });
    const remove = useMutation({
        mutationFn: (id) => apiRequest(`${apiBase}/projects/${id}`, { method: 'DELETE' }),
        onSuccess: (result) => { queryClient.invalidateQueries({ queryKey: ['projects'] }); setFeedback(result.message); },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const openCreate = () => { setForm({ ...emptyForm }); setDialog({}); setError(''); };
    const openEdit = (project) => {
        const next = { ...emptyForm };
        Object.keys(next).forEach((key) => { next[key] = project[key] ?? next[key]; });
        next.start_date = project.start_date?.slice(0, 10) ?? '';
        next.end_date = project.end_date?.slice(0, 10) ?? '';
        next.start_time = project.start_time?.slice(0, 5) ?? '09:00';
        next.end_time = project.end_time?.slice(0, 5) ?? '16:00';
        setForm(next); setDialog(project); setError('');
    };
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const selectedCourse = refs.data?.data?.courses?.find((item) => String(item.id) === String(form.course_id));

    const columns = useMemo(() => [
        { header: 'กลุ่มกิจกรรม', accessorKey: 'title', cell: ({ row }) => <div className="primary-cell"><strong>{row.original.title}</strong><span>{row.original.course?.name} · {row.original.format_type}</span></div> },
        { header: 'ช่วงดำเนินการ', cell: ({ row }) => <div className="primary-cell"><strong>{thaiDate(row.original.start_date)}</strong><span>ถึง {thaiDate(row.original.end_date)}</span></div> },
        { header: 'ผู้เรียน', accessorKey: 'students_count', cell: ({ getValue }) => `${getValue() ?? 0} คน` },
        { header: 'งบประมาณ', accessorKey: 'total_budget', cell: ({ getValue }) => `${Number(getValue() ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท` },
        { header: 'สถานะ', cell: ({ row }) => <div className="badge-stack"><StatusBadge value={row.original.approval_status} /><StatusBadge value={row.original.status} /></div> },
        { header: '', id: 'actions', cell: ({ row }) => <div className="row-actions">
            <Button appearance="subtle" icon={<EyeRegular />} aria-label="เปิดข้อมูลกลุ่ม" onClick={() => navigate(`/projects/${row.original.id}`)} />
            {(user.role === 'super_admin' || Number(row.original.created_by) === Number(user.id)) ? <><Button appearance="subtle" icon={<EditRegular />} aria-label="แก้ไขกลุ่ม" onClick={() => openEdit(row.original)} /><Button appearance="subtle" icon={<DeleteRegular />} aria-label="ลบกลุ่ม" disabled={remove.isPending} onClick={() => window.confirm(`ยืนยันลบ ${row.original.title}`) && remove.mutate(row.original.id)} /></> : null}
        </div> },
    ], [remove.isPending, navigate, user.id, user.role]);

    return (
        <>
            <PageHeader eyebrow="การเรียนรู้เพื่อพัฒนาตนเอง" title="จัดตั้งกลุ่ม" description="วางแผนกิจกรรม กำหนดวิทยากร งบประมาณ ผู้เรียน และส่งให้อำเภออนุมัติ" actions={<Button appearance="primary" icon={<AddRegular />} onClick={openCreate}>จัดตั้งกลุ่มใหม่</Button>} />
            <SuccessMessage message={feedback} />
            <ErrorMessage message={error || projects.error?.message} />
            <section className="content-card">
                <div className="filter-row"><Input contentBefore={<SearchRegular />} placeholder="ค้นหาชื่อกลุ่ม หลักสูตร หรือสถานที่" value={search} onChange={(_, data) => setSearch(data.value)} /><span>{projects.data?.total ?? 0} รายการ</span></div>
                <DataTable columns={columns} data={projects.data?.data ?? []} loading={projects.isLoading} emptyTitle="ยังไม่มีการจัดตั้งกลุ่ม" emptyText="เลือกหลักสูตรที่อนุมัติแล้วเพื่อเริ่มจัดตั้งกลุ่ม" />
            </section>

            <Dialog open={dialog !== null} onOpenChange={(_, data) => !data.open && setDialog(null)}>
                <DialogSurface className="wide-dialog extra-wide">
                    <form onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
                        <DialogBody><DialogTitle>{dialog?.id ? 'แก้ไขการจัดตั้งกลุ่ม' : 'จัดตั้งกลุ่มใหม่'}</DialogTitle>
                            <DialogContent className="form-stack scroll-form">
                                <ErrorMessage message={error} />
                                <div className="form-grid two">
                                    <Field label="หลักสูตร" required><select className="native-select" value={form.course_id} onChange={(event) => update('course_id', event.target.value)}><option value="">เลือกหลักสูตร</option>{refs.data?.data?.courses?.map((course) => <option key={course.id} value={course.id}>{course.name} ({course.hours} ชม.)</option>)}</select></Field>
                                    <Field label="รูปแบบการจัด"><Input value={selectedCourse ? (selectedCourse.hours >= 10 ? 'หลักสูตร 10 ชั่วโมงขึ้นไป' : 'หลักสูตร 3-9 ชั่วโมง') : form.format_type} readOnly /></Field>
                                </div>
                                <Field label="ชื่อโครงการหรือกลุ่มกิจกรรม" required><Input value={form.title} onChange={(_, data) => update('title', data.value)} /></Field>
                                <Field label="วัตถุประสงค์"><Textarea rows={3} value={form.objective} onChange={(_, data) => update('objective', data.value)} /></Field>
                                <div className="form-grid two">
                                    <Field label="วิทยากร" required><select className="native-select" value={form.lecturer_id} onChange={(event) => update('lecturer_id', event.target.value)}><option value="">เลือกวิทยากร</option>{refs.data?.data?.lecturers?.map((person) => <option key={person.id} value={person.id}>{person.prefix}{person.first_name} {person.last_name}</option>)}</select></Field>
                                    <Field label="สถานะดำเนินงาน" required><select className="native-select" value={form.status} onChange={(event) => update('status', event.target.value)}>{['ฉบับร่าง', 'รออนุมัติ', 'กำลังดำเนินการ', 'เสร็จสิ้น'].map((value) => <option key={value}>{value}</option>)}</select></Field>
                                </div>
                                <div className="form-grid two"><Field label="ประเภทคุณลักษณะ"><Input value={form.attribute_type} onChange={(_, data) => update('attribute_type', data.value)} /></Field><Field label="ประเภทกิจกรรม"><Input value={form.activity_type} onChange={(_, data) => update('activity_type', data.value)} /></Field></div>
                                <Field label="สถานที่จัด" required><Input value={form.place} onChange={(_, data) => update('place', data.value)} /></Field>
                                <Field label="ที่อยู่สถานที่"><Textarea rows={2} value={form.address} onChange={(_, data) => update('address', data.value)} /></Field>
                                <div className="form-grid four"><Field label="วันเริ่ม" required><Input type="date" value={form.start_date} onChange={(_, data) => update('start_date', data.value)} /></Field><Field label="วันสิ้นสุด" required><Input type="date" value={form.end_date} onChange={(_, data) => update('end_date', data.value)} /></Field><Field label="เวลาเริ่ม"><Input type="time" value={form.start_time} onChange={(_, data) => update('start_time', data.value)} /></Field><Field label="เวลาสิ้นสุด"><Input type="time" value={form.end_time} onChange={(_, data) => update('end_time', data.value)} /></Field></div>
                                <div className="form-grid two"><Field label="ปีงบประมาณ" required><Input type="number" value={String(form.fiscal_year)} onChange={(_, data) => update('fiscal_year', data.value)} /></Field><Field label="พิกัด"><div className="inline-fields"><Input placeholder="ละติจูด" value={String(form.latitude)} onChange={(_, data) => update('latitude', data.value)} /><Input placeholder="ลองจิจูด" value={String(form.longitude)} onChange={(_, data) => update('longitude', data.value)} /></div></Field></div>
                                <fieldset className="cost-box"><legend>รายละเอียดงบประมาณ</legend><div className="form-grid four">{costs.map(([key, label]) => <Field key={key} label={label}><Input type="number" min="0" step="0.01" value={String(form[key])} onChange={(_, data) => update(key, data.value)} /></Field>)}</div></fieldset>
                            </DialogContent>
                            <DialogActions><Button type="button" onClick={() => setDialog(null)}>ยกเลิก</Button><Button appearance="primary" type="submit" disabled={save.isPending}>{save.isPending ? 'กำลังบันทึก' : user.role === 'subdistrict_admin' ? 'บันทึกและส่งอนุมัติ' : 'บันทึกกลุ่ม'}</Button></DialogActions>
                        </DialogBody>
                    </form>
                </DialogSurface>
            </Dialog>
        </>
    );
}
