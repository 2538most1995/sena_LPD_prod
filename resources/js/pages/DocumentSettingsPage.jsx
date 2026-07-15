import React, { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Button, Field, Input, Spinner, Textarea } from '../ui';
import { CheckmarkCircleRegular, SaveRegular, SettingsRegular } from '../ui/icons';
import { useOutletContext } from 'react-router-dom';
import { apiRequest, firstError } from '../api';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';
import PageHeader from '../components/PageHeader';

const requiredFields = [
    'learning_center_name', 'district_office_name', 'district_office_short_name',
    'province_office_name', 'document_no_prefix', 'office_address', 'phone', 'owner_unit',
    'registrar_name', 'responsible_name', 'director_name',
];

function TextField({ label, value, onChange, required = false, hint = '' }) {
    return <Field label={label} required={required} hint={hint}><Input value={value ?? ''} onChange={(_, data) => onChange(data.value)} /></Field>;
}

export default function DocumentSettingsPage() {
    const { apiBase } = useOutletContext();
    const client = useQueryClient();
    const [form, setForm] = useState({});
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');
    const settings = useQuery({
        queryKey: ['document-settings'],
        queryFn: () => apiRequest(`${apiBase}/document-settings`),
    });

    useEffect(() => {
        if (settings.data?.data) setForm(settings.data.data);
    }, [settings.data]);

    const completed = useMemo(() => requiredFields.filter((field) => String(form[field] ?? '').trim()).length, [form]);
    const ready = completed === requiredFields.length;
    const update = (field, value) => setForm((current) => ({ ...current, [field]: value }));
    const save = useMutation({
        mutationFn: () => apiRequest(`${apiBase}/document-settings`, { method: 'POST', body: form }),
        onSuccess: (result) => {
            setForm(result.data);
            setFeedback(result.message);
            setError('');
            client.setQueryData(['document-settings'], result);
        },
        onError: (requestError) => {
            setError(firstError(requestError));
            setFeedback('');
        },
    });

    if (settings.isLoading) return <div className="state-panel tall"><Spinner label="กำลังโหลดข้อมูลเอกสาร" /></div>;

    return <>
        <PageHeader
            eyebrow="สำหรับ Admin ระดับตำบล"
            title="ตั้งค่าข้อมูลเอกสาร PDF"
            description="ข้อมูลส่วนนี้จะถูกนำไปใส่ในหนังสือราชการ แบบฟอร์ม ผู้ลงนาม และเอกสารการเงินของกลุ่มที่บัญชีนี้สร้าง"
            actions={<Button appearance="primary" icon={<SaveRegular />} disabled={save.isPending} onClick={() => save.mutate()}>{save.isPending ? 'กำลังบันทึก' : 'บันทึกการตั้งค่า'}</Button>}
        />

        <section className={`document-readiness ${ready ? 'is-ready' : ''}`}>
            <span className="document-readiness-icon">{ready ? <CheckmarkCircleRegular /> : <SettingsRegular />}</span>
            <div><strong>{ready ? 'ข้อมูลหลักพร้อมสร้างเอกสาร' : `กรอกข้อมูลหลักแล้ว ${completed} จาก ${requiredFields.length} รายการ`}</strong><p>{ready ? 'PDF ที่สร้างใหม่จะใช้ข้อมูลของตำบลนี้โดยอัตโนมัติ' : 'กรุณาตรวจข้อมูลหน่วยงาน ผู้รับสมัคร ผู้รับผิดชอบ และผู้บริหารให้ครบ'}</p></div>
        </section>

        <SuccessMessage message={feedback} />
        <ErrorMessage message={error || (settings.isError ? firstError(settings.error) : '')} />

        <form className="document-settings-stack" onSubmit={(event) => { event.preventDefault(); save.mutate(); }}>
            <section className="content-card document-setting-card">
                <div className="setting-card-heading"><span>01</span><div><h2>ข้อมูลส่วนราชการ</h2><p>ใช้กับหัวหนังสือภายนอก บันทึกข้อความ ประกาศ และที่อยู่ติดต่อ</p></div></div>
                <div className="form-grid two">
                    <TextField label="ชื่อ ศกร.ระดับตำบล" value={form.learning_center_name} onChange={(value) => update('learning_center_name', value)} required />
                    <TextField label="ชื่อ สกร.ระดับอำเภอ (เต็ม)" value={form.district_office_name} onChange={(value) => update('district_office_name', value)} required />
                    <TextField label="ชื่อ สกร.ระดับอำเภอ (ย่อ)" value={form.district_office_short_name} onChange={(value) => update('district_office_short_name', value)} required />
                    <TextField label="สำนักงาน สกร.ประจำจังหวัด" value={form.province_office_name} onChange={(value) => update('province_office_name', value)} required />
                    <TextField label="รหัสหนังสือราชการ" value={form.document_no_prefix} onChange={(value) => update('document_no_prefix', value)} required hint="ตัวอย่าง ศธ 07093.05" />
                    <TextField label="หน่วยงานเจ้าของเรื่อง" value={form.owner_unit} onChange={(value) => update('owner_unit', value)} required />
                </div>
                <Field label="ที่อยู่สำหรับหัวหนังสือ" required><Textarea rows={3} value={form.office_address ?? ''} onChange={(_, data) => update('office_address', data.value)} /></Field>
                <div className="form-grid two">
                    <TextField label="โทรศัพท์" value={form.phone} onChange={(value) => update('phone', value)} required />
                    <TextField label="โทรสาร" value={form.fax} onChange={(value) => update('fax', value)} />
                </div>
            </section>

            <section className="content-card document-setting-card">
                <div className="setting-card-heading"><span>02</span><div><h2>ผู้ปฏิบัติงานและผู้ลงนามหลัก</h2><p>ใช้ในใบสมัคร บันทึกขออนุมัติ หนังสือภายนอก และเกียรติบัตร</p></div></div>
                <div className="form-grid two">
                    <TextField label="ชื่อผู้รับสมัคร" value={form.registrar_name} onChange={(value) => update('registrar_name', value)} required />
                    <TextField label="ตำแหน่งผู้รับสมัคร" value={form.registrar_position} onChange={(value) => update('registrar_position', value)} />
                    <TextField label="ชื่อผู้รับผิดชอบโครงการ" value={form.responsible_name} onChange={(value) => update('responsible_name', value)} required />
                    <TextField label="ตำแหน่งผู้รับผิดชอบ" value={form.responsible_position} onChange={(value) => update('responsible_position', value)} />
                    <TextField label="ชื่อผู้บริหาร/ผู้ลงนาม" value={form.director_name} onChange={(value) => update('director_name', value)} required />
                    <TextField label="ตำแหน่งผู้บริหาร" value={form.director_position} onChange={(value) => update('director_position', value)} />
                </div>
            </section>

            <section className="content-card document-setting-card">
                <div className="setting-card-heading"><span>03</span><div><h2>การเงิน การนิเทศ และติดตามผล</h2><p>กรอกเมื่อมีผู้รับผิดชอบ เพื่อให้ใบสำคัญรับเงิน แบบเบิกจ่าย และแบบติดตามแสดงชื่อครบ</p></div></div>
                <div className="form-grid two">
                    <TextField label="ชื่อเจ้าหน้าที่การเงิน" value={form.finance_officer_name} onChange={(value) => update('finance_officer_name', value)} />
                    <TextField label="ตำแหน่งเจ้าหน้าที่การเงิน" value={form.finance_officer_position} onChange={(value) => update('finance_officer_position', value)} />
                    <TextField label="ชื่อผู้จ่ายเงิน" value={form.payer_name} onChange={(value) => update('payer_name', value)} />
                    <TextField label="ตำแหน่งผู้จ่ายเงิน" value={form.payer_position} onChange={(value) => update('payer_position', value)} />
                    <TextField label="ชื่อผู้รับรอง" value={form.certifier_name} onChange={(value) => update('certifier_name', value)} />
                    <TextField label="ตำแหน่งผู้รับรอง" value={form.certifier_position} onChange={(value) => update('certifier_position', value)} />
                    <TextField label="ชื่อผู้นิเทศ" value={form.supervisor_name} onChange={(value) => update('supervisor_name', value)} />
                    <TextField label="ตำแหน่งผู้นิเทศ" value={form.supervisor_position} onChange={(value) => update('supervisor_position', value)} />
                    <TextField label="ชื่อผู้ติดตามผล" value={form.follow_up_name} onChange={(value) => update('follow_up_name', value)} />
                    <TextField label="ตำแหน่งผู้ติดตามผล" value={form.follow_up_position} onChange={(value) => update('follow_up_position', value)} />
                </div>
            </section>

            <div className="form-submit"><Button type="submit" appearance="primary" icon={<SaveRegular />} disabled={save.isPending}>{save.isPending ? 'กำลังบันทึก' : 'บันทึกข้อมูลสำหรับ PDF'}</Button></div>
        </form>
    </>;
}
