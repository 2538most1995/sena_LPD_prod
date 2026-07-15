import React, { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
    Button, Dialog, DialogActions, DialogBody, DialogContent, DialogSurface, DialogTitle,
    Field, Input, Textarea,
} from '@fluentui/react-components';
import {
    AddRegular, DeleteRegular, DocumentPdfRegular, DocumentWordRegular, EditRegular, SearchRegular,
} from '@fluentui/react-icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError, queryString, toFormData } from '../api';
import DataTable from '../components/DataTable';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const emptyForm = { name: '', category: '', hours: '', owner: '', description: '', word_attachment: null, pdf_attachment: null };

export default function CoursesPage() {
    const { user, apiBase } = useOutletContext();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState('');
    const [dialog, setDialog] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');

    const courses = useQuery({
        queryKey: ['courses', search],
        queryFn: () => apiRequest(`${apiBase}/courses${queryString({ search, per_page: 100 })}`),
    });

    const save = useMutation({
        mutationFn: async () => {
            const payload = { ...form, owner: form.owner || user.school_name };
            const url = dialog?.id ? `${apiBase}/courses/${dialog.id}` : `${apiBase}/courses`;
            return apiRequest(url, { method: 'POST', body: toFormData(payload) });
        },
        onSuccess: (result) => {
            queryClient.invalidateQueries({ queryKey: ['courses'] });
            queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setDialog(null);
            setFeedback(result.message);
            setError('');
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const remove = useMutation({
        mutationFn: (id) => apiRequest(`${apiBase}/courses/${id}`, { method: 'DELETE' }),
        onSuccess: (result) => {
            queryClient.invalidateQueries({ queryKey: ['courses'] });
            setFeedback(result.message);
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const openCreate = () => {
        setForm({ ...emptyForm, owner: user.school_name || '' });
        setError('');
        setDialog({});
    };
    const openEdit = (course) => {
        setForm({
            name: course.name,
            category: course.category,
            hours: course.hours,
            owner: course.owner,
            description: course.description,
            word_attachment: null,
            pdf_attachment: null,
        });
        setError('');
        setDialog(course);
    };
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));

    const columns = useMemo(() => [
        { header: 'หลักสูตร', accessorKey: 'name', cell: ({ row }) => <div className="primary-cell"><strong>{row.original.name}</strong><span>{row.original.category} · {row.original.hours} ชั่วโมง</span></div> },
        { header: 'หน่วยงาน', accessorKey: 'owner' },
        { header: 'ไฟล์แนบ', cell: ({ row }) => <div className="attachment-list">{row.original.attachments?.map((file) => <a key={file.type} href={file.url} target="_blank" rel="noreferrer">{file.type === 'pdf' ? <DocumentPdfRegular /> : <DocumentWordRegular />}{file.type.toUpperCase()}</a>)}</div> },
        { header: 'สถานะ', accessorKey: 'approval_status', cell: ({ getValue }) => <StatusBadge value={getValue()} /> },
        { header: '', id: 'actions', cell: ({ row }) => (
            <div className="row-actions">
                {(user.role === 'super_admin' || Number(row.original.created_by) === Number(user.id)) ? <>
                    <Button appearance="subtle" icon={<EditRegular />} aria-label="แก้ไขหลักสูตร" onClick={() => openEdit(row.original)} />
                    <Button appearance="subtle" icon={<DeleteRegular />} aria-label="ลบหลักสูตร" disabled={row.original.projects_count > 0 || remove.isPending} onClick={() => window.confirm(`ยืนยันลบหลักสูตร ${row.original.name}`) && remove.mutate(row.original.id)} />
                </> : <span className="muted-text">รอเจ้าของรายการแก้ไข</span>}
            </div>
        ) },
    ], [remove.isPending, user.id, user.role]);

    return (
        <>
            <PageHeader
                eyebrow="คลังหลักสูตร"
                title="หลักสูตร"
                description="สร้าง แก้ไข แนบไฟล์ Word และ PDF พร้อมส่งให้อำเภอพิจารณา"
                actions={<Button appearance="primary" icon={<AddRegular />} onClick={openCreate}>เพิ่มหลักสูตร</Button>}
            />
            <SuccessMessage message={feedback} />
            <ErrorMessage message={error || courses.error?.message} />
            <section className="content-card">
                <div className="filter-row">
                    <Input contentBefore={<SearchRegular />} placeholder="ค้นหาชื่อหลักสูตร กลุ่ม หรือหน่วยงาน" value={search} onChange={(_, data) => setSearch(data.value)} />
                    <span>{courses.data?.total ?? 0} รายการ</span>
                </div>
                <DataTable columns={columns} data={courses.data?.data ?? []} loading={courses.isLoading} emptyTitle="ยังไม่มีหลักสูตร" emptyText="กดเพิ่มหลักสูตรเพื่อจัดทำข้อมูลและแนบเอกสาร" />
            </section>

            <Dialog open={dialog !== null} onOpenChange={(_, data) => !data.open && setDialog(null)}>
                <DialogSurface className="wide-dialog">
                    <form onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
                        <DialogBody>
                            <DialogTitle>{dialog?.id ? 'แก้ไขหลักสูตร' : 'เพิ่มหลักสูตร'}</DialogTitle>
                            <DialogContent className="form-stack">
                                <ErrorMessage message={error} />
                                <Field label="ชื่อหลักสูตร" required><Input value={form.name} onChange={(_, data) => update('name', data.value)} /></Field>
                                <div className="form-grid two">
                                    <Field label="กลุ่มหลักสูตร" required><Input value={form.category} onChange={(_, data) => update('category', data.value)} placeholder="เช่น อาชีพเฉพาะทาง" /></Field>
                                    <Field label="จำนวนชั่วโมง" required><Input type="number" min="1" value={String(form.hours)} onChange={(_, data) => update('hours', data.value)} /></Field>
                                </div>
                                <Field label="หน่วยงานเจ้าของ" required><Input value={form.owner} readOnly={user.role === 'subdistrict_admin'} onChange={(_, data) => update('owner', data.value)} /></Field>
                                <Field label="รายละเอียดและเนื้อหาหลักสูตร" required><Textarea rows={5} value={form.description} onChange={(_, data) => update('description', data.value)} /></Field>
                                <div className="file-pair">
                                    <Field label="ไฟล์ Word (.doc, .docx)" hint={dialog?.word_attachment_name ? `ไฟล์เดิม: ${dialog.word_attachment_name}` : 'ขนาดไม่เกิน 10 MB'}>
                                        <input className="native-file" type="file" accept=".doc,.docx" onChange={(event) => update('word_attachment', event.target.files[0] ?? null)} />
                                    </Field>
                                    <Field label="ไฟล์ PDF (.pdf)" hint={dialog?.pdf_attachment_name ? `ไฟล์เดิม: ${dialog.pdf_attachment_name}` : 'ขนาดไม่เกิน 10 MB'}>
                                        <input className="native-file" type="file" accept="application/pdf" onChange={(event) => update('pdf_attachment', event.target.files[0] ?? null)} />
                                    </Field>
                                </div>
                                <p className="form-note">สามารถเลือกไฟล์ Word และ PDF แล้วบันทึกพร้อมกัน ระบบจะเก็บเป็นเอกสารประกอบหลักสูตรทั้งสองฉบับ</p>
                            </DialogContent>
                            <DialogActions>
                                <Button type="button" appearance="secondary" onClick={() => setDialog(null)}>ยกเลิก</Button>
                                <Button type="submit" appearance="primary" disabled={save.isPending}>{save.isPending ? 'กำลังบันทึก' : user.role === 'subdistrict_admin' ? 'บันทึกและส่งอนุมัติ' : 'บันทึกหลักสูตร'}</Button>
                            </DialogActions>
                        </DialogBody>
                    </form>
                </DialogSurface>
            </Dialog>
        </>
    );
}
