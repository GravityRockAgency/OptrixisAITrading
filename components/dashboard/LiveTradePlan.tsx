'use client';

import { ArrowUp, ArrowDown, Target, StopCircle, TrendingUp, AlertTriangle, DollarSign } from 'lucide-react';
import type { Setup } from '@/lib/mock-data';

interface LiveTradePlanProps {
  setup: Setup;
  accountSize?: number;
  riskPercent?: number;
}

export function LiveTradePlan({
  setup,
  accountSize = 50000,
  riskPercent = 0.25,
}: LiveTradePlanProps) {
  const riskUSD = accountSize * (riskPercent / 100);
  const pipValue = setup.symbol === 'XAUUSD' ? 1 : 10;
  const slPips = Math.abs((setup.entryLow + setup.entryHigh) / 2 - setup.stopLoss);
  const positionSize = slPips > 0 ? (riskUSD / (slPips * pipValue)).toFixed(2) : '0.00';

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
          <TrendingUp className="h-4 w-4" style={{ color: '#38BDF8' }} />
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            Live Trade Plan
          </h3>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="text-lg font-bold" style={{ color: '#F8FAFC' }}>
            {setup.symbol}
          </span>
          <span
            className={`flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full`}
            style={
              setup.direction === 'LONG'
                ? { background: 'rgba(34, 197, 94, 0.15)', color: '#22C55E' }
                : { background: 'rgba(239, 68, 68, 0.15)', color: '#EF4444' }
            }
          >
            {setup.direction === 'LONG' ? (
              <ArrowUp className="h-3 w-3" />
            ) : (
              <ArrowDown className="h-3 w-3" />
            )}
            {setup.direction}
          </span>
        </div>
      </div>

      <div className="p-4 grid grid-cols-2 gap-3">
        {/* Entry Zone */}
        <div
          className="col-span-2 rounded-lg p-3 border"
          style={{ background: 'rgba(245, 158, 11, 0.07)', borderColor: 'rgba(245, 158, 11, 0.3)' }}
        >
          <p className="text-[10px] font-semibold uppercase tracking-wider mb-1" style={{ color: '#F59E0B' }}>
            Entry Zone
          </p>
          <p className="text-base font-bold" style={{ color: '#F8FAFC' }}>
            {setup.entryLow.toFixed(2)} — {setup.entryHigh.toFixed(2)}
          </p>
          <p className="text-[10px] mt-0.5" style={{ color: '#94A3B8' }}>
            Limit order in zone · {setup.timeframe} confirmation
          </p>
        </div>

        {/* Stop Loss */}
        <div
          className="rounded-lg p-3 border"
          style={{ background: 'rgba(239, 68, 68, 0.07)', borderColor: 'rgba(239, 68, 68, 0.25)' }}
        >
          <div className="flex items-center gap-1.5 mb-1">
            <StopCircle className="h-3 w-3" style={{ color: '#EF4444' }} />
            <p className="text-[10px] font-semibold uppercase tracking-wider" style={{ color: '#EF4444' }}>
              Stop Loss
            </p>
          </div>
          <p className="text-base font-bold" style={{ color: '#EF4444' }}>
            {setup.stopLoss.toFixed(2)}
          </p>
        </div>

        {/* Take Profit */}
        <div
          className="rounded-lg p-3 border"
          style={{ background: 'rgba(34, 197, 94, 0.07)', borderColor: 'rgba(34, 197, 94, 0.25)' }}
        >
          <div className="flex items-center gap-1.5 mb-1">
            <Target className="h-3 w-3" style={{ color: '#22C55E' }} />
            <p className="text-[10px] font-semibold uppercase tracking-wider" style={{ color: '#22C55E' }}>
              Take Profit
            </p>
          </div>
          <p className="text-base font-bold" style={{ color: '#22C55E' }}>
            {setup.takeProfit.toFixed(2)}
          </p>
        </div>

        {/* RR */}
        <div
          className="rounded-lg p-3 border"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <p className="text-[10px] font-semibold uppercase tracking-wider mb-1" style={{ color: '#64748B' }}>
            Risk / Reward
          </p>
          <p className="text-base font-bold" style={{ color: '#38BDF8' }}>
            1:{setup.riskReward.toFixed(1)}
          </p>
        </div>

        {/* Risk */}
        <div
          className="rounded-lg p-3 border"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <p className="text-[10px] font-semibold uppercase tracking-wider mb-1" style={{ color: '#64748B' }}>
            Risk
          </p>
          <p className="text-base font-bold" style={{ color: '#F8FAFC' }}>
            {riskPercent}%
          </p>
          <p className="text-[10px]" style={{ color: '#94A3B8' }}>
            ${riskUSD.toFixed(0)}
          </p>
        </div>

        {/* Position Size */}
        <div
          className="col-span-2 rounded-lg p-3 border flex items-center justify-between"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <div className="flex items-center gap-2">
            <DollarSign className="h-4 w-4" style={{ color: '#A78BFA' }} />
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider" style={{ color: '#64748B' }}>
                Position Size (auto-calculated)
              </p>
              <p className="text-sm font-bold" style={{ color: '#A78BFA' }}>
                {positionSize} lots
              </p>
            </div>
          </div>
          <div className="text-right">
            <p className="text-[10px]" style={{ color: '#64748B' }}>
              Max loss
            </p>
            <p className="text-sm font-bold" style={{ color: '#EF4444' }}>
              −${riskUSD.toFixed(0)}
            </p>
          </div>
        </div>

        {/* Invalidation */}
        <div
          className="col-span-2 rounded-lg p-3 border flex gap-2"
          style={{ background: 'rgba(239, 68, 68, 0.04)', borderColor: 'rgba(239, 68, 68, 0.15)' }}
        >
          <AlertTriangle className="h-3.5 w-3.5 shrink-0 mt-0.5" style={{ color: '#EF4444' }} />
          <div>
            <p className="text-[10px] font-semibold uppercase tracking-wider mb-0.5" style={{ color: '#EF4444' }}>
              Invalidation
            </p>
            <p className="text-xs" style={{ color: '#94A3B8' }}>
              {setup.invalidation}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
