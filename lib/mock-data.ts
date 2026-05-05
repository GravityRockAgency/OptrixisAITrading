// ─── Types ───────────────────────────────────────────────────────────────────

export type Direction = 'LONG' | 'SHORT';
export type NewsRisk = 'Low' | 'Medium' | 'High';
export type RiskStatus = 'Allowed' | 'Warning' | 'Blocked';
export type Decision = 'GO' | 'WAIT' | 'NO_TRADE';
export type SetupStatus = 'detected' | 'taken' | 'skipped' | 'blocked' | 'invalidated';
export type ImpactLevel = 'Low' | 'Medium' | 'High';
export type TradeResult = 'Win' | 'Loss' | 'Breakeven' | 'Skipped' | 'Avoided';

export interface UserProfile {
  id: string;
  name: string;
  email: string;
  avatarUrl?: string;
  firmMode: 'Personal' | 'PropFirm';
  challengeLabel: string;
  createdAt: string;
}

export interface RiskSettings {
  accountSize: number;
  riskPerTrade: number;
  maxDailyLoss: number;
  maxTradesPerDay: number;
  maxConsecutiveLosses: number;
  minimumRR: number;
  protectionEnabled: boolean;
}

export interface Setup {
  id: string;
  symbol: string;
  timeframe: string;
  direction: Direction;
  setupType: string;
  entryLow: number;
  entryHigh: number;
  stopLoss: number;
  takeProfit: number;
  riskReward: number;
  technicalScore: number;
  newsRisk: NewsRisk;
  riskStatus: RiskStatus;
  decision: Decision;
  reason: string;
  nextAction: string;
  invalidation: string;
  status: SetupStatus;
  createdAt: string;
}

export interface Trade {
  id: string;
  date: string;
  asset: string;
  direction: Direction;
  setupType: string;
  aiDecision: Decision;
  score: number;
  entry: number;
  stopLoss: number;
  takeProfit: number;
  riskReward: number;
  result: TradeResult;
  rMultiple?: number;
  mistake?: string;
  aiComment: string;
  notes?: string;
}

export interface NewsEvent {
  id: string;
  time: string;
  currency: string;
  event: string;
  impact: ImpactLevel;
  affectedAssets: string[];
  actual?: string;
  forecast?: string;
  previous?: string;
  status: 'upcoming' | 'live' | 'released';
  minutesUntil: number;
}

export interface AIJudgeResult {
  decision: Decision;
  technicalScore: number;
  newsRisk: NewsRisk;
  riskStatus: RiskStatus;
  reason: string;
  nextAction: string;
  invalidation: string;
  confidence: number;
}

export interface AssetWatchlistItem {
  id: string;
  symbol: string;
  name: string;
  decision: Decision;
  setupType: string;
  newsRisk: NewsRisk;
  score: number;
  action: string;
  price: number;
  change: number;
  changePercent: number;
}

export interface WeeklyReview {
  weekLabel: string;
  startDate: string;
  endDate: string;
  totalSetups: number;
  taken: number;
  skipped: number;
  blocked: number;
  wins: number;
  losses: number;
  breakeven: number;
  winRate: number;
  avgRR: number;
  totalRMultiple: number;
  bestAsset: string;
  worstAsset: string;
  mainMistake: string;
  bestSetup: string;
  aiRecommendation: string;
  nextWeekFocus: string;
  dailyPnL: { day: string; pnl: number; rMultiple: number }[];
}

// ─── Mock Data ────────────────────────────────────────────────────────────────

export const mockUserProfile: UserProfile = {
  id: 'usr_001',
  name: 'Alex Morgan',
  email: 'alex@tradyos.com',
  firmMode: 'PropFirm',
  challengeLabel: 'Challenge 50K',
  createdAt: '2026-01-15T00:00:00Z',
};

export const mockRiskSettings: RiskSettings = {
  accountSize: 50000,
  riskPerTrade: 0.25,
  maxDailyLoss: 1.0,
  maxTradesPerDay: 2,
  maxConsecutiveLosses: 2,
  minimumRR: 2.0,
  protectionEnabled: true,
};

export const mockSetups: Setup[] = [
  {
    id: 'setup_001',
    symbol: 'XAUUSD',
    timeframe: 'M5',
    direction: 'LONG',
    setupType: 'OB + FVG',
    entryLow: 2325.50,
    entryHigh: 2327.20,
    stopLoss: 2323.80,
    takeProfit: 2332.00,
    riskReward: 2.6,
    technicalScore: 78,
    newsRisk: 'Medium',
    riskStatus: 'Allowed',
    decision: 'WAIT',
    reason: 'Strong OB + FVG confluence on M5. M1 CHoCH not yet confirmed. Awaiting bullish structure shift.',
    nextAction: 'Set M1 alert at 2325.80 for CHoCH confirmation. Watch for bearish rejection at 2327.50.',
    invalidation: 'Cancel setup if price breaks and closes below 2323.80 on M5.',
    status: 'detected',
    createdAt: '2026-05-05T14:30:00Z',
  },
  {
    id: 'setup_002',
    symbol: 'NQ',
    timeframe: 'M5',
    direction: 'SHORT',
    setupType: 'Sweep + OB',
    entryLow: 17820.0,
    entryHigh: 17845.0,
    stopLoss: 17870.0,
    takeProfit: 17720.0,
    riskReward: 2.0,
    technicalScore: 42,
    newsRisk: 'High',
    riskStatus: 'Blocked',
    decision: 'NO_TRADE',
    reason: 'USD CPI release in 12 minutes. High-impact news risk. Risk rules block trading within 15 minutes of FOMC/CPI events.',
    nextAction: 'Wait for post-news cooldown (30 minutes). Re-evaluate setup if structure holds.',
    invalidation: 'Discard if NQ breaks above 17870 after news.',
    status: 'blocked',
    createdAt: '2026-05-05T15:18:00Z',
  },
  {
    id: 'setup_003',
    symbol: 'EURUSD',
    timeframe: 'M15',
    direction: 'LONG',
    setupType: 'Sweep + CHoCH',
    entryLow: 1.08650,
    entryHigh: 1.08720,
    stopLoss: 1.08560,
    takeProfit: 1.08950,
    riskReward: 3.1,
    technicalScore: 86,
    newsRisk: 'Low',
    riskStatus: 'Allowed',
    decision: 'GO',
    reason: 'Clean liquidity sweep below 1.08600, confirmed M15 CHoCH bullish. HTF bias bullish. All confluence factors aligned.',
    nextAction: 'Validate risk parameters. Place limit order at 1.08680. Set SL and TP.',
    invalidation: 'Cancel if price returns below 1.08560 before fill.',
    status: 'detected',
    createdAt: '2026-05-05T13:45:00Z',
  },
  {
    id: 'setup_004',
    symbol: 'GBPUSD',
    timeframe: 'M5',
    direction: 'SHORT',
    setupType: 'OB Rejection',
    entryLow: 1.26350,
    entryHigh: 1.26420,
    stopLoss: 1.26500,
    takeProfit: 1.25980,
    riskReward: 2.4,
    technicalScore: 68,
    newsRisk: 'Low',
    riskStatus: 'Allowed',
    decision: 'WAIT',
    reason: 'Bearish OB identified but no retrace yet. Price has not returned to supply zone. Patience required.',
    nextAction: 'Wait for price to return to 1.26380–1.26420 zone. Set alert.',
    invalidation: 'Invalidated if price breaks above 1.26500 with strong momentum.',
    status: 'detected',
    createdAt: '2026-05-05T12:00:00Z',
  },
  {
    id: 'setup_005',
    symbol: 'USOIL',
    timeframe: 'H1',
    direction: 'SHORT',
    setupType: 'Resistance Rejection',
    entryLow: 78.20,
    entryHigh: 78.60,
    stopLoss: 79.00,
    takeProfit: 76.50,
    riskReward: 2.1,
    technicalScore: 33,
    newsRisk: 'High',
    riskStatus: 'Blocked',
    decision: 'NO_TRADE',
    reason: 'Oil Inventories report due in 45 minutes. Extremely volatile event. No setup qualifies near inventory release.',
    nextAction: 'Avoid entirely. Check setup validity after inventories and 30-min cooldown.',
    invalidation: 'Discard setup if inventories cause a gap beyond 79.00.',
    status: 'blocked',
    createdAt: '2026-05-05T14:00:00Z',
  },
];

export const mockCurrentSetup: Setup = mockSetups[0];

export const mockNewsEvents: NewsEvent[] = [
  {
    id: 'news_001',
    time: '15:30',
    currency: 'USD',
    event: 'CPI m/m',
    impact: 'High',
    affectedAssets: ['XAUUSD', 'EURUSD', 'GBPUSD', 'NQ', 'DXY'],
    forecast: '0.3%',
    previous: '0.4%',
    status: 'upcoming',
    minutesUntil: 12,
  },
  {
    id: 'news_002',
    time: '16:00',
    currency: 'USD',
    event: 'Fed Speech — Powell',
    impact: 'Medium',
    affectedAssets: ['XAUUSD', 'EURUSD', 'NQ', 'DXY'],
    forecast: undefined,
    previous: undefined,
    status: 'upcoming',
    minutesUntil: 42,
  },
  {
    id: 'news_003',
    time: '16:30',
    currency: 'USD',
    event: 'Crude Oil Inventories',
    impact: 'High',
    affectedAssets: ['USOIL', 'USDCAD'],
    forecast: '-2.1M',
    previous: '+1.8M',
    status: 'upcoming',
    minutesUntil: 72,
  },
  {
    id: 'news_004',
    time: '20:00',
    currency: 'USD',
    event: 'FOMC Meeting Minutes',
    impact: 'High',
    affectedAssets: ['XAUUSD', 'EURUSD', 'GBPUSD', 'NQ', 'SPX'],
    forecast: undefined,
    previous: undefined,
    status: 'upcoming',
    minutesUntil: 272,
  },
  {
    id: 'news_005',
    time: '13:15',
    currency: 'EUR',
    event: 'ECB Interest Rate Decision',
    impact: 'High',
    affectedAssets: ['EURUSD', 'EURGBP', 'EURJPY'],
    actual: '3.15%',
    forecast: '3.15%',
    previous: '3.40%',
    status: 'released',
    minutesUntil: -105,
  },
  {
    id: 'news_006',
    time: '14:00',
    currency: 'GBP',
    event: 'Manufacturing PMI',
    impact: 'Medium',
    affectedAssets: ['GBPUSD', 'GBPJPY'],
    actual: '48.2',
    forecast: '49.1',
    previous: '47.8',
    status: 'released',
    minutesUntil: -45,
  },
];

export const mockTrades: Trade[] = [
  {
    id: 'trade_001',
    date: '2026-05-05T10:22:00Z',
    asset: 'EURUSD',
    direction: 'LONG',
    setupType: 'Sweep + CHoCH',
    aiDecision: 'GO',
    score: 88,
    entry: 1.08320,
    stopLoss: 1.08220,
    takeProfit: 1.08620,
    riskReward: 3.0,
    result: 'Win',
    rMultiple: 2.1,
    aiComment: 'Clean execution. Entry precise, followed M1 CHoCH confirmation. Textbook SMC setup.',
    notes: 'Perfect entry. Held through minor retrace.',
  },
  {
    id: 'trade_002',
    date: '2026-05-04T15:30:00Z',
    asset: 'XAUUSD',
    direction: 'LONG',
    setupType: 'OB + FVG',
    aiDecision: 'WAIT',
    score: 74,
    entry: 0,
    stopLoss: 2318.50,
    takeProfit: 2334.20,
    riskReward: 2.8,
    result: 'Skipped',
    aiComment: 'Correct decision to skip. USD CPI was 8 minutes away. News risk was too high regardless of setup quality.',
    notes: 'Waited as instructed. Price dumped after CPI.',
  },
  {
    id: 'trade_003',
    date: '2026-05-03T16:05:00Z',
    asset: 'NQ',
    direction: 'SHORT',
    setupType: 'Supply Zone',
    aiDecision: 'NO_TRADE',
    score: 38,
    entry: 0,
    stopLoss: 17920.0,
    takeProfit: 17720.0,
    riskReward: 2.2,
    result: 'Avoided',
    aiComment: 'Avoided correctly. FOMC minutes caused 200-point spike. Risk system blocked trade. Well done.',
  },
  {
    id: 'trade_004',
    date: '2026-05-02T09:15:00Z',
    asset: 'GBPUSD',
    direction: 'SHORT',
    setupType: 'OB Rejection',
    aiDecision: 'GO',
    score: 82,
    entry: 1.25680,
    stopLoss: 1.25820,
    takeProfit: 1.25260,
    riskReward: 3.0,
    result: 'Win',
    rMultiple: 3.0,
    aiComment: 'Excellent setup. Clean OB rejection with strong H4 bearish bias. Held to full TP.',
    notes: 'Best trade of the week. Followed all rules.',
  },
  {
    id: 'trade_005',
    date: '2026-05-01T14:30:00Z',
    asset: 'XAUUSD',
    direction: 'SHORT',
    setupType: 'Distribution + Sweep',
    aiDecision: 'WAIT',
    score: 71,
    entry: 2328.00,
    stopLoss: 2331.20,
    takeProfit: 2319.50,
    riskReward: 2.7,
    result: 'Loss',
    rMultiple: -1.0,
    mistake: 'Entered without M1 CHoCH confirmation',
    aiComment: 'Early entry caused by impatience. Setup was valid but M1 confirmation was missing. Wait for CHoCH next time.',
    notes: 'Broke my own rule. Must wait for M1 signal.',
  },
  {
    id: 'trade_006',
    date: '2026-04-30T11:00:00Z',
    asset: 'EURUSD',
    direction: 'LONG',
    setupType: 'FVG Fill',
    aiDecision: 'GO',
    score: 91,
    entry: 1.07850,
    stopLoss: 1.07720,
    takeProfit: 1.08240,
    riskReward: 3.0,
    result: 'Win',
    rMultiple: 3.0,
    aiComment: 'Perfect execution. Highest-scoring setup of the month. All criteria met.',
  },
  {
    id: 'trade_007',
    date: '2026-04-29T15:45:00Z',
    asset: 'NQ',
    direction: 'LONG',
    setupType: 'Demand Zone',
    aiDecision: 'GO',
    score: 77,
    entry: 17640.0,
    stopLoss: 17580.0,
    takeProfit: 17820.0,
    riskReward: 3.0,
    result: 'Loss',
    rMultiple: -1.0,
    mistake: 'Ignored medium news risk (Fed speech)',
    aiComment: 'Score was acceptable but medium news risk should have prompted WAIT decision. Fed speech caused adverse move.',
    notes: 'Should have respected news filter more carefully.',
  },
  {
    id: 'trade_008',
    date: '2026-04-28T10:30:00Z',
    asset: 'USOIL',
    direction: 'SHORT',
    setupType: 'Resistance Rejection',
    aiDecision: 'WAIT',
    score: 65,
    entry: 0,
    stopLoss: 79.40,
    takeProfit: 76.80,
    riskReward: 2.6,
    result: 'Skipped',
    aiComment: 'Good discipline. Inventory risk was present. Skipping was the right call.',
  },
];

export const mockAssetWatchlist: AssetWatchlistItem[] = [
  {
    id: 'asset_001',
    symbol: 'XAUUSD',
    name: 'Gold / US Dollar',
    decision: 'WAIT',
    setupType: 'OB + FVG',
    newsRisk: 'Medium',
    score: 78,
    action: 'Watch M1 CHoCH',
    price: 2326.40,
    change: 4.20,
    changePercent: 0.18,
  },
  {
    id: 'asset_002',
    symbol: 'NQ',
    name: 'Nasdaq 100 Futures',
    decision: 'NO_TRADE',
    setupType: 'News too close',
    newsRisk: 'High',
    score: 42,
    action: 'Blocked — Wait news',
    price: 17832.50,
    change: -124.75,
    changePercent: -0.69,
  },
  {
    id: 'asset_003',
    symbol: 'EURUSD',
    name: 'Euro / US Dollar',
    decision: 'GO',
    setupType: 'Sweep + CHoCH',
    newsRisk: 'Low',
    score: 86,
    action: 'Validate risk',
    price: 1.08685,
    change: 0.00285,
    changePercent: 0.26,
  },
  {
    id: 'asset_004',
    symbol: 'GBPUSD',
    name: 'British Pound / US Dollar',
    decision: 'WAIT',
    setupType: 'No retrace yet',
    newsRisk: 'Low',
    score: 68,
    action: 'Wait pullback',
    price: 1.26385,
    change: -0.00142,
    changePercent: -0.11,
  },
  {
    id: 'asset_005',
    symbol: 'USOIL',
    name: 'US Crude Oil',
    decision: 'NO_TRADE',
    setupType: 'Inventories soon',
    newsRisk: 'High',
    score: 33,
    action: 'Avoid entirely',
    price: 78.42,
    change: -0.58,
    changePercent: -0.73,
  },
];

export const mockWeeklyReview: WeeklyReview = {
  weekLabel: 'Week of Apr 28 – May 2, 2026',
  startDate: '2026-04-28',
  endDate: '2026-05-02',
  totalSetups: 12,
  taken: 5,
  skipped: 4,
  blocked: 3,
  wins: 3,
  losses: 2,
  breakeven: 0,
  winRate: 60,
  avgRR: 2.4,
  totalRMultiple: 4.1,
  bestAsset: 'GBPUSD',
  worstAsset: 'NQ',
  mainMistake: 'Early entry without M1 CHoCH confirmation (2 occurrences)',
  bestSetup: 'GBPUSD Short – OB Rejection on May 2 (+3.0R)',
  aiRecommendation:
    'Focus on patience. You have strong setup identification but you are entering too early. The M1 CHoCH rule exists for a reason — respect it. Your win rate improves to 80% when you wait for confirmation.',
  nextWeekFocus: 'Only enter after M1 CHoCH confirmation. No exceptions. Review GBPUSD execution as the model trade.',
  dailyPnL: [
    { day: 'Mon', pnl: -125, rMultiple: -1.0 },
    { day: 'Tue', pnl: 187.5, rMultiple: 1.5 },
    { day: 'Wed', pnl: 0, rMultiple: 0 },
    { day: 'Thu', pnl: -125, rMultiple: -1.0 },
    { day: 'Fri', pnl: 375, rMultiple: 3.0 },
  ],
};

// ─── Chart Data ───────────────────────────────────────────────────────────────

export interface CandleData {
  time: string;
  open: number;
  high: number;
  low: number;
  close: number;
  volume: number;
}

export function generateXAUUSDCandles(): CandleData[] {
  const basePrice = 2322.0;
  const candles: CandleData[] = [];
  let currentPrice = basePrice;

  const times = [
    '13:00', '13:05', '13:10', '13:15', '13:20', '13:25', '13:30', '13:35',
    '13:40', '13:45', '13:50', '13:55', '14:00', '14:05', '14:10', '14:15',
    '14:20', '14:25', '14:30', '14:35', '14:40', '14:45', '14:50', '14:55',
    '15:00', '15:05', '15:10', '15:15', '15:20', '15:25', '15:30', '15:35',
    '15:40', '15:45', '15:50', '15:55', '16:00', '16:05', '16:10', '16:15',
    '16:20', '16:25', '16:30', '16:35', '16:40', '16:45', '16:50', '16:55',
    '17:00', '17:05', '17:10', '17:15', '17:20', '17:25', '17:30', '17:35',
    '17:40', '17:45', '17:50', '17:55', '18:00', '18:05',
  ];

  // Predefined realistic price movement pattern
  const priceDeltas = [
    1.2, -0.8, 2.1, 1.5, -1.2, -2.3, 0.4, 1.8,
    -0.6, 0.9, 1.1, -0.3, -1.8, -2.5, -1.4, 0.7,
    1.3, 2.2, -0.5, -0.9, -1.1, -3.2, -2.8, -1.5, // Sweep down to OB
    0.8, 1.6, 2.4, 1.9, 0.6, -0.4, 1.2, 2.8,       // Bounce from OB
    1.4, 0.8, -0.2, 1.1, 0.9, 2.0, 1.3, -0.6,       // Rally into FVG
    1.7, 0.4, -0.8, 1.5, 2.1, 1.8, 0.3, -0.5,       // Consolidation
    1.2, 1.9, 2.4, 1.6, 0.8, 1.3, -0.4, 1.1,        // Continuation
    0.7, 1.4, 2.2, 1.5, 0.6, 0.9,
  ];

  for (let i = 0; i < times.length; i++) {
    const delta = priceDeltas[i] || 0;
    const open = currentPrice;
    const close = open + delta;
    const bodySize = Math.abs(close - open);
    const upperWick = bodySize * (0.3 + Math.random() * 0.7);
    const lowerWick = bodySize * (0.2 + Math.random() * 0.6);
    const high = Math.max(open, close) + upperWick;
    const low = Math.min(open, close) - lowerWick;

    candles.push({
      time: times[i],
      open: parseFloat(open.toFixed(2)),
      high: parseFloat(high.toFixed(2)),
      low: parseFloat(low.toFixed(2)),
      close: parseFloat(close.toFixed(2)),
      volume: Math.floor(800 + Math.random() * 2400),
    });

    currentPrice = close;
  }

  return candles;
}
