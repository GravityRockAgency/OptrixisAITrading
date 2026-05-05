'use client';

import { AppLayout } from '@/components/layout/AppLayout';
import { MetricCard } from '@/components/ui/MetricCard';
import { TradingChartMock } from '@/components/dashboard/TradingChartMock';
import { AIJudgeCard } from '@/components/dashboard/AIJudgeCard';
import { LiveTradePlan } from '@/components/dashboard/LiveTradePlan';
import { AssetsWatchlist } from '@/components/dashboard/AssetsWatchlist';
import { NewsGuard } from '@/components/dashboard/NewsGuard';
import { SessionFilter } from '@/components/dashboard/SessionFilter';
import { RiskLock } from '@/components/dashboard/RiskLock';
import { JournalPreview } from '@/components/dashboard/JournalPreview';
import {
  mockCurrentSetup,
  mockRiskSettings,
  mockTrades,
} from '@/lib/mock-data';
import {
  Brain,
  TrendingUp,
  Shield,
  BookOpen,
  Target,
} from 'lucide-react';

export default function DashboardPage() {
  const wins = mockTrades.filter((t) => t.result === 'Win').length;
  const taken = mockTrades.filter((t) => t.result === 'Win' || t.result === 'Loss').length;
  const winRate = taken > 0 ? Math.round((wins / taken) * 100) : 0;
  const totalR = mockTrades.reduce((sum, t) => sum + (t.rMultiple ?? 0), 0);

  return (
    <AppLayout
      title="Market Radar"
      subtitle="AI trading cockpit for setups, macro news, risk and execution discipline."
    >
      {/* Top Metrics Row */}
      <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-4">
        <MetricCard
          label="Active Setups"
          value="3"
          subValue="1 GO · 2 WAIT"
          icon={Target}
          accent="blue"
        />
        <MetricCard
          label="AI Decision"
          value="WAIT"
          subValue="XAUUSD M5"
          icon={Brain}
          accent="purple"
        />
        <MetricCard
          label="Win Rate"
          value={`${winRate}%`}
          subValue={`${wins}/${taken} trades`}
          trend="up"
          trendLabel="+5%"
          icon={TrendingUp}
          accent="green"
        />
        <MetricCard
          label="Total R"
          value={`+${totalR.toFixed(1)}R`}
          subValue="This week"
          trend="up"
          trendLabel="week"
          icon={BookOpen}
          accent="green"
        />
        <MetricCard
          label="Risk Status"
          value="Allowed"
          subValue={`${mockRiskSettings.maxTradesPerDay - 1}/${mockRiskSettings.maxTradesPerDay} trades`}
          icon={Shield}
          accent="green"
        />
      </div>

      {/* Main Area: Chart + AI Judge */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
        <div className="xl:col-span-2">
          <TradingChartMock />
        </div>
        <div className="xl:col-span-1">
          <AIJudgeCard setup={mockCurrentSetup} />
        </div>
      </div>

      {/* Second Row: Live Trade Plan + Assets Watchlist */}
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
        <div className="xl:col-span-1">
          <LiveTradePlan
            setup={mockCurrentSetup}
            accountSize={mockRiskSettings.accountSize}
            riskPercent={mockRiskSettings.riskPerTrade}
          />
        </div>
        <div className="xl:col-span-2">
          <AssetsWatchlist />
        </div>
      </div>

      {/* Third Row: News Guard + Session Filter + Risk Lock */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <NewsGuard />
        <SessionFilter />
        <RiskLock />
      </div>

      {/* Journal Preview Strip */}
      <JournalPreview />

      {/* Disclaimer */}
      <p className="text-center text-[10px] mt-4 leading-relaxed" style={{ color: '#64748B' }}>
        This tool is for educational and analytical purposes only. It does not constitute financial advice.
        Tradyos EdgePilot is a decision-support tool — it never executes trades automatically.
        Trading involves significant risk of loss.
      </p>
    </AppLayout>
  );
}
