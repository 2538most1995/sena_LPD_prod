import React, { useState } from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { AnimatePresence, motion, useReducedMotion } from 'motion/react';
import { Avatar, Badge, Button } from '../ui';
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
} from '../ui/icons';
import { apiRequest } from '../api';

const roleLabels = {
    super_admin: 'Super Admin',
    district_admin: 'Admin ระดับอำเภอ',
    subdistrict_admin: 'Admin ระดับตำบล',
};

function linksFor(user) {
    const links = [
        ['งานหลัก', '/', 'ภาพรวม', HomeRegular],
        ['งานหลัก', '/courses', 'หลักสูตร', BookRegular],
        ['งานหลัก', '/projects', 'จัดตั้งกลุ่ม', ClipboardTaskListLtrRegular],
        ['งานหลัก', '/students', 'ผู้เรียน', PeopleRegular],
        ['งานหลัก', '/lecturers', 'วิทยากร', HatGraduationRegular],
    ];
    if (['district_admin', 'super_admin'].includes(user.role)) {
        links.push(['การดำเนินงาน', '/approvals', 'อนุมัติระดับอำเภอ', CheckmarkCircleRegular]);
    }
    links.push(['การดำเนินงาน', '/reports', 'เอกสารและรายงาน', ChartMultipleRegular]);
    if (user.role === 'subdistrict_admin') {
        links.push(['จัดการระบบ', '/document-settings', 'ตั้งค่าข้อมูลเอกสาร', SettingsRegular]);
    }
    if (['district_admin', 'super_admin'].includes(user.role)) {
        links.push(['จัดการระบบ', '/users', 'ผู้ดูแลระบบ', BuildingGovernmentRegular]);
    }
    if (user.role === 'super_admin') links.push(['จัดการระบบ', '/audit', 'ประวัติระบบ', HistoryRegular]);

    return links.reduce((groups, [section, to, label, Icon]) => {
        const current = groups.find((group) => group.label === section);
        if (current) current.links.push([to, label, Icon]);
        else groups.push({ label: section, links: [[to, label, Icon]] });
        return groups;
    }, []);
}

export default function AppShell({ user, apiBase, logoutUrl, dark, onToggleDark, unread = 0, onLogout }) {
    const [open, setOpen] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();
    const reduceMotion = useReducedMotion();

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
                    <span><strong>Sena LPD</strong><small>ระบบบริหารการเรียนรู้</small></span>
                    <Button className="nav-close" appearance="subtle" icon={<DismissRegular />} aria-label="ปิดเมนู" onClick={() => setOpen(false)} />
                </div>

                <nav className="nav-list">
                    {linksFor(user).map((group) => (
                        <div className="nav-section" key={group.label}>
                            <span className="nav-section-label">{group.label}</span>
                            {group.links.map(([to, label, Icon]) => (
                                <NavLink key={to} to={to} end={to === '/'} onClick={() => setOpen(false)}>
                                    <Icon />
                                    <span>{label}</span>
                                    {to === '/approvals' && unread > 0 ? <Badge color="warning" size="small">{unread}</Badge> : null}
                                </NavLink>
                            ))}
                        </div>
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
                        <span>สถานศึกษาที่กำลังใช้งาน</span>
                        <strong>{user.school_name || user.display_name}</strong>
                    </div>
                    <span className="topbar-role">{roleLabels[user.role]}</span>
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
                <AnimatePresence initial={false} mode="sync">
                    <motion.main
                        key={location.pathname}
                        className="page-content"
                        initial={reduceMotion ? false : { opacity: 0, transform: 'translateY(8px)' }}
                        animate={{ opacity: 1, transform: 'translateY(0)' }}
                        exit={reduceMotion ? { opacity: 1 } : { opacity: 0, transform: 'translateY(-4px)' }}
                        transition={{ duration: reduceMotion ? 0 : 0.22, ease: [0.23, 1, 0.32, 1] }}
                    >
                        <Outlet context={{ user, apiBase }} />
                    </motion.main>
                </AnimatePresence>
            </div>
        </div>
    );
}
