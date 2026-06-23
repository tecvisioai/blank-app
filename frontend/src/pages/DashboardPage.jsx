import { useEffect, useState } from 'react';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';

export default function DashboardPage() {
  const [reports, setReports] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    api.get('/reports')
      .then(r => setReports(r.data.reports))
      .catch(() => setError('Unable to load dashboard.'));
  }, []);

  return (
    <div>
      <SectionHeader title="Dashboard" subtitle="Project and activity health at a glance." />
      {error && <div className="form-error">{error}</div>}
      <div className="dashboard-grid">
        <div className="dashboard-card">
          <h2>Project Planned Hours</h2>
          <ul>
            {reports?.project_planned_hours?.slice(0, 4).map(item => (
              <li key={item.project_id}>{item.project_name}: {item.planned_hours}</li>
            ))}
          </ul>
        </div>
        <div className="dashboard-card">
          <h2>Project Actual Hours</h2>
          <ul>
            {reports?.project_actual_hours?.slice(0, 4).map(item => (
              <li key={item.project_id}>{item.project_name}: {item.actual_hours}</li>
            ))}
          </ul>
        </div>
        <div className="dashboard-card">
          <h2>User Actual Hours</h2>
          <ul>
            {reports?.user_actual_hours?.slice(0, 4).map(item => (
              <li key={item.user_id}>{item.user_name}: {item.actual_hours}</li>
            ))}
          </ul>
        </div>
      </div>
    </div>
  );
}
