'use client';

import { AppLayout } from '@/components/layout/AppLayout';
import { mockWeeklyReview } from '@/lib/mock-data';
import { MetricCard } from '@/components/ui/MetricCard';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  ResponsiveContainer,
  Cell,
  Tooltip,
} from 'recharts';
import { Brain, TrendingUp, TrendingDown, Trophy, AlertTriangle, Target, ChevronLeft, ChevronRight, BarChart2 } from 'lucide-react';

const review = mockWeeklyReview;

interface TooltipProps {
  active?: boolean;
  payload?: Array<{ value: number }>;
  label?: string;
}

function CustomTooltip({ active, payload, label }: TooltipProps) {
  if (!active || !payload?.length) return null;
  const value = payload[0]?.value ?? 0;
  return (
    <div
      className="rounded-lg border px-3 py-2 text-xs"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      <p style={{ color: '#94A3B8' }}>{label}</p>
      <p
        className="font-bold"
        style={{ color: value >= 0 ? '#22C55E' : '#EF4444' }}
      >
        {value >= 0 ? '+' : ''}{value.toFixed(0)} USD
      </p>
    </div>
  );
}

export default function ReviewPage() {
  const winRate = review.wins + review.losses > 0
    ? Math.round((review.wins / (review.wins + review.losses)) * 100)
    : 0;

  return (
    <AppLayout
      title="AI Weekly Review"
      subtitle="AI-powered performance analysis with actionable insights and next-week focus."
    >
      {/* Week Selector */}
      <div
        className="rounded-xl border p-3 mb-4 flex items-center justify-between"
        style={{ background: '#101828', borderColor: '#24314F' }}
      >
        <button className="p-1.5 rounded hover:bg-white/5 transition-colors">
          <ChevronLeft className="h-4 w-4" style={{ color: '#64748B' }} />
        </button>
        <div className="text-center">
          <p className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            {review.weekLabel}
          </p>
          <p className="text-[10px]" style={{ color: '#64748B' }}>
            {review.startDate} — {review.endDate}
          </p>
        </div>
        <button className="p-1.5 rounded hover:bg-white/5 transition-colors">
          <ChevronRight className="h-4 w-4" style={{ color: '#64748B' }} />
        </button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <MetricCard
          label="Win Rate"
          value={`${winRate}%`}
          subValue={`${review.wins}W / ${review.losses}L`}
          accent="green"
          icon={TrendingUp}
        />
        <MetricCard
          label="Total R"
          value={`+${review.totalRMultiple.toFixed(1)}R`}
          subValue="Risk multiples"
          accent="blue"
          icon={Target}
        />
        <MetricCard
          label="Avg RR"
          value={`1:${review.avgRR.toFixed(1)}`}
          subValue="Per taken trade"
          accent="purple"
          icon={BarChart2}
        />
        <MetricCard
          label="Setup Quality"
          value={`${review.taken}/${review.totalSetups}`}
          subValue={`${review.blocked} blocked`}
          accent="amber"
          icon={Brain}
        />
      </div>

      {/* Setup breakdown + Daily PnL */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
        {/* Setup Breakdown */}
        <div
          className="rounded-xl border p-4"
          style={{ background: '#101828', borderColor: '#24314F' }}
        >
          <h3 className="text-sm font-semibold mb-4" style={{ color: '#F8FAFC' }}>
            Setup Breakdown
          </h3>
          <div className="space-y-3">
            {[
              { label: 'Total Setups', value: review.totalSetups, color: '#94A3B8' },
              { label: 'Taken', value: review.taken, color: '#38BDF8' },
              { label: 'Skipped', value: review.skipped, color: '#F59E0B' },
              { label: 'Blocked', value: review.blocked, color: '#EF4444' },
              { label: 'Wins', value: review.wins, color: '#22C55E' },
              { label: 'Losses', value: review.losses, color: '#EF4444' },
            ].map(({ label, value, color }) => (
              <div key={label} className="flex items-center justify-between">
                <span className="text-xs" style={{ color: '#94A3B8' }}>
                  {label}
                </span>
                <div className="flex items-center gap-2">
                  <div
                    className="h-1.5 rounded-full"
                    style={{
                      width: `${(value / review.totalSetups) * 80}px`,
                      background: color,
                      opacity: 0.6,
                    }}
                  />
                  <span className="text-sm font-bold w-5 text-right" style={{ color }}>
                    {value}
                  </span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Daily P&L Chart */}
        <div
          className="xl:col-span-2 rounded-xl border p-4"
          style={{ background: '#101828', borderColor: '#24314F' }}
        >
          <h3 className="text-sm font-semibold mb-4" style={{ color: '#F8FAFC' }}>
            Daily P&amp;L (USD)
          </h3>
          <div style={{ height: 200 }}>
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={review.dailyPnL} margin={{ top: 5, right: 10, bottom: 5, left: 10 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#24314F" strokeOpacity={0.5} vertical={false} />
                <XAxis
                  dataKey="day"
                  tick={{ fill: '#64748B', fontSize: 11 }}
                  tickLine={false}
                  axisLine={false}
                />
                <YAxis
                  tick={{ fill: '#64748B', fontSize: 10 }}
                  tickLine={false}
                  axisLine={false}
                  tickFormatter={(v: number) => `$${v}`}
                />
                <Tooltip content={<CustomTooltip />} />
                <Bar dataKey="pnl" radius={[4, 4, 0, 0]} isAnimationActive={false}>
                  {review.dailyPnL.map((entry, index) => (
                    <Cell
                      key={`cell-${index}`}
                      fill={entry.pnl >= 0 ? '#22C55E' : '#EF4444'}
                      fillOpacity={0.8}
                    />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Performance Insights */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        {/* Best / Worst */}
        <div
          className="rounded-xl border p-4 space-y-3"
          style={{ background: '#101828', borderColor: '#24314F' }}
        >
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            Performance Highlights
          </h3>

          <div
            className="rounded-lg p-3 border flex items-start gap-3"
            style={{ background: 'rgba(34, 197, 94, 0.06)', borderColor: 'rgba(34, 197, 94, 0.2)' }}
          >
            <Trophy className="h-4 w-4 shrink-0 mt-0.5" style={{ color: '#22C55E' }} />
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider mb-0.5" style={{ color: '#22C55E' }}>
                Best Asset
              </p>
              <p className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                {review.bestAsset}
              </p>
            </div>
          </div>

          <div
            className="rounded-lg p-3 border flex items-start gap-3"
            style={{ background: 'rgba(239, 68, 68, 0.06)', borderColor: 'rgba(239, 68, 68, 0.2)' }}
          >
            <TrendingDown className="h-4 w-4 shrink-0 mt-0.5" style={{ color: '#EF4444' }} />
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider mb-0.5" style={{ color: '#EF4444' }}>
                Worst Asset
              </p>
              <p className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                {review.worstAsset}
              </p>
            </div>
          </div>

          <div
            className="rounded-lg p-3 border flex items-start gap-3"
            style={{ background: 'rgba(245, 158, 11, 0.06)', borderColor: 'rgba(245, 158, 11, 0.2)' }}
          >
            <AlertTriangle className="h-4 w-4 shrink-0 mt-0.5" style={{ color: '#F59E0B' }} />
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider mb-0.5" style={{ color: '#F59E0B' }}>
                Main Mistake
              </p>
              <p className="text-sm" style={{ color: '#F8FAFC' }}>
                {review.mainMistake}
              </p>
            </div>
          </div>

          <div
            className="rounded-lg p-3 border flex items-start gap-3"
            style={{ background: 'rgba(56, 189, 248, 0.06)', borderColor: 'rgba(56, 189, 248, 0.2)' }}
          >
            <Target className="h-4 w-4 shrink-0 mt-0.5" style={{ color: '#38BDF8' }} />
            <div>
              <p className="text-[10px] font-semibold uppercase tracking-wider mb-0.5" style={{ color: '#38BDF8' }}>
                Best Setup
              </p>
              <p className="text-sm" style={{ color: '#F8FAFC' }}>
                {review.bestSetup}
              </p>
            </div>
          </div>
        </div>

        {/* AI Recommendation */}
        <div
          className="rounded-xl border p-4 flex flex-col"
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
              AI Recommendation
            </h3>
          </div>

          <div
            className="flex-1 rounded-lg p-4 border"
            style={{ background: 'rgba(167, 139, 250, 0.06)', borderColor: 'rgba(167, 139, 250, 0.2)' }}
          >
            <p className="text-sm leading-relaxed" style={{ color: '#F8FAFC' }}>
              {review.aiRecommendation}
            </p>
          </div>

          <div
            className="mt-3 rounded-lg p-3 border"
            style={{ background: '#0D1220', borderColor: '#24314F' }}
          >
            <p className="text-[10px] font-semibold uppercase tracking-wider mb-1.5" style={{ color: '#64748B' }}>
              Next Week Focus
            </p>
            <p className="text-xs leading-relaxed" style={{ color: '#94A3B8' }}>
              {review.nextWeekFocus}
            </p>
          </div>
        </div>
      </div>

      <p className="text-center text-[10px] mt-2" style={{ color: '#64748B' }}>
        This tool is for educational and analytical purposes only. It does not constitute financial advice.
      </p>
    </AppLayout>
  );
}
