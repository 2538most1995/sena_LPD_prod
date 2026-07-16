import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import {
    Button, Card, Dialog, DialogActions, DialogBody, DialogContent, DialogSurface,
    DialogTitle, Field, Input, Skeleton, SkeletonItem, Tab, TabList,
} from '../ui';
import {
    ArrowLeftRegular, BookRegular, CalendarRegular, CameraRegular, ChartMultipleRegular,
    CheckmarkCircleRegular, DeleteRegular, DocumentBulletListRegular, DocumentPdfRegular,
    MoneyRegular, PeopleRegular, SaveRegular, SearchRegular, UploadRegular,
} from '../ui/icons';
import { useNavigate, useOutletContext, useParams } from 'react-router-dom';
import { apiRequest, firstError } from '../api';
import PageHeader from '../components/PageHeader';
import StatusBadge from '../components/StatusBadge';
import { ErrorMessage, SuccessMessage } from '../components/Feedback';

const tabs = [
    { value: 'overview', label: 'ข้อมูลกลุ่ม', icon: DocumentBulletListRegular },
    { value: 'participants', label: 'ผู้เรียน', icon: PeopleRegular },
    { value: 'scores', label: 'คะแนน', icon: ChartMultipleRegular },
    { value: 'photos', label: 'ภาพกิจกรรม', icon: CameraRegular },
];

const scoreLimits = { knowledge: 20, skill: 40, attribute: 40 };

function personName(person) {
    return person ? `${person.prefix || ''}${person.first_name || ''} ${person.last_name || ''}`.trim() : 'ไม่ระบุ';
}

function thaiDate(value) {
    if (!value) return 'ไม่ระบุ';
    const date = new Date(`${String(value).slice(0, 10)}T12:00:00`);
    return new Intl.DateTimeFormat('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }).format(date);
}

function sameIds(left, right) {
    if (left.length !== right.length) return false;
    const a = [...left].map(Number).sort((x, y) => x - y);
    const b = [...right].map(Number).sort((x, y) => x - y);
    return a.every((value, index) => value === b[index]);
}

function scoreTotal(score = {}) {
    return Number(score.knowledge || 0) + Number(score.skill || 0) + Number(score.attribute || 0);
}

export default function ProjectWorkspacePage() {
    const { id } = useParams();
    const { apiBase } = useOutletContext();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const reduceMotion = useReducedMotion();
    const photoInputRef = useRef(null);
    const [tab, setTab] = useState('overview');
    const [studentIds, setStudentIds] = useState([]);
    const [studentSearch, setStudentSearch] = useState('');
    const [scores, setScores] = useState({});
    const [scoreBaseline, setScoreBaseline] = useState({});
    const [photoFiles, setPhotoFiles] = useState([]);
    const [photoCaptions, setPhotoCaptions] = useState([]);
    const [photoType, setPhotoType] = useState('activity');
    const [photoToDelete, setPhotoToDelete] = useState(null);
    const [feedback, setFeedback] = useState('');
    const [error, setError] = useState('');

    const projectQuery = useQuery({
        queryKey: ['project', id],
        queryFn: () => apiRequest(`${apiBase}/projects/${id}`),
    });
    const refs = useQuery({
        queryKey: ['references'],
        queryFn: () => apiRequest(`${apiBase}/references`),
    });
    const project = projectQuery.data?.data;

    useEffect(() => {
        if (!project) return;
        setStudentIds(project.students?.map((student) => Number(student.id)) ?? []);
        const nextScores = {};
        project.students?.forEach((student) => {
            nextScores[student.id] = student.scores ?? { knowledge: 0, skill: 0, attribute: 0 };
        });
        setScores(nextScores);
        setScoreBaseline(nextScores);
    }, [projectQuery.dataUpdatedAt, project]);

    const photoPreviews = useMemo(() => photoFiles.map((file) => ({ file, url: URL.createObjectURL(file) })), [photoFiles]);
    useEffect(() => () => photoPreviews.forEach((preview) => URL.revokeObjectURL(preview.url)), [photoPreviews]);

    const refresh = () => queryClient.invalidateQueries({ queryKey: ['project', id] });
    const beginMutation = () => { setFeedback(''); setError(''); };
    const finishMutation = async (result) => {
        await refresh();
        setFeedback(result.message);
        setError('');
    };

    const participantsMutation = useMutation({
        mutationFn: () => apiRequest(`${apiBase}/projects/${id}/participants`, {
            method: 'PUT',
            body: { student_ids: studentIds },
        }),
        onMutate: beginMutation,
        onSuccess: finishMutation,
        onError: (requestError) => setError(firstError(requestError)),
    });

    const scoreStudents = project?.students ?? [];
    const scoresMutation = useMutation({
        mutationFn: () => apiRequest(`${apiBase}/projects/${id}/scores`, {
            method: 'PUT',
            body: {
                scores: scoreStudents.map((student) => ({
                    student_id: student.id,
                    knowledge: Number(scores[student.id]?.knowledge || 0),
                    skill: Number(scores[student.id]?.skill || 0),
                    attribute: Number(scores[student.id]?.attribute || 0),
                })),
            },
        }),
        onMutate: beginMutation,
        onSuccess: async (result) => {
            setScoreBaseline(scores);
            await finishMutation(result);
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const photosMutation = useMutation({
        mutationFn: () => {
            const form = new FormData();
            form.append('photo_type', photoType);
            photoFiles.forEach((file, index) => {
                form.append('photos[]', file);
                form.append('captions[]', photoCaptions[index] || '');
            });
            return apiRequest(`${apiBase}/projects/${id}/photos`, { method: 'POST', body: form });
        },
        onMutate: beginMutation,
        onSuccess: async (result) => {
            setPhotoFiles([]);
            setPhotoCaptions([]);
            if (photoInputRef.current) photoInputRef.current.value = '';
            await finishMutation(result);
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const deletePhotoMutation = useMutation({
        mutationFn: (photoId) => apiRequest(`${apiBase}/projects/${id}/photos/${photoId}`, { method: 'DELETE' }),
        onMutate: beginMutation,
        onSuccess: async (result) => {
            setPhotoToDelete(null);
            await finishMutation(result);
        },
        onError: (requestError) => setError(firstError(requestError)),
    });

    const savedStudentIds = project?.students?.map((student) => Number(student.id)) ?? [];
    const participantsDirty = !sameIds(studentIds, savedStudentIds);
    const scoresDirty = scoreStudents.some((student) => ['knowledge', 'skill', 'attribute'].some((key) => (
        Number(scores[student.id]?.[key] || 0) !== Number(scoreBaseline[student.id]?.[key] || 0)
    )));
    const recordedScores = scoreStudents.filter((student) => student.score_recorded).length;
    const averageScore = recordedScores
        ? scoreStudents.filter((student) => student.score_recorded).reduce((sum, student) => sum + scoreTotal(scores[student.id]), 0) / recordedScores
        : 0;

    const filteredStudents = useMemo(() => {
        const keyword = studentSearch.trim().toLocaleLowerCase('th-TH');
        const list = refs.data?.data?.students ?? [];
        if (!keyword) return list;
        return list.filter((student) => `${personName(student)} ${student.id_card || ''}`.toLocaleLowerCase('th-TH').includes(keyword));
    }, [refs.data, studentSearch]);

    const completionItems = project ? [
        { label: 'รายชื่อผู้เรียน', detail: `${project.students?.length ?? 0} คน`, done: (project.students?.length ?? 0) > 0, tab: 'participants' },
        { label: 'คะแนนผู้เรียน', detail: `${recordedScores} จาก ${project.students?.length ?? 0} คน`, done: recordedScores > 0 && recordedScores === project.students?.length, tab: 'scores' },
        { label: 'ภาพประกอบกิจกรรม', detail: `${project.photos?.length ?? 0} ภาพ`, done: (project.photos?.length ?? 0) > 0, tab: 'photos' },
    ] : [];

    const toggleStudent = (studentId) => {
        const numericId = Number(studentId);
        setStudentIds((current) => current.includes(numericId)
            ? current.filter((value) => value !== numericId)
            : [...current, numericId]);
    };
    const selectVisibleStudents = () => setStudentIds((current) => [...new Set([...current, ...filteredStudents.map((student) => Number(student.id))])]);
    const clearVisibleStudents = () => {
        const visible = new Set(filteredStudents.map((student) => Number(student.id)));
        setStudentIds((current) => current.filter((studentId) => !visible.has(Number(studentId))));
    };
    const updateScore = (studentId, key, value) => setScores((current) => ({
        ...current,
        [studentId]: { ...(current[studentId] ?? {}), [key]: value },
    }));
    const normalizeScore = (studentId, key) => {
        const value = Number(scores[studentId]?.[key] || 0);
        updateScore(studentId, key, Math.min(scoreLimits[key], Math.max(0, Number.isFinite(value) ? value : 0)));
    };
    const changeTab = (value) => {
        setTab(value);
        setFeedback('');
        setError('');
    };
    const selectPhotos = (event) => {
        const files = Array.from(event.target.files ?? []);
        if (files.length > 4) {
            setError('เลือกภาพได้ครั้งละไม่เกิน 4 ภาพ');
            event.target.value = '';
            return;
        }
        const tooLarge = files.find((file) => file.size > 5 * 1024 * 1024);
        if (tooLarge) {
            setError(`ไฟล์ ${tooLarge.name} มีขนาดเกิน 5 MB`);
            event.target.value = '';
            return;
        }
        setError('');
        setPhotoFiles(files);
        setPhotoCaptions(files.map(() => ''));
    };
    const openPt7 = () => window.open(`${apiBase}/reports/open?type=pt&doc=6&project_id=${id}`, '_blank', 'noopener,noreferrer');

    let panelContent = null;
    if (project && tab === 'overview') {
        panelContent = (
            <div className="workspace-overview-grid">
                <section className="content-card workspace-detail-card">
                    <div className="workspace-section-heading">
                        <div><h2>รายละเอียดการจัดกิจกรรม</h2><p>ข้อมูลที่ใช้ในแบบฟอร์มและเอกสารราชการของกลุ่มนี้</p></div>
                        <BookRegular />
                    </div>
                    <dl className="workspace-detail-list">
                        <div><dt>รูปแบบการจัด</dt><dd>{project.format_type || 'ไม่ระบุ'}</dd></div>
                        <div><dt>ปีงบประมาณ</dt><dd>{project.fiscal_year}</dd></div>
                        <div><dt>วันจัดกิจกรรม</dt><dd>{thaiDate(project.start_date)} ถึง {thaiDate(project.end_date)}</dd></div>
                        <div><dt>เวลา</dt><dd>{project.start_time?.slice(0, 5) || 'ไม่ระบุ'} ถึง {project.end_time?.slice(0, 5) || 'ไม่ระบุ'}</dd></div>
                        <div className="full"><dt>วัตถุประสงค์</dt><dd>{project.objective || 'ไม่ระบุ'}</dd></div>
                        <div className="full"><dt>สถานที่</dt><dd>{[project.place, project.address].filter(Boolean).join(' ') || 'ไม่ระบุ'}</dd></div>
                    </dl>
                </section>
                <aside className="content-card workspace-progress-card">
                    <div className="workspace-section-heading"><div><h2>ความพร้อมของข้อมูล</h2><p>เลือกหัวข้อเพื่อจัดการต่อได้ทันที</p></div></div>
                    <div className="workspace-checklist">
                        {completionItems.map((item) => (
                            <button key={item.tab} type="button" className={item.done ? 'is-done' : ''} onClick={() => changeTab(item.tab)}>
                                <span><CheckmarkCircleRegular /></span>
                                <span><strong>{item.label}</strong><small>{item.detail}</small></span>
                            </button>
                        ))}
                    </div>
                </aside>
            </div>
        );
    }

    if (project && tab === 'participants') {
        panelContent = (
            <section className="content-card workspace-management-card">
                <div className="workspace-section-heading workspace-section-heading-actions">
                    <div><h2>จัดรายชื่อผู้เรียน</h2><p>ค้นหาและเลือกผู้เรียนที่เข้าร่วมกลุ่มกิจกรรมนี้</p></div>
                    <div className="selection-counter"><strong>{studentIds.length}</strong><span>คนที่เลือก</span></div>
                </div>
                <div className="participant-toolbar">
                    <Input contentBefore={<SearchRegular />} placeholder="ค้นหาชื่อหรือเลขประจำตัวประชาชน" value={studentSearch} onChange={(_, data) => setStudentSearch(data.value)} />
                    <div>
                        <Button appearance="subtle" onClick={selectVisibleStudents} disabled={!filteredStudents.length}>เลือกที่แสดงทั้งหมด</Button>
                        <Button appearance="subtle" onClick={clearVisibleStudents} disabled={!studentIds.length}>ล้างที่แสดง</Button>
                    </div>
                </div>
                {refs.isLoading ? <Skeleton className="participant-skeleton">{[1, 2, 3, 4, 5, 6].map((item) => <SkeletonItem key={item} size={64} />)}</Skeleton> : null}
                {!refs.isLoading && filteredStudents.length ? (
                    <div className="selection-list">
                        {filteredStudents.map((student) => (
                            <label key={student.id}>
                                <input type="checkbox" checked={studentIds.includes(Number(student.id))} onChange={() => toggleStudent(student.id)} />
                                <span className="selection-check"><CheckmarkCircleRegular /></span>
                                <span><strong>{personName(student)}</strong><small>{student.id_card || 'ไม่ระบุเลขประจำตัว'}</small></span>
                            </label>
                        ))}
                    </div>
                ) : null}
                {!refs.isLoading && !filteredStudents.length ? <div className="state-panel empty-state"><PeopleRegular /><strong>ไม่พบผู้เรียน</strong><span>ลองเปลี่ยนคำค้น หรือเพิ่มผู้เรียนในเมนูผู้เรียนก่อน</span></div> : null}
                <div className="workspace-savebar">
                    <span>{participantsDirty ? 'มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก' : 'รายชื่อตรงกับข้อมูลล่าสุดแล้ว'}</span>
                    <Button appearance="primary" icon={<SaveRegular />} onClick={() => participantsMutation.mutate()} disabled={!participantsDirty || participantsMutation.isPending}>
                        {participantsMutation.isPending ? 'กำลังบันทึก' : 'บันทึกรายชื่อ'}
                    </Button>
                </div>
            </section>
        );
    }

    if (project && tab === 'scores') {
        panelContent = (
            <section className="content-card workspace-management-card">
                <div className="workspace-section-heading workspace-section-heading-actions">
                    <div><h2>บันทึกคะแนนผู้เรียน</h2><p>ความรู้ 20 คะแนน ทักษะ 40 คะแนน และคุณลักษณะ 40 คะแนน</p></div>
                    <div className="score-summary">
                        <span><strong>{recordedScores}</strong> บันทึกแล้ว</span>
                        <span><strong>{averageScore.toLocaleString('th-TH', { maximumFractionDigits: 1 })}</strong> คะแนนเฉลี่ย</span>
                    </div>
                </div>
                {scoreStudents.length ? (
                    <div className="score-table-scroll">
                        <div className="score-table">
                            <div className="score-head"><span>ผู้เรียน</span><span>ความรู้</span><span>ทักษะ</span><span>คุณลักษณะ</span><span>รวม</span></div>
                            {scoreStudents.map((student) => {
                                const score = scores[student.id] ?? {};
                                const total = scoreTotal(score);
                                return (
                                    <div className="score-row" key={student.id}>
                                        <div className="score-person"><strong>{personName(student)}</strong><small>{student.id_card}</small></div>
                                        {Object.entries(scoreLimits).map(([key, limit]) => (
                                            <div className="score-input" key={key} data-label={key === 'knowledge' ? 'ความรู้' : key === 'skill' ? 'ทักษะ' : 'คุณลักษณะ'}>
                                                <Input
                                                    aria-label={`${key === 'knowledge' ? 'ความรู้' : key === 'skill' ? 'ทักษะ' : 'คุณลักษณะ'}ของ${personName(student)}`}
                                                    type="number"
                                                    min="0"
                                                    max={String(limit)}
                                                    value={String(score[key] ?? 0)}
                                                    onChange={(_, data) => updateScore(student.id, key, data.value)}
                                                    onBlur={() => normalizeScore(student.id, key)}
                                                />
                                                <small>/{limit}</small>
                                            </div>
                                        ))}
                                        <div className="score-total"><strong>{total.toLocaleString('th-TH', { maximumFractionDigits: 2 })}</strong><small>/100</small></div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ) : <div className="state-panel empty-state"><ChartMultipleRegular /><strong>ยังไม่มีผู้เรียนในกลุ่ม</strong><span>เพิ่มรายชื่อในแท็บผู้เรียนก่อนบันทึกคะแนน</span><Button onClick={() => changeTab('participants')}>ไปที่ผู้เรียน</Button></div>}
                <div className="workspace-savebar">
                    <span>{scoresDirty ? 'คะแนนมีการแก้ไขและยังไม่ได้บันทึก' : 'คะแนนเป็นข้อมูลล่าสุดแล้ว'}</span>
                    <Button appearance="primary" icon={<SaveRegular />} onClick={() => scoresMutation.mutate()} disabled={!scoreStudents.length || !scoresDirty || scoresMutation.isPending}>
                        {scoresMutation.isPending ? 'กำลังบันทึก' : 'บันทึกคะแนน'}
                    </Button>
                </div>
            </section>
        );
    }

    if (project && tab === 'photos') {
        panelContent = (
            <section className="content-card workspace-management-card">
                <div className="workspace-section-heading workspace-section-heading-actions">
                    <div><h2>ภาพวัสดุและภาพกิจกรรม</h2><p>ใช้สำหรับรายงานผลและเอกสารประกอบการจัดกิจกรรม</p></div>
                    <div className="selection-counter"><strong>{project.photos?.length ?? 0}</strong><span>ภาพในระบบ</span></div>
                </div>
                <div className="photo-uploader">
                    <Field label="ประเภทภาพ">
                        <select className="native-select" value={photoType} onChange={(event) => setPhotoType(event.target.value)}>
                            <option value="activity">ภาพกิจกรรม</option>
                            <option value="material">ภาพวัสดุ</option>
                        </select>
                    </Field>
                    <label className="photo-picker">
                        <input ref={photoInputRef} type="file" multiple accept="image/jpeg,image/png,image/webp" onChange={selectPhotos} />
                        <UploadRegular />
                        <span><strong>เลือกภาพจากเครื่อง</strong><small>สูงสุด 4 ภาพ ภาพละไม่เกิน 5 MB</small></span>
                    </label>
                </div>
                {photoPreviews.length ? (
                    <div className="photo-preview-section">
                        <div className="workspace-subheading"><strong>ภาพที่รออัปโหลด</strong><span>{photoPreviews.length} ภาพ</span></div>
                        <div className="photo-preview-grid">
                            {photoPreviews.map((preview, index) => (
                                <div key={`${preview.file.name}-${preview.file.lastModified}`}>
                                    <img src={preview.url} alt={`ตัวอย่าง ${preview.file.name}`} />
                                    <div><strong>{preview.file.name}</strong><Input placeholder="คำอธิบายภาพ (ถ้ามี)" value={photoCaptions[index] || ''} onChange={(_, data) => setPhotoCaptions((current) => current.map((value, itemIndex) => itemIndex === index ? data.value : value))} /></div>
                                </div>
                            ))}
                        </div>
                        <div className="workspace-savebar photo-upload-actions">
                            <Button appearance="subtle" onClick={() => { setPhotoFiles([]); setPhotoCaptions([]); if (photoInputRef.current) photoInputRef.current.value = ''; }}>ยกเลิกชุดนี้</Button>
                            <Button appearance="primary" icon={<UploadRegular />} disabled={photosMutation.isPending} onClick={() => photosMutation.mutate()}>{photosMutation.isPending ? 'กำลังอัปโหลด' : `อัปโหลด ${photoFiles.length} ภาพ`}</Button>
                        </div>
                    </div>
                ) : null}
                <div className="workspace-subheading gallery-heading"><strong>ภาพที่บันทึกแล้ว</strong><span>เรียงตามประเภทและลำดับการอัปโหลด</span></div>
                {project.photos?.length ? (
                    <div className="photo-grid">
                        {project.photos.map((photo) => (
                            <figure key={photo.id}>
                                <img src={photo.url} alt={photo.caption || (photo.photo_type === 'material' ? 'ภาพวัสดุ' : 'ภาพกิจกรรม')} />
                                <figcaption>
                                    <span><strong>{photo.photo_type === 'material' ? 'ภาพวัสดุ' : 'ภาพกิจกรรม'}</strong><small>{photo.caption || 'ไม่มีคำอธิบาย'}</small></span>
                                    <Button appearance="subtle" icon={<DeleteRegular />} aria-label="ลบภาพ" onClick={() => setPhotoToDelete(photo)} />
                                </figcaption>
                            </figure>
                        ))}
                    </div>
                ) : <div className="state-panel empty-state compact"><CameraRegular /><strong>ยังไม่มีภาพกิจกรรม</strong><span>เลือกภาพด้านบนเพื่อเริ่มจัดทำรายงานภาพ</span></div>}
            </section>
        );
    }

    return (
        <div className="project-workspace-page">
            <PageHeader
                eyebrow="พื้นที่จัดการกลุ่มกิจกรรม"
                title={project?.title || 'กำลังโหลดข้อมูล'}
                description={project ? `${project.course?.name || 'ไม่ระบุหลักสูตร'} จัดที่ ${project.place || 'ไม่ระบุสถานที่'}` : 'กำลังดึงข้อมูลกลุ่มกิจกรรม'}
                actions={<><Button icon={<ArrowLeftRegular />} onClick={() => navigate('/projects')}>กลับรายการ</Button><Button appearance="primary" icon={<DocumentPdfRegular />} onClick={openPt7} disabled={!project}>สร้าง พต.7</Button></>}
            />
            <SuccessMessage message={feedback} />
            <ErrorMessage message={error || projectQuery.error?.message || refs.error?.message} />

            {projectQuery.isLoading ? (
                <Skeleton className="workspace-loading">
                    <div>{[1, 2, 3, 4].map((item) => <SkeletonItem key={item} size={106} />)}</div>
                    <SkeletonItem size={58} />
                    <SkeletonItem size={280} />
                </Skeleton>
            ) : null}

            {project ? (
                <>
                    <section className="project-summary">
                        <Card className="project-status-card"><span>สถานะการอนุมัติ</span><StatusBadge value={project.approval_status} /></Card>
                        <Card><span className="summary-icon"><PeopleRegular /></span><span>ผู้เรียน</span><strong>{project.students?.length ?? 0} คน</strong></Card>
                        <Card><span className="summary-icon"><CalendarRegular /></span><span>วันจัดกิจกรรม</span><strong>{thaiDate(project.start_date)}</strong></Card>
                        <Card><span className="summary-icon"><MoneyRegular /></span><span>งบประมาณ</span><strong>{Number(project.total_budget || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท</strong></Card>
                    </section>

                    <div className="workspace-tabs-shell">
                        <TabList className="workspace-tabs" selectedValue={tab} onTabSelect={(_, data) => changeTab(data.value)} aria-label="ส่วนจัดการกลุ่มกิจกรรม">
                            {tabs.map(({ value, label, icon: Icon }) => (
                                <Tab key={value} id={`project-tab-${value}`} aria-controls={`project-panel-${value}`} value={value} icon={<Icon />} iconPosition="start">{label}</Tab>
                            ))}
                        </TabList>
                    </div>

                    <AnimatePresence mode="wait" initial={false}>
                        <motion.div
                            key={tab}
                            id={`project-panel-${tab}`}
                            role="tabpanel"
                            aria-labelledby={`project-tab-${tab}`}
                            className="workspace-panel"
                            initial={reduceMotion ? false : { opacity: 0, transform: 'translateY(6px)' }}
                            animate={{ opacity: 1, transform: 'translateY(0)' }}
                            exit={reduceMotion ? { opacity: 1 } : { opacity: 0, transform: 'translateY(-3px)' }}
                            transition={{ duration: reduceMotion ? 0 : 0.18, ease: [0.23, 1, 0.32, 1] }}
                        >
                            {panelContent}
                        </motion.div>
                    </AnimatePresence>
                </>
            ) : null}

            <Dialog open={Boolean(photoToDelete)} onOpenChange={(_, data) => !data.open && setPhotoToDelete(null)}>
                <DialogSurface className="confirm-dialog">
                    <DialogBody>
                        <DialogTitle>ลบภาพนี้หรือไม่</DialogTitle>
                        <DialogContent>
                            <p className="confirm-dialog-text">ภาพจะถูกนำออกจากรายงานและไม่สามารถเรียกคืนได้</p>
                            {photoToDelete ? <img className="confirm-photo" src={photoToDelete.url} alt={photoToDelete.caption || 'ภาพที่กำลังจะลบ'} /> : null}
                        </DialogContent>
                        <DialogActions>
                            <Button appearance="secondary" onClick={() => setPhotoToDelete(null)}>ยกเลิก</Button>
                            <Button appearance="primary" icon={<DeleteRegular />} disabled={deletePhotoMutation.isPending} onClick={() => deletePhotoMutation.mutate(photoToDelete.id)}>{deletePhotoMutation.isPending ? 'กำลังลบ' : 'ลบภาพ'}</Button>
                        </DialogActions>
                    </DialogBody>
                </DialogSurface>
            </Dialog>
        </div>
    );
}
