'use client';

import { Shield, ShieldOff } from 'lucide-react';
import { mockRiskSettings } from '@/lib/mock-data';
import { mockRiskState } from '@/lib/risk-manager';

export function RiskLock() {
  const isAllowed = true; // mock state
  const dailyDrawdownUsed = Math.abs(mockRiskState.dailyLossPct);
  const drawdownPct = (dailyDrawdownUsed / mockRiskSettings.maxDailyLoss) * 100;

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
        <Shield className="h-4 w-4" style={{ color: '#22C55E' }} />
        <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
          Risk Lock
        </h3>
      </div>

      <div className="p-4 space-y-3">
        {/* Status Banner */}
        <div
          className="rounded-lg p-3 border flex items-center gap-3"
          style={
            isAllowed
              ? { background: 'rgba(34, 197, 94, 0.08)', borderColor: 'rgba(34, 197, 94, 0.3)' }
              : { background: 'rgba(239, 68, 68, 0.08)', borderColor: 'rgba(239, 68, 68, 0.3)' }
          }
        >
          {isAllowed ? (
            <Shield className="h-5 w-5" style={{ color: '#22C55E' }} />
          ) : (
            <ShieldOff className="h-5 w-5" style={{ color: '#EF4444' }} />
          )}
          <div>
            <p
              className="text-sm font-bold"
              style={{ color: isAllowed ? '#22C55E' : '#EF4444' }}
            >
              {isAllowed ? 'Trading Allowed' : 'Trading Blocked'}
            </p>
            <p className="text-[10px]" style={{ color: '#94A3B8' }}>
              {isAllowed ? 'All risk rules within limits' : 'Review risk settings'}
            </p>
          </div>
        </div>

        {/* Metrics */}
        <div className="space-y-2.5">
          <div className="flex justify-between text-xs">
            <span style={{ color: '#94A3B8' }}>Account Size</span>
            <span className="font-semibold" style={{ color: '#F8FAFC' }}>
              ${mockRiskSettings.accountSize.toLocaleString()}
            </span>
          </div>
          <div className="flex justify-between text-xs">
            <span style={{ color: '#94A3B8' }}>Risk Per Trade</span>
            <span className="font-semibold" style={{ color: '#38BDF8' }}>
              {mockRiskSettings.riskPerTrade}%
            </span>
          </div>
          <div className="flex justify-between text-xs">
            <span style={{ color: '#94A3B8' }}>Trades Today</span>
            <span className="font-semibold" style={{ color: '#F8FAFC' }}>
              {mockRiskState.tradesToday}/{mockRiskSettings.maxTradesPerDay}
            </span>
          </div>

          {/* Daily Drawdown */}
          <div>
            <div className="flex justify-between text-xs mb-1.5">
              <span style={{ color: '#94A3B8' }}>Daily Drawdown</span>
              <span
                className="font-semibold"
                style={{
                  color: drawdownPct > 75 ? '#EF4444' : drawdownPct > 50 ? '#F59E0B' : '#22C55E',
                }}
              >
                −{dailyDrawdownUsed.toFixed(2)}% / −{mockRiskSettings.maxDailyLoss}%
              </span>
            </div>
            <div
              className="h-2 rounded-full overflow-hidden"
              style={{ background: '#0D1220' }}
            >
              <div
                className="h-full rounded-full transition-all"
                style={{
                  width: `${Math.min(drawdownPct, 100)}%`,
                  background:
                    drawdownPct > 75 ? '#EF4444' : drawdownPct > 50 ? '#F59E0B' : '#22C55E',
                }}
              />
            </div>
          </div>
        </div>

        {/* Rule */}
        <div
          className="rounded-lg p-2.5 border"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <p className="text-[10px] leading-relaxed" style={{ color: '#64748B' }}>
            Max {mockRiskSettings.maxTradesPerDay} trades/day · Max {mockRiskSettings.maxDailyLoss}% daily loss ·
            Min RR 1:{mockRiskSettings.minimumRR} · Max {mockRiskSettings.maxConsecutiveLosses} consecutive losses
          </p>
        </div>
      </div>
    </div>
  );
}
