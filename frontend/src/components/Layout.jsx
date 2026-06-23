import { NavLink, Outlet } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

export default function Layout() {
  const { user, logout } = useAuth();
  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="brand">Project Tracker</div>
        <nav>
          <NavLink to="/" end>Dashboard</NavLink>
          <NavLink to="/projects">Projects</NavLink>
          <NavLink to="/weekly-actuals">Weekly Actuals</NavLink>
          <NavLink to="/reports">Reports</NavLink>
          {user?.role === 'Admin' && <NavLink to="/users">User Management</NavLink>}
        </nav>
      </aside>
      <main className="main-content">
        <header className="topbar">
          <div>Welcome, {user?.full_name}</div>
          <button className="button secondary" onClick={logout}>Logout</button>
        </header>
        <section className="page-content">
          <Outlet />
        </section>
      </main>
    </div>
  );
}
