import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';

const inter = Inter({
  subsets: ['latin'],
  variable: '--font-inter',
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'Tradyos EdgePilot — AI Trading Cockpit',
  description:
    'Tradyos EdgePilot by Optrixis LLC — AI-powered decision support for disciplined traders. Setups, risk management, and macro awareness in one cockpit.',
  keywords: ['trading', 'AI', 'setups', 'SMC', 'risk management', 'prop firm'],
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${inter.variable} h-full`}>
      <body className="h-full antialiased" style={{ background: '#070A12', color: '#F8FAFC' }}>
        {children}
      </body>
    </html>
  );
}
