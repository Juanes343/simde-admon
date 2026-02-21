import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  define: {
    'process.env': {}
  },
  base: '/simde-admon/frontend/build/', // Manteniendo la ruta del servidor
  build: {
    outDir: 'build', // Donde CRA ponía el resultado, para no cambiar los scripts del servidor
    assetsDir: 'assets', // Cambiando 'static' por 'assets' como pidió el usuario
  },
  server: {
    port: 3000,
  }
});
