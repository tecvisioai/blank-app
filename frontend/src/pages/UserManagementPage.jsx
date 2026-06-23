import { useEffect, useState } from 'react';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';
import ConfirmButton from '../components/ConfirmButton.jsx';

const initialForm = { email: '', full_name: '', role: 'User', groups: [], password: '' };

export default function UserManagementPage() {
  const [users, setUsers] = useState([]);
  const [form, setForm] = useState(initialForm);
  const [isEditing, setIsEditing] = useState(false);
  const [currentId, setCurrentId] = useState(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const loadUsers = () => {
    api.get('/users').then(r => setUsers(r.data.users)).catch(() => setError('Unable to load users.'));
  };

  useEffect(() => { loadUsers(); }, []);

  const handleChange = e => {
    const { name, value, type, checked } = e.target;
    if (name === 'groups') {
      setForm(prev => {
        const existing = new Set(prev.groups);
        if (checked) existing.add(value); else existing.delete(value);
        return { ...prev, groups: Array.from(existing) };
      });
      return;
    }
    setForm(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async e => {
    e.preventDefault();
    setMessage('');
    setError('');
    try {
      if (isEditing) {
        await api.put(`/users/${currentId}`, form);
        setMessage('User updated successfully.');
      } else {
        await api.post('/users', form);
        setMessage('User created successfully.');
      }
      setForm(initialForm);
      setIsEditing(false);
      setCurrentId(null);
      loadUsers();
    } catch (err) {
      setError(err.response?.data?.message || 'Save failed.');
    }
  };

  const editUser = user => {
    setForm({ email: user.email, full_name: user.full_name, role: user.role, groups: user.groups ? user.groups.split(',') : [], password: '' });
    setIsEditing(true);
    setCurrentId(user.id);
    setMessage('');
    setError('');
  };

  const deactivate = async id => {
    try {
      await api.delete(`/users/${id}`);
      setMessage('User deactivated.');
      loadUsers();
    } catch (err) {
      setError('Could not deactivate user.');
    }
  };

  return (
    <div>
      <SectionHeader title="User Management" subtitle="Create, edit, and manage user accounts." />
      <div className="form-panel">
        <form onSubmit={handleSubmit}>
          <h2>{isEditing ? 'Edit User' : 'Add User'}</h2>
          <label>Email</label>
          <input name="email" type="email" value={form.email} onChange={handleChange} required />
          <label>Full Name</label>
          <input name="full_name" value={form.full_name} onChange={handleChange} required />
          <label>Role</label>
          <select name="role" value={form.role} onChange={handleChange}>
            <option>Admin</option>
            <option>Manager</option>
            <option>User</option>
          </select>
          <label>Groups</label>
          <div className="checkbox-group">
            <label><input type="checkbox" name="groups" value="AAIT" checked={form.groups.includes('AAIT')} onChange={handleChange} /> AAIT</label>
            <label><input type="checkbox" name="groups" value="BAT" checked={form.groups.includes('BAT')} onChange={handleChange} /> BAT</label>
          </div>
          <label>Password {isEditing ? '(leave blank to keep current)' : ''}</label>
          <input name="password" type="password" value={form.password} onChange={handleChange} minLength={isEditing ? 0 : 6} />
          {(message || error) && <div className={message ? 'form-success' : 'form-error'}>{message || error}</div>}
          <button type="submit" className="button primary">{isEditing ? 'Save Changes' : 'Create User'}</button>
          {isEditing && <button type="button" className="button secondary" onClick={() => { setForm(initialForm); setIsEditing(false); setCurrentId(null); setMessage(''); setError(''); }}>Cancel</button>}
        </form>
      </div>
      <div className="table-panel">
        <table>
          <thead>
            <tr><th>Name</th><th>Email</th><th>Role</th><th>Groups</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            {users.map(user => (
              <tr key={user.id}>
                <td>{user.full_name}</td>
                <td>{user.email}</td>
                <td>{user.role}</td>
                <td>{user.groups}</td>
                <td>{user.active ? 'Active' : 'Inactive'}</td>
                <td>
                  <button className="button" onClick={() => editUser(user)}>Edit</button>
                  <ConfirmButton onConfirm={() => deactivate(user.id)} label="Deactivate" confirmText="Deactivate this user?" />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
