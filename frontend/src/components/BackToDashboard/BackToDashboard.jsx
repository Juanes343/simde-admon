import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from 'react-bootstrap';
import './BackToDashboard.css';

const BackToDashboard = ({ text = 'Volver al Dashboard' }) => {
  const navigate = useNavigate();

  return (
    <div className="back-to-dashboard">
      <Button 
        variant="outline-primary" 
        className="back-button"
        onClick={() => navigate('/dashboard')}
      >
        <i className="fas fa-home me-2"></i>
        {text}
      </Button>
    </div>
  );
};

export default BackToDashboard;
