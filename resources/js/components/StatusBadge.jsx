import React from 'react';
import { Badge } from '../ui';

const appearances = {
    approved: ['filled', 'success', 'อนุมัติแล้ว'],
    pending: ['tint', 'warning', 'รออนุมัติ'],
    revision: ['tint', 'danger', 'ส่งกลับแก้ไข'],
    active: ['tint', 'success', 'เปิดใช้งาน'],
    inactive: ['tint', 'subtle', 'ปิดใช้งาน'],
    ฉบับร่าง: ['tint', 'informative', 'ฉบับร่าง'],
    รออนุมัติ: ['tint', 'warning', 'รออนุมัติ'],
    กำลังดำเนินการ: ['tint', 'brand', 'กำลังดำเนินการ'],
    เสร็จสิ้น: ['filled', 'success', 'เสร็จสิ้น'],
};

export default function StatusBadge({ value }) {
    const [appearance, color, label] = appearances[value] ?? ['tint', 'subtle', value || 'ไม่ระบุ'];
    return <Badge appearance={appearance} color={color}>{label}</Badge>;
}
