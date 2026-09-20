import { useState, useEffect, useCallback } from 'react';
import cotizacionService from '../services/cotizacionService';

const useCotizaciones = () => {
  const [cotizaciones, setCotizaciones] = useState([]);
  const [loading, setLoading]           = useState(true);
  const [pagination, setPagination]     = useState({
    currentPage: 1,
    lastPage:    1,
    total:       0,
    perPage:     15,
  });
  const [filters, setFilters] = useState({});

  const fetchCotizaciones = useCallback(async (page = 1) => {
    try {
      setLoading(true);
      const response = await cotizacionService.getAll({
        ...filters,
        page,
        per_page: pagination.perPage,
      });

      setCotizaciones(response.data || []);
      setPagination({
        currentPage: response.current_page || 1,
        lastPage:    response.last_page    || 1,
        total:       response.total        || 0,
        perPage:     response.per_page     || 15,
      });
    } catch (error) {
      console.error('Error al obtener cotizaciones:', error);
      setCotizaciones([]);
    } finally {
      setLoading(false);
    }
  }, [filters, pagination.perPage]);

  useEffect(() => {
    fetchCotizaciones(1);
  }, [filters]);

  const setPage = (page) => fetchCotizaciones(page);

  const updateFilters = (newFilters) => setFilters((prev) => ({ ...prev, ...newFilters }));

  const refetch = () => fetchCotizaciones(pagination.currentPage);

  return { cotizaciones, loading, pagination, setPage, updateFilters, refetch };
};

export default useCotizaciones;
