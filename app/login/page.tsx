'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Zap, Eye, EyeOff } from 'lucide-react';

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Auth not implemented — placeholder
    window.location.href = '/dashboard';
  };

  return (
    <div
      className="min-h-screen flex items-center justify-center p-4"
      style={{ background: '#070A12' }}
    >
      <div className="w-full max-w-sm">
        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div
            className="flex h-12 w-12 items-center justify-center rounded-xl mb-3"
            style={{ background: 'linear-gradient(135deg, #38BDF8, #A78BFA)' }}
          >
            <Zap className="h-7 w-7 text-white" />
          </div>
          <h1 className="text-xl font-bold" style={{ color: '#F8FAFC' }}>
            Tradyos EdgePilot
          </h1>
          <p className="text-sm" style={{ color: '#64748B' }}>
            by Optrixis LLC
          </p>
        </div>

        {/* Card */}
        <div
          className="rounded-2xl border p-6"
          style={{ background: '#101828', borderColor: '#24314F' }}
        >
          <h2 className="text-lg font-semibold mb-1" style={{ color: '#F8FAFC' }}>
            Sign In
          </h2>
          <p className="text-xs mb-5" style={{ color: '#64748B' }}>
            Access your AI trading cockpit
          </p>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                Email Address
              </label>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="alex@example.com"
                className="w-full rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/40 transition-all"
                style={{
                  background: '#0D1220',
                  border: '1px solid #24314F',
                  color: '#F8FAFC',
                }}
                required
              />
            </div>

            <div>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                Password
              </label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full rounded-lg px-3 py-2.5 pr-10 text-sm outline-none focus:ring-2 focus:ring-sky-500/40 transition-all"
                  style={{
                    background: '#0D1220',
                    border: '1px solid #24314F',
                    color: '#F8FAFC',
                  }}
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-3 top-1/2 -translate-y-1/2"
                  style={{ color: '#64748B' }}
                >
                  {showPassword ? (
                    <EyeOff className="h-4 w-4" />
                  ) : (
                    <Eye className="h-4 w-4" />
                  )}
                </button>
              </div>
            </div>

            <button
              type="submit"
              className="w-full py-2.5 rounded-lg text-sm font-bold transition-all hover:opacity-90"
              style={{ background: '#38BDF8', color: '#070A12' }}
            >
              Sign In
            </button>
          </form>

          <div className="mt-4 text-center">
            <p className="text-xs" style={{ color: '#64748B' }}>
              Don&apos;t have an account?{' '}
              <Link href="/register" style={{ color: '#38BDF8' }} className="font-medium hover:underline">
                Create one
              </Link>
            </p>
          </div>
        </div>

        {/* Demo hint */}
        <div
          className="mt-4 rounded-xl border p-3 text-center"
          style={{ background: 'rgba(56, 189, 248, 0.05)', borderColor: 'rgba(56, 189, 248, 0.2)' }}
        >
          <p className="text-[10px]" style={{ color: '#64748B' }}>
            Demo mode — Authentication not implemented yet.{' '}
            <Link href="/dashboard" style={{ color: '#38BDF8' }}>
              Go to Dashboard →
            </Link>
          </p>
        </div>

        <p className="text-center text-[10px] mt-4" style={{ color: '#64748B' }}>
          This tool is for educational purposes only. Not financial advice.
        </p>
      </div>
    </div>
  );
}
