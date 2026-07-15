import React, { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useOutletContext } from 'react-router-dom';
import { apiRequest } from '../api';
import DataTable from '../components/DataTable';
import PageHeader from '../components/PageHeader';
import { ErrorMessage } from '../components/Feedback';

const actionLabels = { 'course.created': 'เพิ่มหลักสูตร', 'course.updated': 'แก้ไขหลักสูตร', 'course.deleted': 'ลบหลักสูตร', 'course.reviewed': 'พิจารณาหลักสูตร', 'project.created': 'จัดตั้งกลุ่ม', 'project.updated': 'แก้ไขกลุ่ม', 'project.deleted': 'ลบกลุ่ม', 'project.reviewed': 'พิจารณาจัดตั้งกลุ่ม', 'user.created': 'เพิ่มผู้ดูแล', 'user.updated': 'แก้ไขผู้ดูแล', 'user.deactivated': 'ระงับผู้ดูแล', 'profile.updated': 'แก้ไขโปรไฟล์', 'document_settings.updated': 'แก้ไขข้อมูลเอกสาร PDF' };

export default function AuditPage() {
    const { apiBase } = useOutletContext();
    const query = useQuery({ queryKey: ['audit-logs'], queryFn: () => apiRequest(`${apiBase}/audit-logs`) });
    const columns = useMemo(() => [
        { header: 'วันและเวลา', accessorKey: 'created_at', cell: ({ getValue }) => new Intl.DateTimeFormat('th-TH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(getValue())) },
        { header: 'การดำเนินการ', accessorKey: 'action', cell: ({ getValue }) => actionLabels[getValue()] || getValue() },
        { header: 'ผู้ดำเนินการ', cell: ({ row }) => <div className="primary-cell"><strong>{row.original.user?.display_name || 'ระบบ'}</strong><span>{row.original.user?.school_name}</span></div> },
        { header: 'รายการ', cell: ({ row }) => `${row.original.subject_type?.split('\\').pop() || ''} #${row.original.subject_id ?? ''}` },
        { header: 'IP', accessorKey: 'ip_address' },
    ], []);
    return <><PageHeader eyebrow="ตรวจสอบย้อนหลัง" title="ประวัติการใช้งานระบบ" description="บันทึกการเพิ่ม แก้ไข ลบ และอนุมัติรายการสำคัญเพื่อความโปร่งใส" /><ErrorMessage message={query.error?.message} /><section className="content-card"><DataTable columns={columns} data={query.data?.data ?? []} loading={query.isLoading} emptyTitle="ยังไม่มีประวัติการใช้งาน" /></section></>;
}
