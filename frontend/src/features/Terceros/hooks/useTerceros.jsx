import { useState, useEffect } from 'react';
import { terceroService } from '../services/terceroService';

export const useTerceros = (initialFilters = {}) => {
  const [terceros, setTerceros] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState({
    currentPage: 1,
    lastPage: 1,
    perPage: 15,
    total: 0,
  });
  const [filters, setFilters] = useState(initialFilters);

  const fetchTerceros = async () => {
    setLoading(true);
    setError(null);

    try {
      const params = {
        ...filters,
        page: pagination.currentPage,
        per_page: pagination.perPage,
      };

      const response = await terceroService.getAll(params);
      
      setTerceros(response.data);
      setPagination({
        currentPage: response.current_page,
        lastPage: response.last_page,
        perPage: response.per_page,
        total: response.total,
      });
    } catch (err) {
      setError(err.response?.data?.message || 'Error al cargar terceros');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTerceros();
  }, [pagination.currentPage, filters]);

  const setPage = (page) => {
    setPagination((prev) => ({ ...prev, currentPage: page }));
  };

  const updateFilters = (newFilters) => {
    setFilters(newFilters);
    setPagination((prev) => ({ ...prev, currentPage: 1 }));
  };

  return {
    terceros,
    loading,
    error,
    pagination,
    setPage,
    updateFilters,
    refetch: fetchTerceros,
  };
};
