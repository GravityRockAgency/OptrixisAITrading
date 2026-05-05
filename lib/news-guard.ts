import type { NewsRisk, NewsEvent } from './mock-data';

export interface NewsGuardResult {
  riskLevel: NewsRisk;
  reason: string;
  affectedAssets: string[];
  nextHighImpactEvent?: NewsEvent;
  minutesUntilClear: number;
  recommendation: string;
}

export function assessNewsRisk(
  events: NewsEvent[]
): NewsGuardResult {
  const upcomingHighImpact = events
    .filter((e) => e.impact === 'High' && e.minutesUntil > 0)
    .sort((a, b) => a.minutesUntil - b.minutesUntil);

  const recentHighImpact = events
    .filter((e) => e.impact === 'High' && e.minutesUntil <= 0 && e.minutesUntil > -30)
    .sort((a, b) => b.minutesUntil - a.minutesUntil);

  const nextEvent = upcomingHighImpact[0];

  // Post-news cooldown checks
  if (recentHighImpact.length > 0) {
    const mostRecent = recentHighImpact[0];
    const minutesAgo = Math.abs(mostRecent.minutesUntil);

    if (minutesAgo <= 5) {
      return {
        riskLevel: 'High',
        reason: `High-impact event just released ${minutesAgo} minute(s) ago. Market is highly volatile.`,
        affectedAssets: mostRecent.affectedAssets,
        nextHighImpactEvent: nextEvent,
        minutesUntilClear: 30 - minutesAgo,
        recommendation: 'Wait at least 30 minutes after the event before considering any trades.',
      };
    }

    if (minutesAgo <= 15) {
      return {
        riskLevel: 'Medium',
        reason: `High-impact event released ${minutesAgo} minutes ago. Volatility may still be elevated.`,
        affectedAssets: mostRecent.affectedAssets,
        nextHighImpactEvent: nextEvent,
        minutesUntilClear: 30 - minutesAgo,
        recommendation: 'Proceed with extreme caution. Widen stops if trading.',
      };
    }
  }

  // Pre-news checks
  if (nextEvent) {
    if (nextEvent.minutesUntil <= 15) {
      return {
        riskLevel: 'High',
        reason: `${nextEvent.event} in ${nextEvent.minutesUntil} minute(s). Do not enter new trades.`,
        affectedAssets: nextEvent.affectedAssets,
        nextHighImpactEvent: nextEvent,
        minutesUntilClear: nextEvent.minutesUntil + 30,
        recommendation: 'Close open positions or tighten stops. Do not open new trades.',
      };
    }

    if (nextEvent.minutesUntil <= 30) {
      return {
        riskLevel: 'Medium',
        reason: `${nextEvent.event} in ${nextEvent.minutesUntil} minutes. Exercise caution.`,
        affectedAssets: nextEvent.affectedAssets,
        nextHighImpactEvent: nextEvent,
        minutesUntilClear: nextEvent.minutesUntil + 30,
        recommendation: 'Only take setups with very high conviction. Consider sitting out.',
      };
    }
  }

  return {
    riskLevel: 'Low',
    reason: 'No high-impact news within the next 30 minutes. Market conditions are stable.',
    affectedAssets: [],
    nextHighImpactEvent: nextEvent,
    minutesUntilClear: 0,
    recommendation: 'Normal trading conditions. Follow standard entry criteria.',
  };
}
