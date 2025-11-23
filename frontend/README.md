# ChamaFunding Frontend (Next.js)

This is the Next.js frontend for the ChamaFunding platform.

## Getting Started

1. Install dependencies:
```bash
npm install
```

2. Set up environment variables:
Create a `.env.local` file:
```
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

3. Run the development server:
```bash
npm run dev
```

4. Open [http://localhost:3000](http://localhost:3000) in your browser.

## Building for Production

```bash
npm run build
npm start
```

## Project Structure

- `app/` - Next.js app directory with pages and layouts
- `components/` - React components
- `lib/` - Utility functions and API clients
- `public/` - Static assets

## API Integration

The frontend communicates with the Laravel backend API. All API calls are handled through the API client in `lib/api/client.ts`.

