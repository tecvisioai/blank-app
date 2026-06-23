import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';

export default function PasswordResetPage() {
  const { token } = useParams();
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const navigate = useNavigate();

  const handleSubmit = async e => {
    e.preventDefault();
    setMessage('');
    setError('');
    try {
      await api.post('/password-reset', { token, password, confirm_password: confirmPassword });
      setMessage('Password reset successfully. Please login again.');
      setTimeout(() => navigate('/login'), 2000);
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to reset password.');
    }
  };

  return (
    <div className="auth-page">
      <form className="auth-card" onSubmit={handleSubmit}>
        <h1>Set New Password</h1>
        <label>New Password</label>
        <input type="password" value={password} onChange={e => setPassword(e.target.value)} required minLength={6} />
        <label>Confirm Password</label>
        <input type="password" value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} required minLength={6} />
        {message && <div className="form-success">{message}</div>}
        {error && <div className="form-error">{error}</div>}
        <button type="submit" className="button primary">Reset Password</button>
      </form>
    </div>
  );
}
