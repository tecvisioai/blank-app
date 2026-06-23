import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from './context/AuthContext.jsx';
import LoginPage from './pages/LoginPage.jsx';
import PasswordResetRequestPage from './pages/PasswordResetRequestPage.jsx';
import PasswordResetPage from './pages/PasswordResetPage.jsx';
import DashboardPage from './pages/DashboardPage.jsx';
import UserManagementPage from './pages/UserManagementPage.jsx';
import ProjectListPage from './pages/ProjectListPage.jsx';
import ProjectDetailPage from './pages/ProjectDetailPage.jsx';
import WeeklyActualsPage from './pages/WeeklyActualsPage.jsx';
import ReportsPage from './pages/ReportsPage.jsx';
import Layout from './components/Layout.jsx';

function ProtectedRoute({ children }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="app-shell">Loading...</div>;
  if (!user) return <Navigate replace to="/login" />;
  return children;
}

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/password-reset-request" element={<PasswordResetRequestPage />} />
        <Route path="/password-reset/:token" element={<PasswordResetPage />} />
        <Route
          path="/*"
          element={
            <ProtectedRoute>
              <Layout />
            </ProtectedRoute>
          }
        >
          <Route index element={<DashboardPage />} />
          <Route path="users" element={<UserManagementPage />} />
          <Route path="projects" element={<ProjectListPage />} />
          <Route path="projects/:id" element={<ProjectDetailPage />} />
          <Route path="weekly-actuals" element={<WeeklyActualsPage />} />
          <Route path="reports" element={<ReportsPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
