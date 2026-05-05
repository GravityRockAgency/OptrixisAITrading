'use client';

import { Globe, Clock, Activity, Waves } from 'lucide-react';

interface SessionInfo {
  name: string;
  status: 'Active' | 'Closed' | 'Pre-Open';
  times: string;
}

const sessions: SessionInfo[] = [
  { name: 'New York', status: 'Active', times: '13:00 – 22:00 UTC' },
  { name: 'London', status: 'Closed', times: '07:00 – 16:00 UTC' },
  { name: 'Tokyo', status: 'Closed', times: '00:00 – 09:00 UTC' },
  { name: 'Sydney', status: 'Closed', times: '21:00 – 06:00 UTC' },
];

const statusConfig = {
  Active: { color: '#22C55E', bg: 'rgba(34, 197, 94, 0.12)', pulse: true },
  Closed: { color: '#64748B', bg: 'rgba(100, 116, 139, 0.08)', pulse: false },
  'Pre-Open': { color: '#F59E0B', bg: 'rgba(245, 158, 11, 0.08)', pulse: false },
};

export function SessionFilter() {
  return (
    <div
      className="rounded-xl border flex flex-col"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      {/* Header */}
      <div
        className="flex items-center gap-2 px-4 py-3 border-b"
        style={{ borderColor: '#24314F' }}
      >
        <Globe className="h-4 w-4" style={{ color: '#38BDF8' }} />
        <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
          Session Filter
        </h3>
      </div>

      <div className="p-4 space-y-3">
        {/* Sessions grid */}
        <div className="grid grid-cols-2 gap-2">
          {sessions.map((session) => {
            const cfg = statusConfig[session.status];
            return (
              <div
                key={session.name}
                className="rounded-lg p-2.5 border"
                style={{ background: cfg.bg, borderColor: `${cfg.color}30` }}
              >
                <div className="flex items-center gap-1.5 mb-1">
                  <span
                    className={`h-2 w-2 rounded-full ${cfg.pulse ? 'animate-pulse' : ''}`}
                    style={{ background: cfg.color }}
                  />
                  <span className="text-xs font-semibold" style={{ color: cfg.color }}>
                    {session.status}
                  </span>
                </div>
                <p className="text-xs font-bold" style={{ color: '#F8FAFC' }}>
                  {session.name}
                </p>
                <p className="text-[10px]" style={{ color: '#64748B' }}>
                  {session.times}
                </p>
              </div>
            );
          })}
        </div>

        {/* Market conditions */}
        <div
          className="rounded-lg p-3 border space-y-2"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <div className="flex items-center gap-2">
            <Clock className="h-3.5 w-3.5" style={{ color: '#38BDF8' }} />
            <span className="text-xs" style={{ color: '#94A3B8' }}>
              Kill Zone:
            </span>
            <span className="text-xs font-semibold" style={{ color: '#F59E0B' }}>
              15:30 – 17:00 UTC
            </span>
          </div>
          <div className="flex items-center gap-2">
            <Activity className="h-3.5 w-3.5" style={{ color: '#38BDF8' }} />
            <span className="text-xs" style={{ color: '#94A3B8' }}>
              Volatility:
            </span>
            <span className="text-xs font-semibold" style={{ color: '#EF4444' }}>
              High
            </span>
          </div>
          <div className="flex items-center gap-2">
            <Waves className="h-3.5 w-3.5" style={{ color: '#38BDF8' }} />
            <span className="text-xs" style={{ color: '#94A3B8' }}>
              Spread:
            </span>
            <span className="text-xs font-semibold" style={{ color: '#22C55E' }}>
              Normal
            </span>
          </div>
          <div className="flex items-center gap-2">
            <Globe className="h-3.5 w-3.5" style={{ color: '#38BDF8' }} />
            <span className="text-xs" style={{ color: '#94A3B8' }}>
              Liquidity:
            </span>
            <span className="text-xs font-semibold" style={{ color: '#22C55E' }}>
              Good
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}
