import React from 'react';
import AppNavbar from '../Navbar/AppNavbar';
import './MainLayout.css';

const MainLayout = ({ children }) => {
  return (
    <div className="main-layout">
      <AppNavbar />
      <div className="main-content">{children}</div>
    </div>
  );
};

export default MainLayout;
