import { useState } from 'react';
import api from '../services/api.js';
import SectionHeader from '../components/SectionHeader.jsx';

export default function PasswordResetRequestPage() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const handleSubmit = async e => {
    e.preventDefault();
    setMessage('');
    setError('');
    try {
      const response = await api.post('/password-reset-request', { email });
      setMessage(response.data.message);
    } catch (err) {
      setError(err.response?.data?.message || 'Unable to send reset request.');
    }
  };

  return (
    <div className="auth-page">
      <form className="auth-card" onSubmit={handleSubmit}>
        <h1>Reset Password</h1>
        <label>Email</label>
        <input type="email" value={email} onChange={e => setEmail(e.target.value)} required />
        {message && <div className="form-success">{message}</div>}
        {error && <div className="form-error">{error}</div>}
        <button type="submit" className="button primary">Request Password Reset</button>
      </form>
    </div>
  );
}
