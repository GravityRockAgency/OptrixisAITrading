'use client';

import { useState } from 'react';
import { AppLayout } from '@/components/layout/AppLayout';
import { TradingChartMock } from '@/components/dashboard/TradingChartMock';
import { AIJudgeCard } from '@/components/dashboard/AIJudgeCard';
import { LiveTradePlan } from '@/components/dashboard/LiveTradePlan';
import { mockSetups, mockRiskSettings } from '@/lib/mock-data';
import { Check, X, Bell, BookOpen, Ban, SkipForward } from 'lucide-react';

interface ChecklistItem {
  id: string;
  label: string;
  checked: boolean;
  critical?: boolean;
}

const defaultChecklist: ChecklistItem[] = [
  { id: 'htf_bias', label: 'HTF bias aligned', checked: true },
  { id: 'ob_fresh', label: 'M5 order block fresh (not mitigated)', checked: true },
  { id: 'fvg_aligned', label: 'FVG aligned with setup direction', checked: true },
  { id: 'sweep', label: 'Liquidity sweep confirmed', checked: true },
  { id: 'm1_choch', label: 'M1 CHoCH confirmed', checked: false, critical: true },
  { id: 'rr_min', label: 'RR minimum 1:2 satisfied', checked: true },
  { id: 'news_clear', label: 'News window clear (no high-impact <15min)', checked: false },
  { id: 'risk_rules', label: 'Risk rules respected (trade count, drawdown)', checked: true },
];

interface PageProps {
  params: { id: string };
}

export default function SetupDetailPage({ params }: PageProps) {
  const setup = mockSetups.find((s) => s.id === params.id) ?? mockSetups[0];
  const [checklist, setChecklist] = useState<ChecklistItem[]>(defaultChecklist);
  const [actionTaken, setActionTaken] = useState<string | null>(null);

  const toggleItem = (id: string) => {
    setChecklist((prev) =>
      prev.map((item) => (item.id === id ? { ...item, checked: !item.checked } : item))
    );
  };

  const checkedCount = checklist.filter((i) => i.checked).length;
  const criticalMissing = checklist.filter((i) => i.critical && !i.checked);
  const readyToTrade = criticalMissing.length === 0 && checkedCount >= 7;

  return (
    <AppLayout
      title={`Setup · ${setup.symbol}`}
      subtitle={`${setup.direction} · ${setup.setupType} · ${setup.timeframe}`}
    >
      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4 mb-4">
        {/* Chart */}
        <div className="xl:col-span-2">
          <TradingChartMock />
        </div>
        {/* AI Judge */}
        <div className="xl:col-span-1">
          <AIJudgeCard setup={setup} />
        </div>
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
        {/* Trade Plan */}
        <div className="xl:col-span-1">
          <LiveTradePlan
            setup={setup}
            accountSize={mockRiskSettings.accountSize}
            riskPercent={mockRiskSettings.riskPerTrade}
          />
        </div>

        {/* Confirmation Checklist */}
        <div className="xl:col-span-2">
          <div
            className="rounded-xl border"
            style={{ background: '#101828', borderColor: '#24314F' }}
          >
            {/* Header */}
            <div
              className="flex items-center justify-between px-4 py-3 border-b"
              style={{ borderColor: '#24314F' }}
            >
              <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                Confirmation Checklist
              </h3>
              <span
                className="text-xs font-semibold px-2 py-0.5 rounded-full"
                style={
                  readyToTrade
                    ? { background: 'rgba(34, 197, 94, 0.15)', color: '#22C55E' }
                    : { background: 'rgba(245, 158, 11, 0.15)', color: '#F59E0B' }
                }
              >
                {checkedCount}/{checklist.length} confirmed
              </span>
            </div>

            <div className="p-4 space-y-2">
              {checklist.map((item) => (
                <button
                  key={item.id}
                  onClick={() => toggleItem(item.id)}
                  className="w-full flex items-center gap-3 text-left rounded-lg px-3 py-2.5 border transition-all hover:border-sky-500/30"
                  style={{
                    background: item.checked ? 'rgba(34, 197, 94, 0.05)' : '#0D1220',
                    borderColor: item.checked
                      ? 'rgba(34, 197, 94, 0.2)'
                      : item.critical
                      ? 'rgba(239, 68, 68, 0.3)'
                      : '#24314F',
                  }}
                >
                  <div
                    className="h-5 w-5 rounded flex items-center justify-center shrink-0"
                    style={
                      item.checked
                        ? { background: '#22C55E' }
                        : { background: 'transparent', border: `1.5px solid ${item.critical ? '#EF4444' : '#24314F'}` }
                    }
                  >
                    {item.checked && <Check className="h-3 w-3 text-white" />}
                  </div>
                  <span
                    className="text-sm flex-1"
                    style={{ color: item.checked ? '#F8FAFC' : '#94A3B8' }}
                  >
                    {item.label}
                  </span>
                  {item.critical && !item.checked && (
                    <span
                      className="text-[10px] font-bold px-1.5 py-0.5 rounded"
                      style={{ background: 'rgba(239, 68, 68, 0.15)', color: '#EF4444' }}
                    >
                      Required
                    </span>
                  )}
                  {item.checked ? (
                    <Check className="h-4 w-4" style={{ color: '#22C55E' }} />
                  ) : (
                    <X className="h-4 w-4" style={{ color: '#64748B' }} />
                  )}
                </button>
              ))}

              {/* Readiness status */}
              {readyToTrade ? (
                <div
                  className="rounded-lg p-3 border text-center"
                  style={{
                    background: 'rgba(34, 197, 94, 0.08)',
                    borderColor: 'rgba(34, 197, 94, 0.3)',
                  }}
                >
                  <p className="text-sm font-semibold" style={{ color: '#22C55E' }}>
                    All criteria met — Setup ready to execute
                  </p>
                </div>
              ) : (
                <div
                  className="rounded-lg p-3 border"
                  style={{
                    background: 'rgba(245, 158, 11, 0.06)',
                    borderColor: 'rgba(245, 158, 11, 0.25)',
                  }}
                >
                  <p className="text-xs font-semibold mb-1" style={{ color: '#F59E0B' }}>
                    Pending confirmation
                  </p>
                  {criticalMissing.map((item) => (
                    <p key={item.id} className="text-xs" style={{ color: '#94A3B8' }}>
                      ✗ {item.label}
                    </p>
                  ))}
                </div>
              )}
            </div>

            {/* Action Buttons */}
            <div
              className="px-4 pb-4 flex flex-wrap gap-2 border-t pt-4"
              style={{ borderColor: '#24314F' }}
            >
              {actionTaken ? (
                <div
                  className="w-full text-center py-2 rounded-lg text-sm font-semibold"
                  style={{ background: 'rgba(56, 189, 248, 0.1)', color: '#38BDF8' }}
                >
                  Action recorded: {actionTaken}
                </div>
              ) : (
                <>
                  <button
                    onClick={() => setActionTaken('Saved to Journal')}
                    className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                    style={{ background: 'rgba(56, 189, 248, 0.12)', color: '#38BDF8', border: '1px solid rgba(56, 189, 248, 0.25)' }}
                  >
                    <BookOpen className="h-3.5 w-3.5" />
                    Save to Journal
                  </button>
                  <button
                    onClick={() => setActionTaken('Marked as Taken')}
                    className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                    style={{ background: 'rgba(34, 197, 94, 0.12)', color: '#22C55E', border: '1px solid rgba(34, 197, 94, 0.25)' }}
                  >
                    <Check className="h-3.5 w-3.5" />
                    Mark as Taken
                  </button>
                  <button
                    onClick={() => setActionTaken('Marked as Skipped')}
                    className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                    style={{ background: 'rgba(245, 158, 11, 0.12)', color: '#F59E0B', border: '1px solid rgba(245, 158, 11, 0.25)' }}
                  >
                    <SkipForward className="h-3.5 w-3.5" />
                    Mark as Skipped
                  </button>
                  <button
                    onClick={() => setActionTaken('Marked as Invalidated')}
                    className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                    style={{ background: 'rgba(239, 68, 68, 0.12)', color: '#EF4444', border: '1px solid rgba(239, 68, 68, 0.25)' }}
                  >
                    <Ban className="h-3.5 w-3.5" />
                    Invalidated
                  </button>
                  <button
                    onClick={() => setActionTaken('Alert Created')}
                    className="flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold transition-all hover:opacity-80"
                    style={{ background: 'rgba(167, 139, 250, 0.12)', color: '#A78BFA', border: '1px solid rgba(167, 139, 250, 0.25)' }}
                  >
                    <Bell className="h-3.5 w-3.5" />
                    Create Alert
                  </button>
                </>
              )}
            </div>
          </div>
        </div>
      </div>

      <p className="text-center text-[10px] mt-4" style={{ color: '#64748B' }}>
        This tool is for educational and analytical purposes only. It does not constitute financial advice.
      </p>
    </AppLayout>
  );
}
