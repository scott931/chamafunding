'use client';

import { useEffect } from 'react';
import { usePathname } from 'next/navigation';

export default function CSRFProvider({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();

  useEffect(() => {
    // Fetch CSRF token from Laravel
    const fetchCSRF = async () => {
      try {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'}/sanctum/csrf-cookie`, {
          method: 'GET',
          credentials: 'include',
        });
        
        // CSRF token is set as a cookie by Laravel Sanctum
        // The API client will handle it automatically
      } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
      }
    };

    // Fetch CSRF token on mount and route changes
    fetchCSRF();
  }, [pathname]);

  return <>{children}</>;
}

