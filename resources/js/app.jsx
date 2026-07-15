import React, { lazy, Suspense, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider, useQuery, useQueryClient } from '@tanstack/react-query';
import { SenaProvider, Spinner } from './ui';
import { HashRouter, Navigate, Route, Routes } from 'react-router-dom';
import AppShell from './components/AppShell';
import { apiRequest } from './api';
import { senaDarkTheme, senaLightTheme } from './theme';

const LoginPage = lazy(() => import('./pages/LoginPage'));
const DashboardPage = lazy(() => import('./pages/DashboardPage'));
const CoursesPage = lazy(() => import('./pages/CoursesPage'));
const ProjectsPage = lazy(() => import('./pages/ProjectsPage'));
const ProjectWorkspacePage = lazy(() => import('./pages/ProjectWorkspacePage'));
const PeoplePage = lazy(() => import('./pages/PeoplePage'));
const ApprovalsPage = lazy(() => import('./pages/ApprovalsPage'));
const UsersPage = lazy(() => import('./pages/UsersPage'));
const NotificationsPage = lazy(() => import('./pages/NotificationsPage'));
const ReportsPage = lazy(() => import('./pages/ReportsPage'));
const ProfilePage = lazy(() => import('./pages/ProfilePage'));
const DocumentSettingsPage = lazy(() => import('./pages/DocumentSettingsPage'));
const AuditPage = lazy(() => import('./pages/AuditPage'));

const queryClient = new QueryClient({
    defaultOptions: {
        queries: { staleTime: 30_000, gcTime: 5 * 60_000, retry: 1, refetchOnWindowFocus: false },
        mutations: { retry: 0 },
    },
});

function RoleRoute({ user, roles, children }) {
    return roles.includes(user.role) ? children : <Navigate to="/" replace />;
}

function SenaApplication({ apiBase, assetsUrl, loginUrl, logoutUrl }) {
    const client = useQueryClient();
    const [dark, setDark] = useState(() => localStorage.getItem('sena-theme') === 'dark');
    const me = useQuery({ queryKey: ['me'], queryFn: () => apiRequest(`${apiBase}/me`), retry: false });
    const currentUser = me.data?.data;
    const user = currentUser ? { ...currentUser, photo_url: currentUser.photo_path ? `${apiBase}/profile/photo` : null } : null;
    const notifications = useQuery({
        queryKey: ['notifications', 'badge'],
        queryFn: () => apiRequest(`${apiBase}/notifications?per_page=100`),
        enabled: Boolean(user),
        refetchInterval: 30_000,
    });
    const unread = notifications.data?.data?.filter((item) => !item.is_read).length ?? 0;

    useEffect(() => {
        localStorage.setItem('sena-theme', dark ? 'dark' : 'light');
        document.documentElement.dataset.theme = dark ? 'dark' : 'light';
    }, [dark]);

    const afterLogin = async () => {
        await client.invalidateQueries({ queryKey: ['me'] });
        await me.refetch();
    };
    const afterLogout = async (phase) => {
        if (phase === 'prepare') {
            await client.cancelQueries();
            return;
        }
        client.setQueryData(['me'], { data: null });
        client.removeQueries({ predicate: (query) => query.queryKey[0] !== 'me' });
    };
    const updateUser = (next) => client.setQueryData(['me'], { data: next });

    if (me.isLoading) {
        return <SenaProvider theme={dark ? senaDarkTheme : senaLightTheme}><div className="boot-screen"><span className="brand-mark">S</span><Spinner label="กำลังเปิดระบบ Sena LPD" /></div></SenaProvider>;
    }

    return (
        <SenaProvider theme={dark ? senaDarkTheme : senaLightTheme} className="sena-root">
          <Suspense fallback={<div className="state-panel tall"><Spinner label="กำลังเปิดหน้าใช้งาน" /></div>}>
            <Routes>
                <Route path="/login" element={user ? <Navigate to="/" replace /> : <LoginPage loginUrl={loginUrl} assetsUrl={assetsUrl} onLogin={afterLogin} />} />
                {user ? (
                    <Route element={<AppShell user={user} apiBase={apiBase} logoutUrl={logoutUrl} dark={dark} onToggleDark={() => setDark((value) => !value)} unread={unread} onLogout={afterLogout} />}>
                        <Route index element={<DashboardPage />} />
                        <Route path="courses" element={<CoursesPage />} />
                        <Route path="projects" element={<ProjectsPage />} />
                        <Route path="projects/:id" element={<ProjectWorkspacePage />} />
                        <Route path="students" element={<PeoplePage type="students" />} />
                        <Route path="lecturers" element={<PeoplePage type="lecturers" />} />
                        <Route path="approvals" element={<RoleRoute user={user} roles={['district_admin', 'super_admin']}><ApprovalsPage /></RoleRoute>} />
                        <Route path="users" element={<RoleRoute user={user} roles={['district_admin', 'super_admin']}><UsersPage /></RoleRoute>} />
                        <Route path="notifications" element={<NotificationsPage />} />
                        <Route path="reports" element={<ReportsPage />} />
                        <Route path="profile" element={<ProfilePage onUserChange={updateUser} />} />
                        <Route path="document-settings" element={<RoleRoute user={user} roles={['subdistrict_admin']}><DocumentSettingsPage /></RoleRoute>} />
                        <Route path="audit" element={<RoleRoute user={user} roles={['super_admin']}><AuditPage /></RoleRoute>} />
                    </Route>
                ) : null}
                <Route path="*" element={<Navigate to={user ? '/' : '/login'} replace />} />
            </Routes>
          </Suspense>
        </SenaProvider>
    );
}

const rootElement = document.getElementById('sena-next-root');
if (rootElement) {
    createRoot(rootElement).render(
        <React.StrictMode>
            <QueryClientProvider client={queryClient}>
                <HashRouter>
                    <SenaApplication
                        apiBase={rootElement.dataset.apiBase}
                        assetsUrl={rootElement.dataset.assetsUrl}
                        loginUrl={rootElement.dataset.loginUrl}
                        logoutUrl={rootElement.dataset.logoutUrl}
                    />
                </HashRouter>
            </QueryClientProvider>
        </React.StrictMode>,
    );
}
