import { Poppins } from 'next/font/google';
import './globals.css';
import CSRFProvider from '@/components/CSRFProvider';

const poppins = Poppins({
  weight: ['400', '500', '600', '700'],
  subsets: ['latin'],
  display: 'swap',
});

export const metadata = {
  title: 'ChamaFunding - Crowdfunding Platform',
  description: 'Support campaigns and make a difference',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className={poppins.className}>
        <CSRFProvider>{children}</CSRFProvider>
      </body>
    </html>
  );
}

