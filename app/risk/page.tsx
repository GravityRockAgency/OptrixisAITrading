'use client';

import { useState } from 'react';
import { AppLayout } from '@/components/layout/AppLayout';
import { MetricCard } from '@/components/ui/MetricCard';
import { mockRiskSettings } from '@/lib/mock-data';
import { mockRiskState, runRiskManager } from '@/lib/risk-manager';
import { Shield, ShieldOff, DollarSign, TrendingDown, BarChart2, Save } from 'lucide-react';

export default function RiskPage() {
  const [settings, setSettings] = useState(mockRiskSettings);
  const [saved, setSaved] = useState(false);

  const riskResult = runRiskManager({
    tradesToday: mockRiskState.tradesToday,
    maxTradesPerDay: settings.maxTradesPerDay,
    dailyLossPct: mockRiskState.dailyLossPct,
    maxDailyLoss: settings.maxDailyLoss,
    consecutiveLosses: mockRiskState.consecutiveLosses,
    maxConsecutiveLosses: settings.maxConsecutiveLosses,
    riskReward: 2.6,
    minimumRR: settings.minimumRR,
    accountSize: settings.accountSize,
    riskPerTrade: settings.riskPerTrade,
  });

  const drawdownUsed = Math.abs(mockRiskState.dailyLossPct);
  const drawdownPct = (drawdownUsed / settings.maxDailyLoss) * 100;

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  return (
    <AppLayout
      title="Risk Manager"
      subtitle="Real-time risk monitoring and protection rules for disciplined trading."
    >
      {/* Status Banner */}
      <div
        className="rounded-xl border p-4 mb-4 flex items-center justify-between"
        style={
          riskResult.status === 'Allowed'
            ? { background: 'rgba(34, 197, 94, 0.08)', borderColor: 'rgba(34, 197, 94, 0.35)' }
            : riskResult.status === 'Warning'
            ? { background: 'rgba(245, 158, 11, 0.08)', borderColor: 'rgba(245, 158, 11, 0.35)' }
            : { background: 'rgba(239, 68, 68, 0.08)', borderColor: 'rgba(239, 68, 68, 0.35)' }
        }
      >
        <div className="flex items-center gap-3">
          {riskResult.status === 'Allowed' ? (
            <Shield className="h-6 w-6" style={{ color: '#22C55E' }} />
          ) : (
            <ShieldOff className="h-6 w-6" style={{ color: riskResult.status === 'Warning' ? '#F59E0B' : '#EF4444' }} />
          )}
          <div>
            <p
              className="text-lg font-bold"
              style={{
                color: riskResult.status === 'Allowed' ? '#22C55E' : riskResult.status === 'Warning' ? '#F59E0B' : '#EF4444',
              }}
            >
              {riskResult.status === 'Allowed'
                ? 'Trading Allowed'
                : riskResult.status === 'Warning'
                ? 'Trading Warning'
                : 'Trading Blocked'}
            </p>
            {riskResult.reasons.length > 0 ? (
              riskResult.reasons.map((r, i) => (
                <p key={i} className="text-xs" style={{ color: '#94A3B8' }}>
                  {r}
                </p>
              ))
            ) : (
              <p className="text-xs" style={{ color: '#94A3B8' }}>
                All risk parameters within acceptable limits
              </p>
            )}
          </div>
        </div>
        <div className="text-right">
          <p className="text-sm font-semibold" style={{ color: '#94A3B8' }}>
            Remaining trades
          </p>
          <p className="text-2xl font-black" style={{ color: '#F8FAFC' }}>
            {riskResult.allowedTrades}
          </p>
        </div>
      </div>

      {/* Account Overview */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <MetricCard
          label="Account Size"
          value={`$${(settings.accountSize / 1000).toFixed(0)}K`}
          subValue="Challenge account"
          icon={DollarSign}
          accent="blue"
        />
        <MetricCard
          label="Risk Per Trade"
          value={`${settings.riskPerTrade}%`}
          subValue={`$${(settings.accountSize * settings.riskPerTrade / 100).toFixed(0)} max`}
          icon={BarChart2}
          accent="purple"
        />
        <MetricCard
          label="Daily Drawdown"
          value={`-${drawdownUsed.toFixed(2)}%`}
          subValue={`Limit: -${settings.maxDailyLoss}%`}
          trend={drawdownPct > 75 ? 'down' : 'neutral'}
          trendLabel={`${drawdownPct.toFixed(0)}% used`}
          icon={TrendingDown}
          accent={drawdownPct > 75 ? 'red' : drawdownPct > 50 ? 'amber' : 'green'}
        />
        <MetricCard
          label="Trades Today"
          value={`${mockRiskState.tradesToday}/${settings.maxTradesPerDay}`}
          subValue={`${riskResult.allowedTrades} remaining`}
          icon={Shield}
          accent={mockRiskState.tradesToday >= settings.maxTradesPerDay ? 'red' : 'green'}
        />
      </div>

      {/* Drawdown Gauge */}
      <div
        className="rounded-xl border p-4 mb-4"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            Daily Drawdown Gauge
          </h3>
          <span className="text-xs" style={{ color: '#94A3B8' }}>
            Prop Firm Challenge 50K
          </span>
        </div>
        <div className="relative h-5 rounded-full overflow-hidden mb-2" style={{ background: '#0D1220' }}>
          {/* Warning zone */}
          <div
            className="absolute top-0 h-full rounded-full opacity-20"
            style={{ left: '50%', width: '25%', background: '#F59E0B' }}
          />
          {/* Danger zone */}
          <div
            className="absolute top-0 h-full rounded-full opacity-20"
            style={{ left: '75%', width: '25%', background: '#EF4444' }}
          />
          {/* Used */}
          <div
            className="absolute top-0 h-full rounded-full transition-all duration-700"
            style={{
              width: `${Math.min(drawdownPct, 100)}%`,
              background:
                drawdownPct > 75 ? '#EF4444' : drawdownPct > 50 ? '#F59E0B' : '#22C55E',
            }}
          />
        </div>
        <div className="flex justify-between text-[10px]" style={{ color: '#64748B' }}>
          <span>0%</span>
          <span style={{ color: '#F59E0B' }}>50% — Warning</span>
          <span style={{ color: '#EF4444' }}>75% — Danger</span>
          <span style={{ color: '#EF4444' }}>100% — Blocked</span>
        </div>
        <p className="text-xs mt-2" style={{ color: '#94A3B8' }}>
          Used{' '}
          <span className="font-semibold" style={{ color: '#22C55E' }}>
            {drawdownUsed.toFixed(2)}%
          </span>{' '}
          of{' '}
          <span className="font-semibold">{settings.maxDailyLoss}%</span>{' '}
          daily limit.{' '}
          <span style={{ color: '#22C55E' }}>
            {riskResult.remainingDailyDrawdown.toFixed(2)}% remaining.
          </span>
        </p>
      </div>

      {/* Risk Rules Configuration */}
      <div
        className="rounded-xl border p-4"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            Risk Rules Configuration
          </h3>
          <button
            onClick={handleSave}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
            style={{
              background: saved ? 'rgba(34, 197, 94, 0.15)' : 'rgba(56, 189, 248, 0.12)',
              color: saved ? '#22C55E' : '#38BDF8',
              border: `1px solid ${saved ? 'rgba(34, 197, 94, 0.3)' : 'rgba(56, 189, 248, 0.25)'}`,
            }}
          >
            <Save className="h-3.5 w-3.5" />
            {saved ? 'Saved!' : 'Save Settings'}
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {[
            {
              label: 'Account Size ($)',
              key: 'accountSize' as const,
              type: 'number',
              min: 1000,
              step: 1000,
            },
            {
              label: 'Risk Per Trade (%)',
              key: 'riskPerTrade' as const,
              type: 'number',
              min: 0.1,
              max: 5,
              step: 0.05,
            },
            {
              label: 'Max Daily Loss (%)',
              key: 'maxDailyLoss' as const,
              type: 'number',
              min: 0.5,
              max: 5,
              step: 0.25,
            },
            {
              label: 'Max Trades Per Day',
              key: 'maxTradesPerDay' as const,
              type: 'number',
              min: 1,
              max: 10,
              step: 1,
            },
            {
              label: 'Max Consecutive Losses',
              key: 'maxConsecutiveLosses' as const,
              type: 'number',
              min: 1,
              max: 5,
              step: 1,
            },
            {
              label: 'Minimum RR Ratio (e.g. 2 = 1:2)',
              key: 'minimumRR' as const,
              type: 'number',
              min: 1,
              max: 5,
              step: 0.5,
            },
          ].map(({ label, key, ...attrs }) => (
            <div key={key}>
              <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                {label}
              </label>
              <input
                {...attrs}
                value={settings[key]}
                onChange={(e) =>
                  setSettings((prev) => ({
                    ...prev,
                    [key]: parseFloat(e.target.value),
                  }))
                }
                className="w-full rounded-lg px-3 py-2 text-sm font-semibold outline-none focus:ring-1 focus:ring-sky-500/50"
                style={{
                  background: '#0D1220',
                  border: '1px solid #24314F',
                  color: '#F8FAFC',
                }}
              />
            </div>
          ))}
        </div>

        <div
          className="mt-4 rounded-lg p-3 border"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <p className="text-[10px] leading-relaxed" style={{ color: '#64748B' }}>
            Risk settings are enforced before every setup decision. The AI Judge and Risk Manager work together to block
            trades that violate these rules. Prop firm protection mode ensures you never breach challenge conditions automatically.
          </p>
        </div>
      </div>

      <p className="text-center text-[10px] mt-4" style={{ color: '#64748B' }}>
        This tool is for educational and analytical purposes only. It does not constitute financial advice.
      </p>
    </AppLayout>
  );
}
