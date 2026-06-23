import { useEffect, useMemo, useState } from 'react';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';
import { format, startOfWeek, subWeeks, addWeeks } from 'date-fns';

function formatWeekLabel(date) {
  const start = startOfWeek(date, { weekStartsOn: 1 });
  const end = addWeeks(start, 0);
  const rangeEnd = new Date(start);
  rangeEnd.setDate(start.getDate() + 4);
  return `${format(start, 'dd MMM yyyy')} - ${format(rangeEnd, 'dd MMM yyyy')}`;
}

function getWeekOptions() {
  const options = [];
  let current = startOfWeek(new Date(), { weekStartsOn: 1 });
  for (let i = 0; i < 12; i += 1) {
    options.push({ value: format(current, 'yyyy-MM-dd'), label: formatWeekLabel(current) });
    current = subWeeks(current, 1);
  }
  return options;
}

export default function WeeklyActualsPage() {
  const [week, setWeek] = useState(format(startOfWeek(new Date(), { weekStartsOn: 1 }), 'yyyy-MM-dd'));
  const [activities, setActivities] = useState([]);
  const [records, setRecords] = useState({});
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const weekOptions = useMemo(() => getWeekOptions(), []);

  useEffect(() => {
    api.get('/weekly-actuals', { params: { week_start_date: week } })
      .then(r => {
        const loaded = r.data.weekly_actuals || [];
        setActivities(loaded);
        const map = {};
        loaded.forEach(item => {
          map[item.activity_id] = { actual_hours: item.actual_hours, notes: item.notes || '', id: item.id };
        });
        setRecords(map);
      })
      .catch(() => setError('Unable to load actuals.'));
  }, [week]);

  const handleChange = (activityId, field, value) => {
    setRecords(prev => ({ ...prev, [activityId]: { ...prev[activityId], [field]: value } }));
  };

  const handleSave = async () => {
    setMessage('');
    setError('');
    const payload = {
      records: activities.map(item => ({
        activity_id: item.activity_id,
        week_start_date: week,
        actual_hours: Number(records[item.activity_id]?.actual_hours || 0),
        notes: records[item.activity_id]?.notes || ''
      }))
    };
    try {
      await api.post('/weekly-actuals', payload);
      setMessage('Weekly actuals saved successfully.');
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to save weekly actuals.');
    }
  };

  return (
    <div>
      <SectionHeader title="Weekly Actuals" subtitle="Log actual hours for your assigned activities." />
      <div className="form-panel">
        <label>Select Week</label>
        <select value={week} onChange={e => setWeek(e.target.value)}>
          {weekOptions.map(option => <option key={option.value} value={option.value}>{option.label}</option>)}
        </select>
      </div>
      {error && <div className="form-error">{error}</div>}
      {message && <div className="form-success">{message}</div>}
      <div className="table-panel">
        <table>
          <thead>
            <tr>
              <th>Project</th>
              <th>Activity</th>
              <th>Planned Hours</th>
              <th>Actual Hours</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            {activities.map(activity => (
              <tr key={activity.id}>
                <td>{activity.project_name}</td>
                <td>{activity.activity_name}</td>
                <td>{activity.weekly_planned_hours}</td>
                <td><input type="number" step="0.25" value={records[activity.activity_id]?.actual_hours ?? activity.actual_hours ?? 0} onChange={e => handleChange(activity.activity_id, 'actual_hours', e.target.value)} /></td>
                <td><input type="text" value={records[activity.activity_id]?.notes ?? ''} onChange={e => handleChange(activity.activity_id, 'notes', e.target.value)} /></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <button className="button primary" onClick={handleSave}>Save Actuals</button>
    </div>
  );
}
