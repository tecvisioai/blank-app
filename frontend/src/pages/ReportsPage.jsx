import { useEffect, useState } from 'react';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';

function renderBar(value, total) {
  const pct = total > 0 ? Math.round((value / total) * 100) : 0;
  return (
    <div className="report-bar-row">
      <div className="report-bar-label">{value.label}</div>
      <div className="report-bar-track">
        <div className="report-bar-fill" style={{ width: `${pct}%` }} />
      </div>
      <div className="report-bar-value">{value.value}</div>
    </div>
  );
}

export default function ReportsPage() {
  const [reports, setReports] = useState(null);
  const [lookups, setLookups] = useState({ lookups: {}, groups: [], users: [] });
  const [filters, setFilters] = useState({ start_date: '', end_date: '', status: '', priority: '', group: '', owner_user_id: '' });
  const [error, setError] = useState('');

  const loadLookups = () => {
    api.get('/lookups')
      .then(r => setLookups(r.data))
      .catch(() => setError('Unable to load lookup data.'));
  };

  const loadReports = () => {
    api.get('/reports', { params: filters })
      .then(r => setReports(r.data.reports))
      .catch(() => setError('Unable to load reports.'));
  };

  useEffect(() => {
    loadLookups();
    loadReports();
  }, []);

  const handleChange = e => {
    const { name, value } = e.target;
    setFilters(prev => ({ ...prev, [name]: value }));
  };

  const applyFilters = e => {
    e.preventDefault();
    setError('');
    loadReports();
  };

  const statusTotal = reports?.status_summary?.reduce((sum, item) => sum + item.projects, 0) || 0;
  const priorityTotal = reports?.priority_summary?.reduce((sum, item) => sum + item.projects, 0) || 0;

  return (
    <div>
      <SectionHeader title="Reports" subtitle="View project and actual hour summaries." />
      <div className="form-panel">
        <form className="filter-row" onSubmit={applyFilters}>
          <label>Start</label>
          <input name="start_date" type="date" value={filters.start_date} onChange={handleChange} />
          <label>End</label>
          <input name="end_date" type="date" value={filters.end_date} onChange={handleChange} />
          <label>Group</label>
          <select name="group" value={filters.group} onChange={handleChange}>
            <option value="">All groups</option>
            {lookups.groups.map(group => <option key={group.id} value={group.name}>{group.name}</option>)}
          </select>
          <label>Owner</label>
          <select name="owner_user_id" value={filters.owner_user_id} onChange={handleChange}>
            <option value="">All owners</option>
            {lookups.users.map(user => <option key={user.id} value={user.id}>{user.full_name}</option>)}
          </select>
          <label>Status</label>
          <select name="status" value={filters.status} onChange={handleChange}>
            <option value="">All statuses</option>
            {lookups.lookups.project_status?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
          </select>
          <label>Priority</label>
          <select name="priority" value={filters.priority} onChange={handleChange}>
            <option value="">All priorities</option>
            {lookups.lookups.project_priority?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
          </select>
          <button type="submit" className="button">Apply</button>
        </form>
      </div>
      {error && <div className="form-error">{error}</div>}
      <div className="reports-grid">
        <div className="report-card">
          <h3>Project Planned Hours</h3>
          <ul>{reports?.project_planned_hours?.map(item => <li key={item.project_id}>{item.project_name}: {item.planned_hours}</li>)}</ul>
        </div>
        <div className="report-card">
          <h3>Project Actual Hours</h3>
          <ul>{reports?.project_actual_hours?.map(item => <li key={item.project_id}>{item.project_name}: {item.actual_hours}</li>)}</ul>
        </div>
        <div className="report-card">
          <h3>User Actual Hours</h3>
          <ul>{reports?.user_actual_hours?.map(item => <li key={item.user_id}>{item.user_name}: {item.actual_hours}</li>)}</ul>
        </div>
        <div className="report-card">
          <h3>Status Summary</h3>
          {reports?.status_summary?.length ? reports.status_summary.map(item => renderBar({ label: item.status, value: item.projects }, statusTotal)) : <p>No data</p>}
        </div>
        <div className="report-card">
          <h3>Priority Summary</h3>
          {reports?.priority_summary?.length ? reports.priority_summary.map(item => renderBar({ label: item.priority, value: item.projects }, priorityTotal)) : <p>No data</p>}
        </div>
      </div>
    </div>
  );
}
