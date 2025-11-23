import { NextResponse } from 'next/server';
import apiClient from '@/lib/api/client';

export async function GET() {
  try {
    // Fetch CSRF token from Laravel
    const response = await apiClient.get('/sanctum/csrf-cookie');
    const csrfToken = response.headers['x-csrf-token'] || '';
    
    return NextResponse.json({ csrfToken });
  } catch (error) {
    return NextResponse.json({ csrfToken: '' }, { status: 200 });
  }
}

