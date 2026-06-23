import { createContext, useContext, useEffect, useState } from 'react';
import api from '../services/api.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('project_tracker_token'));
  const [loading, setLoading] = useState(Boolean(token));

  useEffect(() => {
    if (!token) {
      setLoading(false);
      return;
    }
    api.setToken(token);
    api.get('/me')
      .then(response => {
        setUser(response.data.user);
      })
      .catch(() => {
        logout();
      })
      .finally(() => setLoading(false));
  }, [token]);

  const login = async (email, password) => {
    const response = await api.post('/login', { email, password });
    const result = response.data;
    localStorage.setItem('project_tracker_token', result.token);
    api.setToken(result.token);
    setUser(result.user);
    setToken(result.token);
    return result;
  };

  const logout = () => {
    localStorage.removeItem('project_tracker_token');
    api.setToken(null);
    setUser(null);
    setToken(null);
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, token }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
