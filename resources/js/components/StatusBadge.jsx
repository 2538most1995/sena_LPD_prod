import React from 'react';
import { Badge } from '../ui';

const appearances = {
    approved: ['filled', 'success', 'อนุมัติแล้ว'],
    pending: ['tint', 'warning', 'รออนุมัติ'],
    revision: ['tint', 'danger', 'แก้ไข'],
    active: ['tint', 'success', 'เปิดใช้งาน'],
    inactive: ['tint', 'subtle', 'ปิดใช้งาน'],
};

export default function StatusBadge({ value }) {
    const [appearance, color, label] = appearances[value] ?? ['tint', 'subtle', value || 'ไม่ระบุ'];
    return <Badge appearance={appearance} color={color}>{label}</Badge>;
}
