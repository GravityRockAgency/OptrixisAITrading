'use client';

import { useState } from 'react';
import Link from 'next/link';
import { Zap } from 'lucide-react';

export default function RegisterPage() {
  const [form, setForm] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Auth not implemented — redirect to dashboard
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
            Create Account
          </h2>
          <p className="text-xs mb-5" style={{ color: '#64748B' }}>
            Join Tradyos EdgePilot — AI-powered trading discipline
          </p>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                Full Name
              </label>
              <input
                type="text"
                value={form.name}
                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                placeholder="Alex Morgan"
                className="w-full rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/40 transition-all"
                style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                required
              />
            </div>

            <div>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                Email Address
              </label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                placeholder="alex@example.com"
                className="w-full rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/40 transition-all"
                style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                required
              />
            </div>

            <div>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                Password
              </label>
              <input
                type="password"
                value={form.password}
                onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                placeholder="••••••••"
                className="w-full rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/40 transition-all"
                style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                required
                minLength={8}
              />
            </div>

            <div>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                Confirm Password
              </label>
              <input
                type="password"
                value={form.confirmPassword}
                onChange={(e) => setForm((f) => ({ ...f, confirmPassword: e.target.value }))}
                placeholder="••••••••"
                className="w-full rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/40 transition-all"
                style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                required
              />
            </div>

            <button
              type="submit"
              className="w-full py-2.5 rounded-lg text-sm font-bold transition-all hover:opacity-90"
              style={{ background: '#38BDF8', color: '#070A12' }}
            >
              Create Account
            </button>
          </form>

          <div className="mt-4 text-center">
            <p className="text-xs" style={{ color: '#64748B' }}>
              Already have an account?{' '}
              <Link href="/login" style={{ color: '#38BDF8' }} className="font-medium hover:underline">
                Sign in
              </Link>
            </p>
          </div>
        </div>

        <p className="text-center text-[10px] mt-4" style={{ color: '#64748B' }}>
          This tool is for educational purposes only. Not financial advice.
        </p>
      </div>
    </div>
  );
}
