import type { Decision, NewsRisk, RiskStatus, AIJudgeResult } from './mock-data';

export interface AIJudgeInput {
  technicalScore: number;
  newsRisk: NewsRisk;
  riskStatus: RiskStatus;
  riskReward: number;
  m1Confirmation: boolean;
  symbol: string;
  direction: 'LONG' | 'SHORT';
}

export function runAIJudge(input: AIJudgeInput): AIJudgeResult {
  const { technicalScore, newsRisk, riskStatus, riskReward, m1Confirmation } = input;

  // Hard blocks — these override everything
  if (riskStatus === 'Blocked') {
    return {
      decision: 'NO_TRADE' as Decision,
      technicalScore,
      newsRisk,
      riskStatus,
      reason: 'Risk Manager has blocked trading. Review your daily drawdown limits or trade count.',
      nextAction: 'Check risk settings. Wait for reset or contact your prop firm.',
      invalidation: 'Trade blocked by risk management system.',
      confidence: 0,
    };
  }

  if (newsRisk === 'High') {
    return {
      decision: 'NO_TRADE' as Decision,
      technicalScore,
      newsRisk,
      riskStatus,
      reason: 'High-impact news event is imminent or just released. Market conditions are unpredictable.',
      nextAction: 'Wait for post-news cooldown (minimum 30 minutes after high-impact events).',
      invalidation: 'Reassess setup after market stabilizes post-news.',
      confidence: 5,
    };
  }

  if (riskReward < 2.0) {
    return {
      decision: 'NO_TRADE' as Decision,
      technicalScore,
      newsRisk,
      riskStatus,
      reason: `Risk-reward ratio of 1:${riskReward.toFixed(1)} is below the minimum threshold of 1:2.0.`,
      nextAction: 'Adjust take profit level to achieve at least 1:2 RR, or skip this setup.',
      invalidation: 'Setup does not meet minimum RR requirement.',
      confidence: 10,
    };
  }

  // GO conditions (newsRisk is already narrowed to 'Low' | 'Medium' at this point)
  if (technicalScore >= 80 && m1Confirmation) {
    const confidence = Math.min(95, technicalScore + (newsRisk === 'Low' ? 10 : 0));
    return {
      decision: 'GO' as Decision,
      technicalScore,
      newsRisk,
      riskStatus,
      reason: `High-quality setup with ${technicalScore}/100 technical score. M1 CHoCH confirmed. All criteria met.`,
      nextAction: 'Place limit order in entry zone. Set stop loss and take profit. Size position per risk rules.',
      invalidation: 'Cancel if price breaks stop loss zone before fill.',
      confidence,
    };
  }

  // WAIT conditions
  if (technicalScore >= 65) {
    const reason = !m1Confirmation
      ? `Technical score of ${technicalScore}/100 is good but M1 CHoCH confirmation is still pending.`
      : `Score is ${technicalScore}/100. Setup has potential but ${newsRisk === 'Medium' ? 'medium news risk warrants caution' : 'conditions are not fully optimal'}.`;

    return {
      decision: 'WAIT' as Decision,
      technicalScore,
      newsRisk,
      riskStatus,
      reason,
      nextAction: !m1Confirmation
        ? 'Set M1 alert for CHoCH. Do not enter until lower timeframe confirms direction.'
        : 'Monitor closely. Enter only when all confirmation criteria are met.',
      invalidation: 'Discard setup if price moves beyond invalidation level without triggering entry.',
      confidence: Math.max(30, technicalScore - 20),
    };
  }

  // Default NO_TRADE
  return {
    decision: 'NO_TRADE' as Decision,
    technicalScore,
    newsRisk,
    riskStatus,
    reason: `Technical score of ${technicalScore}/100 is below the minimum threshold of 65. Setup does not meet quality standards.`,
    nextAction: 'Look for higher-quality setups. Do not force trades with low confluence.',
    invalidation: 'No trade to invalidate.',
    confidence: 0,
  };
}
