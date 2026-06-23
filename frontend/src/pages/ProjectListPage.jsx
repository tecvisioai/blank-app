import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';
import Pagination from '../components/Pagination.jsx';

const initialForm = { name: '', phase: '', type: '', status: '', priority: '', business_team: '', business_lead: '', owner_user_id: '', start_date: '', end_date: '' };

export default function ProjectListPage() {
  const [projects, setProjects] = useState([]);
  const [lookups, setLookups] = useState({ lookups: {}, users: [] });
  const [form, setForm] = useState(initialForm);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [filters, setFilters] = useState({ status: '', priority: '', owner_user_id: '', search: '', page: 1, page_size: 10 });
  const [total, setTotal] = useState(0);
  const navigate = useNavigate();

  const loadLookups = () => {
    api.get('/lookups').then(r => setLookups(r.data)).catch(() => setError('Unable to load lookup data.'));
  };

  const loadProjects = params => {
    const payload = params || filters;
    api.get('/projects', { params: payload })
      .then(r => {
        setProjects(r.data.projects);
        setTotal(r.data.total || 0);
      })
      .catch(() => setError('Unable to load projects.'));
  };

  useEffect(() => {
    loadLookups();
    loadProjects();
  }, []);

  const handleChange = e => {
    const { name, value } = e.target;
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleFilterChange = e => {
    const { name, value } = e.target;
    setFilters(prev => ({ ...prev, [name]: value, page: 1 }));
  };

  const handleSubmit = async e => {
    e.preventDefault();
    setMessage('');
    setError('');
    try {
      await api.post('/projects', form);
      setMessage('Project created.');
      setForm(initialForm);
      loadProjects({ ...filters, page: 1 });
    } catch (err) {
      setError(err.response?.data?.message || 'Save failed.');
    }
  };

  const applyFilters = e => {
    e.preventDefault();
    setMessage('');
    setError('');
    setFilters(prev => ({ ...prev, page: 1 }));
    loadProjects({ ...filters, page: 1 });
  };

  const handlePageChange = nextPage => {
    const nextFilters = { ...filters, page: nextPage };
    setFilters(nextFilters);
    loadProjects(nextFilters);
  };

  return (
    <div>
      <SectionHeader title="Projects" subtitle="Manage project master records and visibility." />
      <div className="row-grid">
        <div className="form-panel">
          <form onSubmit={handleSubmit}>
            <h2>Add New Project</h2>
            <label>Project Name</label>
            <input name="name" value={form.name} onChange={handleChange} required />
            <label>Phase</label>
            <select name="phase" value={form.phase} onChange={handleChange} required>
              <option value="">Choose phase</option>
              {lookups.lookups.project_phase?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
            </select>
            <label>Type</label>
            <select name="type" value={form.type} onChange={handleChange} required>
              <option value="">Choose type</option>
              {lookups.lookups.project_type?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
            </select>
            <label>Status</label>
            <select name="status" value={form.status} onChange={handleChange} required>
              <option value="">Choose status</option>
              {lookups.lookups.project_status?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
            </select>
            <label>Priority</label>
            <select name="priority" value={form.priority} onChange={handleChange} required>
              <option value="">Choose priority</option>
              {lookups.lookups.project_priority?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
            </select>
            <label>Business Team</label>
            <input name="business_team" value={form.business_team} onChange={handleChange} />
            <label>Business Lead</label>
            <input name="business_lead" value={form.business_lead} onChange={handleChange} />
            <label>Owner</label>
            <select name="owner_user_id" value={form.owner_user_id} onChange={handleChange} required>
              <option value="">Choose owner</option>
              {lookups.users.map(user => <option key={user.id} value={user.id}>{user.full_name}</option>)}
            </select>
            <label>Start Date</label>
            <input name="start_date" type="date" value={form.start_date} onChange={handleChange} />
            <label>End Date</label>
            <input name="end_date" type="date" value={form.end_date} onChange={handleChange} />
            {(message || error) && <div className={message ? 'form-success' : 'form-error'}>{message || error}</div>}
            <button type="submit" className="button primary">Create Project</button>
          </form>
        </div>
        <div className="table-panel">
          <form className="filter-row" onSubmit={applyFilters}>
            <input type="search" name="search" value={filters.search} onChange={handleFilterChange} placeholder="Search projects" />
            <select name="status" value={filters.status} onChange={handleFilterChange}>
              <option value="">All statuses</option>
              {lookups.lookups.project_status?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
            </select>
            <select name="priority" value={filters.priority} onChange={handleFilterChange}>
              <option value="">All priorities</option>
              {lookups.lookups.project_priority?.map(item => <option key={item.value} value={item.value}>{item.label}</option>)}
            </select>
            <select name="owner_user_id" value={filters.owner_user_id} onChange={handleFilterChange}>
              <option value="">All owners</option>
              {lookups.users.map(user => <option key={user.id} value={user.id}>{user.full_name}</option>)}
            </select>
            <button type="submit" className="button">Filter</button>
          </form>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Phase</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Owner</th>
                <th>Planned Hours</th>
              </tr>
            </thead>
            <tbody>
              {projects.map(project => (
                <tr key={project.id} onClick={() => navigate(`/projects/${project.id}`)}>
                  <td>{project.name}</td>
                  <td>{project.phase}</td>
                  <td>{project.status}</td>
                  <td>{project.priority}</td>
                  <td>{project.owner_name}</td>
                  <td>{project.total_planned_hours}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <Pagination page={filters.page} pageSize={filters.page_size} total={total} onPageChange={handlePageChange} />
        </div>
      </div>
    </div>
  );
}
