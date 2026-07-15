import React, { useState } from 'react';
import { Button, Field, Input, Spinner } from '@fluentui/react-components';
import { BookRegular, EyeOffRegular, EyeRegular, LockClosedRegular, PersonRegular } from '@fluentui/react-icons';
import { useNavigate } from 'react-router-dom';
import { apiRequest, firstError } from '../api';
import { ErrorMessage } from '../components/Feedback';

export default function LoginPage({ loginUrl, assetsUrl, onLogin }) {
    const [schoolId, setSchoolId] = useState('');
    const [password, setPassword] = useState('');
    const [visible, setVisible] = useState(false);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const navigate = useNavigate();

    const submit = async (event) => {
        event.preventDefault();
        setBusy(true);
        setError('');
        try {
            const payload = await apiRequest(loginUrl, {
                method: 'POST',
                body: { school_id: schoolId, password },
            });
            onLogin(payload.data);
            navigate('/', { replace: true });
        } catch (requestError) {
            setPassword('');
            setError(firstError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <main className="login-layout">
            <section className="login-visual" style={{ '--login-image': `url(${assetsUrl}/sena-learning-hero.png)` }}>
                <div className="login-brand"><span><BookRegular /></span><strong>Sena LPD</strong></div>
                <div className="login-message">
                    <p>สกร.ระดับอำเภอเสนา</p>
                    <h1>เรียนรู้วันนี้<br />พัฒนาชีวิตได้ทุกวัน</h1>
                    <span>จัดการหลักสูตร ผู้เรียน กิจกรรม การอนุมัติ และรายงานในระบบเดียว</span>
                </div>
            </section>
            <section className="login-panel">
                <form className="login-form" onSubmit={submit}>
                    <div>
                        <p className="page-eyebrow">Sena LPD</p>
                        <h2>ยินดีต้อนรับ</h2>
                        <p>เข้าสู่ระบบการเรียนรู้เพื่อการพัฒนาตนเอง</p>
                    </div>
                    <ErrorMessage message={error} />
                    <Field label="รหัสสถานศึกษา" required>
                        <Input
                            size="large"
                            contentBefore={<PersonRegular />}
                            value={schoolId}
                            onChange={(_, data) => setSchoolId(data.value)}
                            autoComplete="username"
                            inputMode="numeric"
                            autoFocus
                        />
                    </Field>
                    <Field label="รหัสผ่าน" required>
                        <Input
                            size="large"
                            type={visible ? 'text' : 'password'}
                            contentBefore={<LockClosedRegular />}
                            contentAfter={(
                                <Button
                                    type="button"
                                    appearance="transparent"
                                    icon={visible ? <EyeOffRegular /> : <EyeRegular />}
                                    aria-label={visible ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน'}
                                    onClick={() => setVisible((value) => !value)}
                                />
                            )}
                            value={password}
                            onChange={(_, data) => setPassword(data.value)}
                            autoComplete="current-password"
                        />
                    </Field>
                    <Button appearance="primary" size="large" type="submit" disabled={busy || !schoolId || !password}>
                        {busy ? <Spinner size="tiny" label="กำลังเข้าสู่ระบบ" /> : 'เข้าสู่ระบบ'}
                    </Button>
                    <p className="login-help">พบปัญหาการใช้งาน ติดต่อผู้ดูแลระบบ สกร.ระดับอำเภอเสนา</p>
                </form>
            </section>
        </main>
    );
}
