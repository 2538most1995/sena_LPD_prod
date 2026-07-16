import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Avatar, Button, Field, Input } from '../ui';
import {
    BuildingRegular, CameraRegular, CheckmarkCircleRegular, LocationRegular,
    LockClosedRegular, PersonRegular, PhoneRegular, SaveRegular,
} from '../ui/icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError, toFormData } from '../api';
import PageHeader from '../components/PageHeader';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const maxPhotoSize = 5 * 1024 * 1024;
const allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/webp'];

export default function ProfilePage({ onUserChange }) {
    const { user, apiBase } = useOutletContext();
    const photoInputRef = useRef(null);
    const [form, setForm] = useState({ ...user, password: '', photo: null });
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const update = (name, value) => setForm((current) => ({ ...current, [name]: value }));
    const photoPreview = useMemo(() => form.photo ? URL.createObjectURL(form.photo) : null, [form.photo]);

    useEffect(() => () => {
        if (photoPreview) URL.revokeObjectURL(photoPreview);
    }, [photoPreview]);

    const profilePhoto = photoPreview || user.photo_url || (user.photo_path ? `${apiBase}/profile/photo?v=${encodeURIComponent(user.updated_at || '')}` : null);
    const save = useMutation({
        mutationFn: () => apiRequest(`${apiBase}/profile`, { method: 'POST', body: toFormData(form) }),
        onMutate: () => { setFeedback(''); setError(''); },
        onSuccess: (result) => {
            const nextUser = { ...result.data, photo_url: result.data.photo_url || null };
            setForm({ ...nextUser, password: '', photo: null });
            if (photoInputRef.current) photoInputRef.current.value = '';
            setFeedback(result.message);
            setError('');
            onUserChange(nextUser);
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const selectPhoto = (event) => {
        const file = event.target.files?.[0] ?? null;
        if (!file) return;
        if (!allowedPhotoTypes.includes(file.type)) {
            setError('รองรับเฉพาะไฟล์ JPG, PNG และ WebP');
            event.target.value = '';
            return;
        }
        if (file.size > maxPhotoSize) {
            setError('รูปภาพต้องมีขนาดไม่เกิน 5 MB');
            event.target.value = '';
            return;
        }
        setError('');
        setFeedback('');
        update('photo', file);
    };

    return (
        <>
            <PageHeader eyebrow="บัญชีของฉัน" title="ข้อมูลส่วนตัวและสถานศึกษา" description="ข้อมูลที่บันทึกจะนำไปใช้ในหนังสือราชการ แบบฟอร์ม และรายงาน PDF" />
            <SuccessMessage message={feedback} />
            <ErrorMessage message={error} />
            <form className="profile-layout profile-page" onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
                <aside className="profile-sidebar">
                    <section className="profile-photo-card">
                        <div className="profile-avatar-wrap">
                            <Avatar name={form.teacher_name || form.display_name} image={profilePhoto ? { src: profilePhoto } : undefined} size={132} color="colorful" />
                            <span className="profile-photo-status"><CheckmarkCircleRegular /></span>
                        </div>
                        <div className="profile-identity">
                            <strong>{form.teacher_name || 'ยังไม่ได้ระบุชื่อ'}</strong>
                            <span>{form.position || 'ยังไม่ได้ระบุตำแหน่ง'}</span>
                        </div>
                        <label className="profile-photo-picker">
                            <CameraRegular />
                            <span>{form.photo ? 'เลือกภาพใหม่อีกครั้ง' : 'เปลี่ยนรูปโปรไฟล์'}</span>
                            <input ref={photoInputRef} type="file" accept="image/jpeg,image/png,image/webp" onChange={selectPhoto} />
                        </label>
                        {form.photo ? (
                            <div className="profile-selected-file">
                                <strong>พร้อมอัปโหลด</strong>
                                <span>{form.photo.name}</span>
                                <small>{(form.photo.size / 1024 / 1024).toLocaleString('th-TH', { maximumFractionDigits: 2 })} MB</small>
                            </div>
                        ) : <p className="profile-photo-hint">JPG, PNG หรือ WebP ขนาดไม่เกิน 5 MB</p>}
                    </section>
                    <div className="profile-security-note">
                        <LockClosedRegular />
                        <span><strong>ข้อมูลส่วนบุคคล</strong><small>ระบบจัดเก็บไฟล์แบบไม่เปิดเผยต่อสาธารณะ</small></span>
                    </div>
                </aside>

                <section className="content-card profile-form-card">
                    <div className="profile-form-section">
                        <div className="profile-section-heading"><span><PersonRegular /></span><div><h2>ข้อมูลผู้รับผิดชอบ</h2><p>ชื่อที่ใช้แสดงในระบบและเอกสารราชการ</p></div></div>
                        <div className="form-grid two">
                            <Field label="ชื่อที่แสดง" required><Input required value={form.display_name ?? ''} onChange={(_, data) => update('display_name', data.value)} /></Field>
                            <Field label="ชื่อและนามสกุล" required><Input required value={form.teacher_name ?? ''} onChange={(_, data) => update('teacher_name', data.value)} /></Field>
                        </div>
                        <Field label="ตำแหน่ง"><Input value={form.position ?? ''} onChange={(_, data) => update('position', data.value)} /></Field>
                    </div>

                    <div className="profile-form-section">
                        <div className="profile-section-heading"><span><BuildingRegular /></span><div><h2>ข้อมูลสถานศึกษา</h2><p>ชื่อหน่วยงานและที่อยู่สำหรับส่วนราชการเจ้าของหนังสือ</p></div></div>
                        <Field label="ชื่อสถานศึกษา" required><Input required value={form.school_name ?? ''} onChange={(_, data) => update('school_name', data.value)} /></Field>
                        <Field label="ที่อยู่"><Input value={form.address_line ?? ''} onChange={(_, data) => update('address_line', data.value)} /></Field>
                        <div className="form-grid four">
                            <Field label="ตำบล"><Input value={form.subdistrict ?? ''} onChange={(_, data) => update('subdistrict', data.value)} /></Field>
                            <Field label="อำเภอ"><Input value={form.district ?? ''} onChange={(_, data) => update('district', data.value)} /></Field>
                            <Field label="จังหวัด"><Input value={form.province ?? ''} onChange={(_, data) => update('province', data.value)} /></Field>
                            <Field label="รหัสไปรษณีย์"><Input inputMode="numeric" value={form.postal_code ?? ''} onChange={(_, data) => update('postal_code', data.value)} /></Field>
                        </div>
                    </div>

                    <div className="profile-form-section">
                        <div className="profile-section-heading"><span><LocationRegular /></span><div><h2>การติดต่อและพิกัด</h2><p>ใช้สำหรับข้อมูลอ้างอิงของสถานศึกษา</p></div></div>
                        <div className="form-grid three">
                            <Field label="โทรศัพท์"><Input contentBefore={<PhoneRegular />} inputMode="tel" value={form.phone ?? ''} onChange={(_, data) => update('phone', data.value)} /></Field>
                            <Field label="ละติจูด"><Input inputMode="decimal" value={form.latitude ?? ''} onChange={(_, data) => update('latitude', data.value)} /></Field>
                            <Field label="ลองจิจูด"><Input inputMode="decimal" value={form.longitude ?? ''} onChange={(_, data) => update('longitude', data.value)} /></Field>
                        </div>
                    </div>

                    <div className="profile-form-section profile-password-section">
                        <div className="profile-section-heading"><span><LockClosedRegular /></span><div><h2>ความปลอดภัย</h2><p>เปลี่ยนรหัสผ่านเฉพาะเมื่อต้องการ</p></div></div>
                        <Field label="รหัสผ่านใหม่" hint="เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน"><Input type="password" autoComplete="new-password" value={form.password} onChange={(_, data) => update('password', data.value)} /></Field>
                    </div>

                    <div className="profile-savebar">
                        <span>{form.photo ? 'มีรูปภาพใหม่รอบันทึกพร้อมข้อมูลส่วนตัว' : 'ตรวจสอบข้อมูลให้ถูกต้องก่อนบันทึก'}</span>
                        <Button type="submit" appearance="primary" icon={<SaveRegular />} disabled={save.isPending}>{save.isPending ? 'กำลังบันทึกข้อมูล' : 'บันทึกการเปลี่ยนแปลง'}</Button>
                    </div>
                </section>
            </form>
        </>
    );
}
