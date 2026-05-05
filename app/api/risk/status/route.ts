import { NextResponse } from 'next/server';
import { mockRiskSettings } from '@/lib/mock-data';
import { runRiskManager, mockRiskState } from '@/lib/risk-manager';

export async function GET() {
  const result = runRiskManager({
    tradesToday: mockRiskState.tradesToday,
    maxTradesPerDay: mockRiskSettings.maxTradesPerDay,
    dailyLossPct: mockRiskState.dailyLossPct,
    maxDailyLoss: mockRiskSettings.maxDailyLoss,
    consecutiveLosses: mockRiskState.consecutiveLosses,
    maxConsecutiveLosses: mockRiskSettings.maxConsecutiveLosses,
    riskReward: 2.0,
    minimumRR: mockRiskSettings.minimumRR,
    accountSize: mockRiskSettings.accountSize,
    riskPerTrade: mockRiskSettings.riskPerTrade,
  });

  return NextResponse.json({
    status: result.status,
    reasons: result.reasons,
    settings: mockRiskSettings,
    state: mockRiskState,
    result,
    timestamp: new Date().toISOString(),
  });
}
