import React from 'react';
import AppNavbar from '../Navbar/AppNavbar';

const MainLayout = ({ children }) => {
  return (
    <div style={{ minHeight: '100vh', backgroundColor: '#f8f9fa' }}>
      <AppNavbar />
      <div>{children}</div>
    </div>
  );
};

export default MainLayout;
