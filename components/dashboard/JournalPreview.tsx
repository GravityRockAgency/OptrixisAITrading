'use client';

import Link from 'next/link';
import { BookOpen, ArrowRight, TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { mockTrades } from '@/lib/mock-data';

export function JournalPreview() {
  const recentTrades = mockTrades.slice(0, 3);

  return (
    <div
      className="rounded-xl border"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      {/* Header */}
      <div
        className="flex items-center justify-between px-4 py-3 border-b"
        style={{ borderColor: '#24314F' }}
      >
        <div className="flex items-center gap-2">
          <BookOpen className="h-4 w-4" style={{ color: '#38BDF8' }} />
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            Recent Journal
          </h3>
        </div>
        <Link
          href="/journal"
          className="flex items-center gap-1 text-xs font-medium transition-colors hover:opacity-80"
          style={{ color: '#38BDF8' }}
        >
          View all
          <ArrowRight className="h-3 w-3" />
        </Link>
      </div>

      <div className="divide-y" style={{ borderColor: '#24314F' }}>
        {recentTrades.map((trade) => {
          const isWin = trade.result === 'Win';
          const isLoss = trade.result === 'Loss';

          return (
            <div
              key={trade.id}
              className="flex items-center gap-4 px-4 py-3"
            >
              {/* Result indicator */}
              <div
                className="h-8 w-8 rounded-lg flex items-center justify-center shrink-0"
                style={{
                  background: isWin
                    ? 'rgba(34, 197, 94, 0.12)'
                    : isLoss
                    ? 'rgba(239, 68, 68, 0.12)'
                    : 'rgba(100, 116, 139, 0.12)',
                }}
              >
                {isWin ? (
                  <TrendingUp className="h-4 w-4" style={{ color: '#22C55E' }} />
                ) : isLoss ? (
                  <TrendingDown className="h-4 w-4" style={{ color: '#EF4444' }} />
                ) : (
                  <Minus className="h-4 w-4" style={{ color: '#64748B' }} />
                )}
              </div>

              {/* Trade info */}
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2 mb-0.5">
                  <span className="text-sm font-bold" style={{ color: '#F8FAFC' }}>
                    {trade.asset}
                  </span>
                  <StatusBadge variant={trade.aiDecision} size="sm" />
                  <span
                    className="text-xs"
                    style={{
                      color: isWin ? '#22C55E' : isLoss ? '#EF4444' : '#64748B',
                    }}
                  >
                    {trade.result}
                  </span>
                </div>
                <p className="text-[10px] truncate" style={{ color: '#64748B' }}>
                  {trade.aiComment}
                </p>
              </div>

              {/* R multiple */}
              {trade.rMultiple !== undefined && (
                <span
                  className="text-sm font-bold tabular-nums shrink-0"
                  style={{
                    color: trade.rMultiple >= 0 ? '#22C55E' : '#EF4444',
                  }}
                >
                  {trade.rMultiple >= 0 ? '+' : ''}
                  {trade.rMultiple.toFixed(1)}R
                </span>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
