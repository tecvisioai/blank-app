import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';

const initialActivity = { name: '', owner_user_id: '', start_date: '', end_date: '', weekly_planned_hours: '' };

export default function ProjectDetailPage() {
  const { id } = useParams();
  const [project, setProject] = useState(null);
  const [lookups, setLookups] = useState({ users: [] });
  const [activity, setActivity] = useState(initialActivity);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const loadProject = () => {
    api.get(`/projects/${id}`).then(r => setProject(r.data.project)).catch(() => setError('Unable to load project.'));
  };

  useEffect(() => {
    api.get('/lookups').then(r => setLookups(r.data)).catch(() => setError('Unable to load lookup data.'));
    loadProject();
  }, [id]);

  const handleChange = e => {
    const { name, value } = e.target;
    setActivity(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async e => {
    e.preventDefault();
    setMessage('');
    setError('');
    try {
      await api.post('/activities', { ...activity, project_id: id });
      setMessage('Activity added.');
      setActivity(initialActivity);
      loadProject();
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to save activity.');
    }
  };

  return (
    <div>
      <SectionHeader title={project?.name || 'Project'} subtitle="Project details and activity planning." />
      {project && (
        <div className="project-detail">
          <div className="panel">
            <h3>Project Summary</h3>
            <p><strong>Phase:</strong> {project.phase}</p>
            <p><strong>Status:</strong> {project.status}</p>
            <p><strong>Priority:</strong> {project.priority}</p>
            <p><strong>Owner:</strong> {project.owner_name}</p>
            <p><strong>Business Team:</strong> {project.business_team}</p>
            <p><strong>Business Lead:</strong> {project.business_lead}</p>
            <p><strong>Planned Hours:</strong> {project.total_planned_hours}</p>
          </div>
          <div className="panel">
            <h3>Add Activity</h3>
            <form onSubmit={handleSubmit}>
              <label>Activity Name</label>
              <input name="name" value={activity.name} onChange={handleChange} required />
              <label>Owner</label>
              <select name="owner_user_id" value={activity.owner_user_id} onChange={handleChange} required>
                <option value="">Choose owner</option>
                {lookups.users.map(user => <option key={user.id} value={user.id}>{user.full_name}</option>)}
              </select>
              <label>Start Date</label>
              <input type="date" name="start_date" value={activity.start_date} onChange={handleChange} />
              <label>End Date</label>
              <input type="date" name="end_date" value={activity.end_date} onChange={handleChange} />
              <label>Weekly Planned Hours</label>
              <input type="number" name="weekly_planned_hours" step="0.25" value={activity.weekly_planned_hours} onChange={handleChange} required />
              {(message || error) && <div className={message ? 'form-success' : 'form-error'}>{message || error}</div>}
              <button type="submit" className="button primary">Add Activity</button>
            </form>
          </div>
        </div>
      )}
      <div className="table-panel">
        <h2>Activities</h2>
        <table>
          <thead>
            <tr><th>Name</th><th>Owner</th><th>Start Date</th><th>End Date</th><th>Planned Hours</th></tr>
          </thead>
          <tbody>
            {project?.activities?.map(item => (
              <tr key={item.id}>
                <td>{item.name}</td>
                <td>{item.owner_name}</td>
                <td>{item.start_date}</td>
                <td>{item.end_date}</td>
                <td>{item.weekly_planned_hours}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
