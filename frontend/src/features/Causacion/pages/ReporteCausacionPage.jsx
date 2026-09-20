import React from 'react';
import { Container } from 'react-bootstrap';
import { useNavigate } from 'react-router-dom';
import MainLayout from '../../../components/Layout/MainLayout';
import ReporteCausacionView from '../views/ReporteCausacionView';

const ReporteCausacionPage = () => {
  const navigate = useNavigate();
  return (
    <MainLayout>
      <Container fluid>
        <ReporteCausacionView onVolver={() => navigate('/dashboard')} />
      </Container>
    </MainLayout>
  );
};

export default ReporteCausacionPage;
