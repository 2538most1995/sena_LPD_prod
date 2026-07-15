import React, { useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Avatar, Button, Field, Input } from '@fluentui/react-components';
import { SaveRegular } from '@fluentui/react-icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError, toFormData } from '../api';
import PageHeader from '../components/PageHeader';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

export default function ProfilePage({ onUserChange }) {
    const { user, apiBase } = useOutletContext();
    const [form, setForm] = useState({ ...user, password: '', photo: null });
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const save = useMutation({ mutationFn: () => apiRequest(`${apiBase}/profile`, { method: 'POST', body: toFormData(form) }), onSuccess: (result) => { setFeedback(result.message); setError(''); onUserChange(result.data); }, onError: (requestError) => setError(firstError(requestError)) });
    return <>
        <PageHeader eyebrow="บัญชีของฉัน" title="ข้อมูลส่วนตัวและสถานศึกษา" description="ข้อมูลส่วนนี้นำไปใช้ในแบบฟอร์มราชการ หนังสืออนุมัติ และรายงาน PDF" />
        <SuccessMessage message={feedback} /><ErrorMessage message={error} />
        <form className="profile-layout" onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
            <aside className="profile-photo-card"><Avatar name={form.teacher_name || form.display_name} image={user.photo_path ? { src: `${apiBase}/profile/photo` } : undefined} size={128} color="colorful" /><strong>{form.teacher_name}</strong><span>{form.position}</span><label className="file-label">เปลี่ยนรูปภาพ<input type="file" accept="image/jpeg,image/png,image/webp" onChange={(event) => update('photo', event.target.files[0] ?? null)} /></label></aside>
            <section className="content-card form-stack"><div className="form-grid two"><Field label="ชื่อที่แสดง" required><Input value={form.display_name ?? ''} onChange={(_, data) => update('display_name', data.value)} /></Field><Field label="ชื่อสถานศึกษา" required><Input value={form.school_name ?? ''} onChange={(_, data) => update('school_name', data.value)} /></Field></div><div className="form-grid two"><Field label="ชื่อและนามสกุล" required><Input value={form.teacher_name ?? ''} onChange={(_, data) => update('teacher_name', data.value)} /></Field><Field label="ตำแหน่ง"><Input value={form.position ?? ''} onChange={(_, data) => update('position', data.value)} /></Field></div><Field label="ที่อยู่"><Input value={form.address_line ?? ''} onChange={(_, data) => update('address_line', data.value)} /></Field><div className="form-grid four"><Field label="ตำบล"><Input value={form.subdistrict ?? ''} onChange={(_, data) => update('subdistrict', data.value)} /></Field><Field label="อำเภอ"><Input value={form.district ?? ''} onChange={(_, data) => update('district', data.value)} /></Field><Field label="จังหวัด"><Input value={form.province ?? ''} onChange={(_, data) => update('province', data.value)} /></Field><Field label="รหัสไปรษณีย์"><Input value={form.postal_code ?? ''} onChange={(_, data) => update('postal_code', data.value)} /></Field></div><div className="form-grid three"><Field label="โทรศัพท์"><Input value={form.phone ?? ''} onChange={(_, data) => update('phone', data.value)} /></Field><Field label="ละติจูด"><Input value={form.latitude ?? ''} onChange={(_, data) => update('latitude', data.value)} /></Field><Field label="ลองจิจูด"><Input value={form.longitude ?? ''} onChange={(_, data) => update('longitude', data.value)} /></Field></div><Field label="รหัสผ่านใหม่" hint="เว้นว่างหากไม่ต้องการเปลี่ยน"><Input type="password" value={form.password} onChange={(_, data) => update('password', data.value)} /></Field><div className="form-submit"><Button type="submit" appearance="primary" icon={<SaveRegular />} disabled={save.isPending}>{save.isPending ? 'กำลังบันทึก' : 'บันทึกข้อมูล'}</Button></div></section>
        </form>
    </>;
}
