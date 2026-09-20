import React from 'react';
import { Container } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import MainLayout from '../../../components/Layout/MainLayout';
import CausacionView from '../views/CausacionView';

const CausacionPage = () => {
  const navigate = useNavigate();
  return (
    <MainLayout>
      <Container fluid>
        <CausacionView
          onVolver={() => navigate('/dashboard')}
          onVerHistorial={() => navigate('/causacion/historial')}
        />
      </Container>
    </MainLayout>
  );
};

export default CausacionPage;
