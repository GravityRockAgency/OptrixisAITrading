'use client';

import { Brain, AlertTriangle, CheckCircle, XCircle, Clock } from 'lucide-react';
import { StatusBadge } from '@/components/ui/StatusBadge';
import type { Setup } from '@/lib/mock-data';

interface AIJudgeCardProps {
  setup: Setup;
}

export function AIJudgeCard({ setup }: AIJudgeCardProps) {
  const decisionColor = {
    GO: '#22C55E',
    WAIT: '#F59E0B',
    NO_TRADE: '#EF4444',
  }[setup.decision];

  const DecisionIcon = {
    GO: CheckCircle,
    WAIT: Clock,
    NO_TRADE: XCircle,
  }[setup.decision];

  return (
    <div
      className="rounded-xl border flex flex-col h-full"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      {/* Header */}
      <div
        className="flex items-center gap-2.5 px-4 py-3 border-b"
        style={{ borderColor: '#24314F' }}
      >
        <div
          className="flex h-7 w-7 items-center justify-center rounded-lg"
          style={{ background: 'rgba(167, 139, 250, 0.15)' }}
        >
          <Brain className="h-4 w-4" style={{ color: '#A78BFA' }} />
        </div>
        <div>
          <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
            AI Judge
          </h3>
          <p className="text-[10px]" style={{ color: '#64748B' }}>
            {setup.symbol} {setup.direction} · {setup.timeframe}
          </p>
        </div>
      </div>

      <div className="flex-1 p-4 flex flex-col gap-4">
        {/* Decision */}
        <div
          className="flex flex-col items-center justify-center py-4 rounded-lg border gap-2"
          style={{
            background: `${decisionColor}12`,
            borderColor: `${decisionColor}30`,
          }}
        >
          <DecisionIcon className="h-8 w-8" style={{ color: decisionColor }} />
          <span
            className="text-2xl font-black tracking-wider"
            style={{ color: decisionColor }}
          >
            {setup.decision === 'NO_TRADE' ? 'NO TRADE' : setup.decision}
          </span>
        </div>

        {/* Setup Info */}
        <div className="text-sm font-medium" style={{ color: '#F8FAFC' }}>
          {setup.symbol} {setup.direction === 'LONG' ? 'Long' : 'Short'} Setup — {setup.setupType}
        </div>

        {/* Scores */}
        <div className="space-y-3">
          <div>
            <div className="flex justify-between items-center mb-1">
              <span className="text-xs" style={{ color: '#94A3B8' }}>
                Technical Score
              </span>
              <span
                className="text-xs font-bold"
                style={{
                  color:
                    setup.technicalScore >= 80
                      ? '#22C55E'
                      : setup.technicalScore >= 65
                      ? '#F59E0B'
                      : '#EF4444',
                }}
              >
                {setup.technicalScore}/100
              </span>
            </div>
            <div
              className="h-2 rounded-full overflow-hidden"
              style={{ background: '#0D1220' }}
            >
              <div
                className="h-full rounded-full transition-all"
                style={{
                  width: `${setup.technicalScore}%`,
                  background:
                    setup.technicalScore >= 80
                      ? '#22C55E'
                      : setup.technicalScore >= 65
                      ? '#F59E0B'
                      : '#EF4444',
                }}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-2">
            <div
              className="rounded-lg p-2.5 border"
              style={{ background: '#0D1220', borderColor: '#24314F' }}
            >
              <p className="text-[10px] mb-1" style={{ color: '#64748B' }}>
                News Risk
              </p>
              <StatusBadge variant={setup.newsRisk} size="sm" />
            </div>
            <div
              className="rounded-lg p-2.5 border"
              style={{ background: '#0D1220', borderColor: '#24314F' }}
            >
              <p className="text-[10px] mb-1" style={{ color: '#64748B' }}>
                Risk Status
              </p>
              <StatusBadge variant={setup.riskStatus} size="sm" />
            </div>
          </div>
        </div>

        {/* Divider */}
        <div className="border-t" style={{ borderColor: '#24314F' }} />

        {/* Instruction */}
        <div className="space-y-2.5">
          <div
            className="rounded-lg p-3 border"
            style={{ background: 'rgba(167, 139, 250, 0.08)', borderColor: 'rgba(167, 139, 250, 0.2)' }}
          >
            <p className="text-[10px] font-semibold uppercase tracking-wider mb-1" style={{ color: '#A78BFA' }}>
              Final Instruction
            </p>
            <p className="text-xs leading-relaxed" style={{ color: '#F8FAFC' }}>
              {setup.reason}
            </p>
          </div>

          <div
            className="rounded-lg p-3 border"
            style={{ background: '#0D1220', borderColor: '#24314F' }}
          >
            <p className="text-[10px] font-semibold uppercase tracking-wider mb-1" style={{ color: '#64748B' }}>
              Next Action
            </p>
            <p className="text-xs" style={{ color: '#94A3B8' }}>
              {setup.nextAction}
            </p>
          </div>

          <div
            className="rounded-lg p-3 border flex gap-2"
            style={{ background: 'rgba(239, 68, 68, 0.06)', borderColor: 'rgba(239, 68, 68, 0.2)' }}
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
    </div>
  );
}
