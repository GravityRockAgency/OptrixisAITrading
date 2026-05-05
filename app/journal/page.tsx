'use client';

import { useState } from 'react';
import { AppLayout } from '@/components/layout/AppLayout';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { mockTrades } from '@/lib/mock-data';
import type { Decision, TradeResult } from '@/lib/mock-data';
import { TrendingUp, TrendingDown, Minus, Filter } from 'lucide-react';

const ALL_ASSETS = ['All', 'EURUSD', 'XAUUSD', 'NQ', 'GBPUSD', 'USOIL'];
const ALL_DECISIONS: ('All' | Decision)[] = ['All', 'GO', 'WAIT', 'NO_TRADE'];
const ALL_RESULTS: ('All' | TradeResult)[] = ['All', 'Win', 'Loss', 'Breakeven', 'Skipped', 'Avoided'];

export default function JournalPage() {
  const [asset, setAsset] = useState('All');
  const [decision, setDecision] = useState<'All' | Decision>('All');
  const [result, setResult] = useState<'All' | TradeResult>('All');

  const filtered = mockTrades.filter((t) => {
    return (
      (asset === 'All' || t.asset === asset) &&
      (decision === 'All' || t.aiDecision === decision) &&
      (result === 'All' || t.result === result)
    );
  });

  const wins = filtered.filter((t) => t.result === 'Win').length;
  const losses = filtered.filter((t) => t.result === 'Loss').length;
  const taken = wins + losses;
  const winRate = taken > 0 ? Math.round((wins / taken) * 100) : 0;
  const totalR = filtered.reduce((sum, t) => sum + (t.rMultiple ?? 0), 0);

  return (
    <AppLayout
      title="Trading Journal"
      subtitle="Complete trade history with AI analysis and performance review."
    >
      {/* Summary cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        {[
          { label: 'Total Entries', value: filtered.length.toString(), sub: 'Filtered' },
          { label: 'Win Rate', value: `${winRate}%`, sub: `${wins}W / ${losses}L` },
          {
            label: 'Total R',
            value: `${totalR >= 0 ? '+' : ''}${totalR.toFixed(1)}R`,
            sub: 'Risk multiples',
          },
          { label: 'Avg Score', value: `${Math.round(filtered.reduce((s, t) => s + t.score, 0) / Math.max(filtered.length, 1))}/100`, sub: 'AI quality' },
        ].map(({ label, value, sub }) => (
          <div
            key={label}
            className="rounded-xl border p-4"
            style={{ background: '#101828', borderColor: '#24314F' }}
          >
            <p className="text-[10px] font-semibold uppercase tracking-wider mb-1" style={{ color: '#64748B' }}>
              {label}
            </p>
            <p className="text-2xl font-black" style={{ color: '#F8FAFC' }}>
              {value}
            </p>
            <p className="text-xs" style={{ color: '#94A3B8' }}>
              {sub}
            </p>
          </div>
        ))}
      </div>

      {/* Filters */}
      <div
        className="rounded-xl border p-3 mb-4 flex flex-wrap gap-3 items-center"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <Filter className="h-4 w-4" style={{ color: '#64748B' }} />

        <div className="flex items-center gap-2">
          <span className="text-xs" style={{ color: '#64748B' }}>Asset:</span>
          <div className="flex gap-1 flex-wrap">
            {ALL_ASSETS.map((a) => (
              <button
                key={a}
                onClick={() => setAsset(a)}
                className="px-2 py-0.5 rounded text-xs font-semibold transition-all"
                style={asset === a ? { background: '#38BDF8', color: '#070A12' } : { background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }}
              >
                {a}
              </button>
            ))}
          </div>
        </div>

        <div className="flex items-center gap-2">
          <span className="text-xs" style={{ color: '#64748B' }}>Decision:</span>
          <div className="flex gap-1">
            {ALL_DECISIONS.map((d) => (
              <button
                key={d}
                onClick={() => setDecision(d)}
                className="px-2 py-0.5 rounded text-xs font-semibold transition-all"
                style={decision === d ? { background: '#38BDF8', color: '#070A12' } : { background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }}
              >
                {d === 'NO_TRADE' ? 'NO' : d}
              </button>
            ))}
          </div>
        </div>

        <div className="flex items-center gap-2">
          <span className="text-xs" style={{ color: '#64748B' }}>Result:</span>
          <div className="flex gap-1 flex-wrap">
            {ALL_RESULTS.map((r) => (
              <button
                key={r}
                onClick={() => setResult(r)}
                className="px-2 py-0.5 rounded text-xs font-semibold transition-all"
                style={result === r ? { background: '#38BDF8', color: '#070A12' } : { background: '#0D1220', color: '#94A3B8', border: '1px solid #24314F' }}
              >
                {r}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Journal Table */}
      <div
        className="rounded-xl border overflow-hidden"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr style={{ borderBottom: '1px solid #24314F' }}>
                {['Date', 'Asset', 'Dir', 'Setup', 'AI', 'Score', 'Entry', 'SL', 'TP', 'RR', 'Result', 'R', 'Mistake', 'AI Comment'].map(
                  (col) => (
                    <th
                      key={col}
                      className="px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider whitespace-nowrap"
                      style={{ color: '#64748B' }}
                    >
                      {col}
                    </th>
                  )
                )}
              </tr>
            </thead>
            <tbody>
              {filtered.map((trade) => {
                const isWin = trade.result === 'Win';
                const isLoss = trade.result === 'Loss';

                return (
                  <tr
                    key={trade.id}
                    className="border-b hover:bg-white/[0.02] transition-colors"
                    style={{ borderColor: '#24314F' }}
                  >
                    <td className="px-3 py-2.5 text-xs whitespace-nowrap" style={{ color: '#94A3B8' }}>
                      {new Date(trade.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </td>
                    <td className="px-3 py-2.5">
                      <span className="text-sm font-bold" style={{ color: '#F8FAFC' }}>
                        {trade.asset}
                      </span>
                    </td>
                    <td className="px-3 py-2.5">
                      {trade.entry > 0 && (
                        <span
                          className="text-xs font-semibold"
                          style={{ color: trade.direction === 'LONG' ? '#22C55E' : '#EF4444' }}
                        >
                          {trade.direction === 'LONG' ? '▲' : '▼'}
                        </span>
                      )}
                    </td>
                    <td className="px-3 py-2.5 text-xs whitespace-nowrap" style={{ color: '#94A3B8' }}>
                      {trade.setupType}
                    </td>
                    <td className="px-3 py-2.5">
                      <StatusBadge variant={trade.aiDecision} size="sm" />
                    </td>
                    <td className="px-3 py-2.5">
                      <span
                        className="text-xs font-semibold"
                        style={{
                          color: trade.score >= 80 ? '#22C55E' : trade.score >= 65 ? '#F59E0B' : '#EF4444',
                        }}
                      >
                        {trade.score}
                      </span>
                    </td>
                    <td className="px-3 py-2.5 text-xs tabular-nums" style={{ color: '#94A3B8' }}>
                      {trade.entry > 0 ? trade.entry.toFixed(trade.asset.includes('USD') && !trade.asset.startsWith('XAU') ? 5 : 2) : '—'}
                    </td>
                    <td className="px-3 py-2.5 text-xs tabular-nums" style={{ color: '#EF4444' }}>
                      {trade.stopLoss.toFixed(trade.asset.includes('USD') && !trade.asset.startsWith('XAU') ? 5 : 2)}
                    </td>
                    <td className="px-3 py-2.5 text-xs tabular-nums" style={{ color: '#22C55E' }}>
                      {trade.takeProfit.toFixed(trade.asset.includes('USD') && !trade.asset.startsWith('XAU') ? 5 : 2)}
                    </td>
                    <td className="px-3 py-2.5 text-xs font-semibold" style={{ color: '#38BDF8' }}>
                      1:{trade.riskReward.toFixed(1)}
                    </td>
                    <td className="px-3 py-2.5">
                      <div className="flex items-center gap-1">
                        {isWin ? (
                          <TrendingUp className="h-3.5 w-3.5" style={{ color: '#22C55E' }} />
                        ) : isLoss ? (
                          <TrendingDown className="h-3.5 w-3.5" style={{ color: '#EF4444' }} />
                        ) : (
                          <Minus className="h-3.5 w-3.5" style={{ color: '#64748B' }} />
                        )}
                        <span
                          className="text-xs font-semibold"
                          style={{
                            color: isWin ? '#22C55E' : isLoss ? '#EF4444' : '#64748B',
                          }}
                        >
                          {trade.result}
                        </span>
                      </div>
                    </td>
                    <td className="px-3 py-2.5">
                      {trade.rMultiple !== undefined ? (
                        <span
                          className="text-xs font-bold tabular-nums"
                          style={{ color: trade.rMultiple >= 0 ? '#22C55E' : '#EF4444' }}
                        >
                          {trade.rMultiple >= 0 ? '+' : ''}
                          {trade.rMultiple.toFixed(1)}R
                        </span>
                      ) : (
                        <span className="text-xs" style={{ color: '#64748B' }}>—</span>
                      )}
                    </td>
                    <td className="px-3 py-2.5 max-w-[120px]">
                      {trade.mistake ? (
                        <span
                          className="text-[10px] rounded px-1.5 py-0.5 block truncate"
                          style={{ background: 'rgba(239, 68, 68, 0.1)', color: '#EF4444' }}
                        >
                          {trade.mistake}
                        </span>
                      ) : (
                        <span className="text-xs" style={{ color: '#64748B' }}>—</span>
                      )}
                    </td>
                    <td className="px-3 py-2.5 max-w-[200px]">
                      <p className="text-[10px] leading-relaxed line-clamp-2" style={{ color: '#64748B' }}>
                        {trade.aiComment}
                      </p>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      <p className="text-center text-[10px] mt-4" style={{ color: '#64748B' }}>
        This tool is for educational and analytical purposes only. It does not constitute financial advice.
      </p>
    </AppLayout>
  );
}
