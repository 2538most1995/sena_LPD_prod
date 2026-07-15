import React, { useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { Avatar, Badge, Button } from '@fluentui/react-components';
import {
    AlertRegular,
    BookRegular,
    BuildingGovernmentRegular,
    ChartMultipleRegular,
    CheckmarkCircleRegular,
    ClipboardTaskListLtrRegular,
    DismissRegular,
    HatGraduationRegular,
    HistoryRegular,
    HomeRegular,
    NavigationRegular,
    PeopleRegular,
    PersonRegular,
    SignOutRegular,
    SettingsRegular,
    WeatherMoonRegular,
    WeatherSunnyRegular,
} from '@fluentui/react-icons';
import { apiRequest } from '../api';

const roleLabels = {
    super_admin: 'Super Admin',
    district_admin: 'Admin ระดับอำเภอ',
    subdistrict_admin: 'Admin ระดับตำบล',
};

function linksFor(user) {
    const links = [
        ['/', 'ภาพรวม', HomeRegular],
        ['/courses', 'หลักสูตร', BookRegular],
        ['/projects', 'จัดตั้งกลุ่ม', ClipboardTaskListLtrRegular],
        ['/students', 'ผู้เรียน', PeopleRegular],
        ['/lecturers', 'วิทยากร', HatGraduationRegular],
    ];
    if (['district_admin', 'super_admin'].includes(user.role)) {
        links.push(['/approvals', 'อนุมัติระดับอำเภอ', CheckmarkCircleRegular]);
    }
    links.push(['/reports', 'เอกสารและรายงาน', ChartMultipleRegular]);
    if (user.role === 'subdistrict_admin') {
        links.push(['/document-settings', 'ตั้งค่าข้อมูลเอกสาร', SettingsRegular]);
    }
    if (['district_admin', 'super_admin'].includes(user.role)) {
        links.push(['/users', 'ผู้ดูแลระบบ', BuildingGovernmentRegular]);
    }
    if (user.role === 'super_admin') links.push(['/audit', 'ประวัติระบบ', HistoryRegular]);
    return links;
}

export default function AppShell({ user, apiBase, logoutUrl, dark, onToggleDark, unread = 0, onLogout }) {
    const [open, setOpen] = useState(false);
    const navigate = useNavigate();

    const logout = async () => {
        await onLogout('prepare');
        await apiRequest(logoutUrl, { method: 'POST' });
        await onLogout('finish');
        navigate('/login', { replace: true });
    };

    return (
        <div className={`app-frame ${open ? 'nav-open' : ''}`}>
            <button className="nav-backdrop" aria-label="ปิดเมนู" onClick={() => setOpen(false)} />
            <aside className="side-nav" aria-label="เมนูหลัก">
                <div className="brand-block">
                    <span className="brand-mark"><BookRegular /></span>
                    <span><strong>Sena LPD</strong><small>Learning for Personal Development</small></span>
                    <Button className="nav-close" appearance="subtle" icon={<DismissRegular />} aria-label="ปิดเมนู" onClick={() => setOpen(false)} />
                </div>

                <nav className="nav-list">
                    {linksFor(user).map(([to, label, Icon]) => (
                        <NavLink key={to} to={to} end={to === '/'} onClick={() => setOpen(false)}>
                            <Icon />
                            <span>{label}</span>
                            {to === '/approvals' && unread > 0 ? <Badge color="warning" size="small">{unread}</Badge> : null}
                        </NavLink>
                    ))}
                </nav>

                <div className="nav-account">
                    <Avatar
                        name={user.teacher_name || user.display_name}
                        image={user.photo_url ? { src: user.photo_url } : undefined}
                        color="colorful"
                    />
                    <span><strong>{user.display_name}</strong><small>{roleLabels[user.role]}</small></span>
                </div>
            </aside>

            <div className="workspace">
                <header className="topbar">
                    <Button className="menu-button" appearance="subtle" icon={<NavigationRegular />} aria-label="เปิดเมนู" onClick={() => setOpen(true)} />
                    <div className="topbar-context">
                        <strong>{user.school_name || user.display_name}</strong>
                        <span>{roleLabels[user.role]}</span>
                    </div>
                    <div className="topbar-actions">
                        <Button
                            className="topbar-action"
                            type="button"
                            appearance="subtle"
                            icon={dark ? <WeatherSunnyRegular /> : <WeatherMoonRegular />}
                            aria-label={dark ? 'ใช้โหมดสว่าง' : 'ใช้โหมดมืด'}
                            title={dark ? 'ใช้โหมดสว่าง' : 'ใช้โหมดมืด'}
                            onClick={onToggleDark}
                        />
                        <Button
                            className="topbar-action"
                            type="button"
                            appearance="subtle"
                            icon={<AlertRegular />}
                            aria-label="การแจ้งเตือน"
                            title="การแจ้งเตือน"
                            onClick={() => navigate('/notifications')}
                        >
                            {unread > 0 ? <Badge className="topbar-badge" color="warning" size="small">{unread}</Badge> : null}
                        </Button>
                        <Button
                            className="topbar-action"
                            type="button"
                            appearance="subtle"
                            icon={<PersonRegular />}
                            aria-label="ข้อมูลส่วนตัว"
                            title="ข้อมูลส่วนตัว"
                            onClick={() => navigate('/profile')}
                        />
                        <Button
                            className="topbar-action"
                            type="button"
                            appearance="subtle"
                            icon={<SignOutRegular />}
                            aria-label="ออกจากระบบ"
                            title="ออกจากระบบ"
                            onClick={logout}
                        />
                    </div>
                </header>
                <main className="page-content">
                    <Outlet context={{ user, apiBase }} />
                </main>
            </div>
        </div>
    );
}
