'use client';

import { Newspaper, Clock, AlertTriangle } from 'lucide-react';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { mockNewsEvents } from '@/lib/mock-data';
import type { ImpactLevel } from '@/lib/mock-data';

const impactColors: Record<ImpactLevel, string> = {
  High: '#EF4444',
  Medium: '#F59E0B',
  Low: '#22C55E',
};

export function NewsGuard() {
  const upcoming = mockNewsEvents.filter((e) => e.minutesUntil > 0);
  const nextHigh = upcoming.find((e) => e.impact === 'High');
  const currentRisk = nextHigh && nextHigh.minutesUntil <= 15 ? 'High' : nextHigh && nextHigh.minutesUntil <= 30 ? 'Medium' : 'Low';

  return (
    <div
      className="rounded-xl border flex flex-col"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      {/* Header */}
      <div
        className="flex items-center justify-between px-4 py-3 border-b"
        style={{ borderColor: '#24314F' }}
      >
        <div className="flex items-center gap-2">
          <Newspaper className="h-4 w-4" style={{ color: '#38BDF8' }} />
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            News Guard
          </h3>
        </div>
        <StatusBadge variant={currentRisk} label={`${currentRisk} Risk`} size="sm" />
      </div>

      <div className="p-4 space-y-3">
        {/* Countdown */}
        {nextHigh && (
          <div
            className="rounded-lg p-3 border flex items-center gap-3"
            style={{
              background: nextHigh.minutesUntil <= 15 ? 'rgba(239, 68, 68, 0.08)' : 'rgba(245, 158, 11, 0.08)',
              borderColor: nextHigh.minutesUntil <= 15 ? 'rgba(239, 68, 68, 0.25)' : 'rgba(245, 158, 11, 0.25)',
            }}
          >
            {nextHigh.minutesUntil <= 15 ? (
              <AlertTriangle className="h-5 w-5 shrink-0" style={{ color: '#EF4444' }} />
            ) : (
              <Clock className="h-5 w-5 shrink-0" style={{ color: '#F59E0B' }} />
            )}
            <div>
              <p className="text-xs font-semibold" style={{ color: '#F8FAFC' }}>
                {nextHigh.currency} {nextHigh.event}
              </p>
              <p
                className="text-xs"
                style={{ color: nextHigh.minutesUntil <= 15 ? '#EF4444' : '#F59E0B' }}
              >
                in {nextHigh.minutesUntil} minutes · {nextHigh.time} UTC
              </p>
            </div>
          </div>
        )}

        {/* Event list */}
        <div className="space-y-1.5">
          {upcoming.slice(0, 4).map((event) => (
            <div
              key={event.id}
              className="flex items-center gap-3 text-xs py-1.5 border-b"
              style={{ borderColor: '#24314F' }}
            >
              <span
                className="w-12 shrink-0 font-medium"
                style={{ color: '#94A3B8' }}
              >
                {event.time}
              </span>
              <span
                className="h-2 w-2 rounded-full shrink-0"
                style={{ background: impactColors[event.impact] }}
              />
              <span className="flex-1 truncate" style={{ color: '#F8FAFC' }}>
                {event.currency} · {event.event}
              </span>
              <span
                className="text-[10px] font-semibold"
                style={{ color: impactColors[event.impact] }}
              >
                {event.impact}
              </span>
            </div>
          ))}
        </div>

        {/* Affected assets */}
        {nextHigh && (
          <div>
            <p className="text-[10px] font-semibold uppercase tracking-wider mb-1.5" style={{ color: '#64748B' }}>
              Affected Assets
            </p>
            <div className="flex flex-wrap gap-1.5">
              {nextHigh.affectedAssets.map((asset) => (
                <span
                  key={asset}
                  className="text-[10px] font-semibold px-2 py-0.5 rounded"
                  style={{ background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }}
                >
                  {asset}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
