import type { RiskStatus } from './mock-data';

export interface RiskManagerInput {
  tradesToday: number;
  maxTradesPerDay: number;
  dailyLossPct: number;
  maxDailyLoss: number;
  consecutiveLosses: number;
  maxConsecutiveLosses: number;
  riskReward: number;
  minimumRR: number;
  accountSize: number;
  riskPerTrade: number;
}

export interface RiskManagerResult {
  status: RiskStatus;
  reasons: string[];
  positionSizeUSD: number;
  positionSizeLots: number;
  maxRiskUSD: number;
  allowedTrades: number;
  remainingDailyDrawdown: number;
}

export function runRiskManager(input: RiskManagerInput): RiskManagerResult {
  const reasons: string[] = [];
  let status: RiskStatus = 'Allowed';

  // Check max trades per day
  if (input.tradesToday >= input.maxTradesPerDay) {
    status = 'Blocked';
    reasons.push(`Daily trade limit reached (${input.tradesToday}/${input.maxTradesPerDay})`);
  }

  // Check daily loss
  if (Math.abs(input.dailyLossPct) >= input.maxDailyLoss) {
    status = 'Blocked';
    reasons.push(`Daily drawdown limit reached (${input.dailyLossPct.toFixed(2)}% / ${input.maxDailyLoss}%)`);
  }

  // Check consecutive losses
  if (input.consecutiveLosses >= input.maxConsecutiveLosses) {
    status = 'Blocked';
    reasons.push(`Maximum consecutive losses reached (${input.consecutiveLosses}/${input.maxConsecutiveLosses})`);
  }

  // Check minimum RR
  if (input.riskReward < input.minimumRR) {
    if (status === 'Allowed') status = 'Blocked';
    reasons.push(`RR ratio below minimum (1:${input.riskReward.toFixed(1)} < 1:${input.minimumRR.toFixed(1)})`);
  }

  // Warnings (not blocking but notable)
  if (status === 'Allowed') {
    if (input.tradesToday >= input.maxTradesPerDay - 1 && input.tradesToday < input.maxTradesPerDay) {
      status = 'Warning';
      reasons.push(`Approaching daily trade limit (${input.tradesToday + 1}/${input.maxTradesPerDay})`);
    }
    if (Math.abs(input.dailyLossPct) >= input.maxDailyLoss * 0.75) {
      status = 'Warning';
      reasons.push(`Approaching daily drawdown limit (${input.dailyLossPct.toFixed(2)}% / ${input.maxDailyLoss}%)`);
    }
  }

  const maxRiskUSD = input.accountSize * (input.riskPerTrade / 100);
  const remainingDailyDrawdown = input.maxDailyLoss - Math.abs(input.dailyLossPct);

  return {
    status,
    reasons,
    positionSizeUSD: maxRiskUSD,
    positionSizeLots: parseFloat((maxRiskUSD / 100).toFixed(2)),
    maxRiskUSD,
    allowedTrades: Math.max(0, input.maxTradesPerDay - input.tradesToday),
    remainingDailyDrawdown: parseFloat(remainingDailyDrawdown.toFixed(2)),
  };
}

// Current mock state
export const mockRiskState = {
  tradesToday: 1,
  dailyLossPct: -0.25,
  consecutiveLosses: 0,
};
