import { useState, useEffect, useCallback } from 'react';
import ordenServicioService from '../services/ordenServicioService';

const useOrdenesServicio = () => {
  const [ordenes, setOrdenes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [pagination, setPagination] = useState({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 15,
  });
  const [filters, setFilters] = useState({});

  const fetchOrdenes = useCallback(async (page = 1) => {
    try {
      setLoading(true);
      const response = await ordenServicioService.getAll({
        ...filters,
        page,
        per_page: pagination.perPage,
      });
      
      setOrdenes(response.data || []);
      setPagination({
        currentPage: response.current_page || 1,
        lastPage: response.last_page || 1,
        total: response.total || 0,
        perPage: response.per_page || 15,
      });
    } catch (error) {
      console.error('Error al obtener órdenes:', error);
      setOrdenes([]);
    } finally {
      setLoading(false);
    }
  }, [filters, pagination.perPage]);

  useEffect(() => {
    fetchOrdenes(1);
  }, [filters]);

  const setPage = (page) => {
    fetchOrdenes(page);
  };

  const updateFilters = (newFilters) => {
    setFilters({ ...filters, ...newFilters });
  };

  const refetch = () => {
    fetchOrdenes(pagination.currentPage);
  };

  return {
    ordenes,
    loading,
    pagination,
    setPage,
    updateFilters,
    refetch,
  };
};

export default useOrdenesServicio;
