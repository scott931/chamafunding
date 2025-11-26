/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  output: 'standalone',
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  },
  async rewrites() {
    // Use internal API URL for server-side rewrites (same container)
    // For client-side, use relative /api which will be proxied
    const internalApiUrl = process.env.NEXT_PUBLIC_API_URL_INTERNAL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    const baseUrl = internalApiUrl.replace('/api', ''); // Remove /api to get base URL
    return [
      {
        source: '/api/:path*',
        destination: `${internalApiUrl}/:path*`,
      },
      {
        // Health check endpoint - proxy to Laravel
        source: '/up',
        destination: `${baseUrl}/up`,
      },
    ];
  },
  async headers() {
    return [
      {
        // Apply cache prevention headers to all routes
        source: '/:path*',
        headers: [
          {
            key: 'Cache-Control',
            value: 'no-cache, no-store, must-revalidate, max-age=0, private',
          },
          {
            key: 'Pragma',
            value: 'no-cache',
          },
          {
            key: 'Expires',
            value: '0',
          },
          {
            key: 'X-Accel-Expires',
            value: '0',
          },
        ],
      },
    ];
  },
};

module.exports = nextConfig;

