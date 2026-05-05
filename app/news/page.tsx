'use client';

import { useState } from 'react';
import { AppLayout } from '@/components/layout/AppLayout';
import { mockNewsEvents } from '@/lib/mock-data';
import type { ImpactLevel } from '@/lib/mock-data';
import { Brain, Clock, AlertTriangle } from 'lucide-react';
import { StatusBadge } from '@/components/ui/StatusBadge';

const CURRENCIES = ['All', 'USD', 'EUR', 'GBP', 'JPY'];
const IMPACTS: ('All' | ImpactLevel)[] = ['All', 'Low', 'Medium', 'High'];

const impactColors: Record<ImpactLevel, string> = {
  High: '#EF4444',
  Medium: '#F59E0B',
  Low: '#22C55E',
};

const impactBg: Record<ImpactLevel, string> = {
  High: 'rgba(239, 68, 68, 0.1)',
  Medium: 'rgba(245, 158, 11, 0.1)',
  Low: 'rgba(34, 197, 94, 0.1)',
};

function ImpactDot({ impact }: { impact: ImpactLevel }) {
  return (
    <span
      className="inline-block h-2.5 w-2.5 rounded-full"
      style={{ background: impactColors[impact] }}
    />
  );
}

export default function NewsPage() {
  const [currency, setCurrency] = useState('All');
  const [impact, setImpact] = useState<'All' | ImpactLevel>('All');

  const filtered = mockNewsEvents.filter((e) => {
    const currencyMatch = currency === 'All' || e.currency === currency;
    const impactMatch = impact === 'All' || e.impact === impact;
    return currencyMatch && impactMatch;
  });

  return (
    <AppLayout
      title="News Center"
      subtitle="Economic calendar with real-time news risk assessment and post-news cooldown tracking."
    >
      {/* Filter Bar */}
      <div
        className="rounded-xl border p-3 mb-4 flex flex-wrap gap-3"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold" style={{ color: '#64748B' }}>
            Currency:
          </span>
          <div className="flex gap-1">
            {CURRENCIES.map((c) => (
              <button
                key={c}
                onClick={() => setCurrency(c)}
                className="px-2.5 py-1 rounded text-xs font-semibold transition-all"
                style={
                  currency === c
                    ? { background: '#38BDF8', color: '#070A12' }
                    : { background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }
                }
              >
                {c}
              </button>
            ))}
          </div>
        </div>

        <div className="flex items-center gap-2">
          <span className="text-xs font-semibold" style={{ color: '#64748B' }}>
            Impact:
          </span>
          <div className="flex gap-1">
            {IMPACTS.map((i) => (
              <button
                key={i}
                onClick={() => setImpact(i)}
                className="px-2.5 py-1 rounded text-xs font-semibold transition-all"
                style={
                  impact === i
                    ? { background: '#38BDF8', color: '#070A12' }
                    : { background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }
                }
              >
                {i}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Post-news cooldown */}
      <div
        className="rounded-xl border p-3 mb-4 flex items-center gap-3"
        style={{ background: 'rgba(245, 158, 11, 0.07)', borderColor: 'rgba(245, 158, 11, 0.3)' }}
      >
        <Clock className="h-5 w-5 shrink-0" style={{ color: '#F59E0B' }} />
        <div>
          <p className="text-sm font-semibold" style={{ color: '#F59E0B' }}>
            Post-News Cooldown Active
          </p>
          <p className="text-xs" style={{ color: '#94A3B8' }}>
            ECB Rate Decision released 105 minutes ago — EURUSD, EURGBP still in settling phase. Monitor volatility before trading.
          </p>
        </div>
        <div
          className="ml-auto text-sm font-bold px-3 py-1 rounded-lg"
          style={{ background: 'rgba(245, 158, 11, 0.15)', color: '#F59E0B' }}
        >
          Monitor
        </div>
      </div>

      {/* Economic Calendar Table */}
      <div
        className="rounded-xl border overflow-hidden mb-4"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <div
          className="px-4 py-3 border-b flex items-center justify-between"
          style={{ borderColor: '#24314F' }}
        >
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            Economic Calendar — Today
          </h3>
          <span className="text-xs" style={{ color: '#64748B' }}>
            {filtered.length} events
          </span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr style={{ borderBottom: `1px solid #24314F` }}>
                {['Time', 'Currency', 'Event', 'Impact', 'Forecast', 'Previous', 'Actual', 'Countdown', 'Status'].map((col) => (
                  <th
                    key={col}
                    className="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider"
                    style={{ color: '#64748B' }}
                  >
                    {col}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {filtered.map((event) => (
                <tr
                  key={event.id}
                  className="border-b hover:bg-white/[0.02] transition-colors"
                  style={{
                    borderColor: '#24314F',
                    background: event.minutesUntil > 0 && event.minutesUntil <= 15
                      ? impactBg[event.impact]
                      : 'transparent',
                  }}
                >
                  <td className="px-4 py-3">
                    <span className="text-sm font-semibold tabular-nums" style={{ color: '#F8FAFC' }}>
                      {event.time}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span
                      className="text-xs font-bold px-2 py-0.5 rounded"
                      style={{ background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }}
                    >
                      {event.currency}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm" style={{ color: '#F8FAFC' }}>
                      {event.event}
                    </span>
                    <div className="flex flex-wrap gap-1 mt-1">
                      {event.affectedAssets.slice(0, 3).map((a) => (
                        <span
                          key={a}
                          className="text-[9px] px-1.5 py-0.5 rounded"
                          style={{ background: '#0D1220', color: '#64748B' }}
                        >
                          {a}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-1.5">
                      <ImpactDot impact={event.impact} />
                      <span
                        className="text-xs font-semibold"
                        style={{ color: impactColors[event.impact] }}
                      >
                        {event.impact}
                      </span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-xs" style={{ color: '#94A3B8' }}>
                    {event.forecast ?? '—'}
                  </td>
                  <td className="px-4 py-3 text-xs" style={{ color: '#94A3B8' }}>
                    {event.previous ?? '—'}
                  </td>
                  <td className="px-4 py-3">
                    {event.actual ? (
                      <span className="text-xs font-semibold" style={{ color: '#22C55E' }}>
                        {event.actual}
                      </span>
                    ) : (
                      <span className="text-xs" style={{ color: '#64748B' }}>
                        Pending
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    {event.minutesUntil > 0 ? (
                      <span
                        className="text-xs font-semibold"
                        style={{
                          color: event.minutesUntil <= 15 ? '#EF4444' : event.minutesUntil <= 30 ? '#F59E0B' : '#94A3B8',
                        }}
                      >
                        {event.minutesUntil < 60
                          ? `${event.minutesUntil}m`
                          : `${Math.floor(event.minutesUntil / 60)}h ${event.minutesUntil % 60}m`}
                      </span>
                    ) : (
                      <span className="text-xs" style={{ color: '#64748B' }}>
                        {Math.abs(event.minutesUntil)}m ago
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    {event.status === 'released' ? (
                      <StatusBadge variant="Info" label="Released" size="sm" />
                    ) : event.minutesUntil <= 15 ? (
                      <StatusBadge variant="High" label="Imminent" size="sm" pulse />
                    ) : (
                      <StatusBadge variant="Low" label="Upcoming" size="sm" />
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* AI Macro Summary */}
      <div
        className="rounded-xl border p-4"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <div className="flex items-center gap-2 mb-3">
          <div
            className="flex h-7 w-7 items-center justify-center rounded-lg"
            style={{ background: 'rgba(167, 139, 250, 0.15)' }}
          >
            <Brain className="h-4 w-4" style={{ color: '#A78BFA' }} />
          </div>
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            AI Macro Summary
          </h3>
        </div>
        <div className="space-y-2 text-sm" style={{ color: '#94A3B8' }}>
          <p>
            <span className="font-semibold" style={{ color: '#F59E0B' }}>High Risk Window:</span>{' '}
            USD CPI at 15:30 is the primary risk event. Expect 50–100 pip moves on EUR/USD and 2–5 USD moves on Gold post-release.
          </p>
          <p>
            <span className="font-semibold" style={{ color: '#38BDF8' }}>Dollar Bias:</span>{' '}
            Markets pricing in CPI at 0.3% (cooler than previous 0.4%). A miss to the downside could strengthen EUR/USD and Gold. A hot print (above 0.4%) would likely strengthen DXY.
          </p>
          <p>
            <span className="font-semibold" style={{ color: '#A78BFA' }}>Recommended Approach:</span>{' '}
            Do not trade within 15 minutes of 15:30 release. Re-evaluate Gold and EUR/USD setups after 16:00 once volatility settles.
          </p>
          <div
            className="flex items-center gap-2 rounded-lg p-2.5 border mt-3"
            style={{ background: 'rgba(239, 68, 68, 0.06)', borderColor: 'rgba(239, 68, 68, 0.2)' }}
          >
            <AlertTriangle className="h-4 w-4 shrink-0" style={{ color: '#EF4444' }} />
            <p className="text-xs" style={{ color: '#EF4444' }}>
              FOMC Minutes at 20:00 could cause a second volatility spike. Plan your end-of-day risk accordingly.
            </p>
          </div>
        </div>
      </div>

      <p className="text-center text-[10px] mt-4" style={{ color: '#64748B' }}>
        This tool is for educational and analytical purposes only. It does not constitute financial advice.
      </p>
    </AppLayout>
  );
}
