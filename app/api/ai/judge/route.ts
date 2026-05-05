import { NextRequest, NextResponse } from 'next/server';
import { runAIJudge } from '@/lib/ai-judge';
import type { NewsRisk, RiskStatus } from '@/lib/mock-data';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    // Validate required fields
    const required = ['technicalScore', 'newsRisk', 'riskStatus', 'riskReward', 'm1Confirmation', 'symbol', 'direction'];
    for (const field of required) {
      if (body[field] === undefined || body[field] === null) {
        return NextResponse.json(
          { error: `Missing required field: ${field}` },
          { status: 400 }
        );
      }
    }

    const validNewsRisks: NewsRisk[] = ['Low', 'Medium', 'High'];
    const validRiskStatuses: RiskStatus[] = ['Allowed', 'Warning', 'Blocked'];

    if (!validNewsRisks.includes(body.newsRisk)) {
      return NextResponse.json(
        { error: `Invalid newsRisk. Must be one of: ${validNewsRisks.join(', ')}` },
        { status: 400 }
      );
    }

    if (!validRiskStatuses.includes(body.riskStatus)) {
      return NextResponse.json(
        { error: `Invalid riskStatus. Must be one of: ${validRiskStatuses.join(', ')}` },
        { status: 400 }
      );
    }

    const result = runAIJudge({
      technicalScore: Number(body.technicalScore),
      newsRisk: body.newsRisk as NewsRisk,
      riskStatus: body.riskStatus as RiskStatus,
      riskReward: Number(body.riskReward),
      m1Confirmation: Boolean(body.m1Confirmation),
      symbol: String(body.symbol),
      direction: body.direction as 'LONG' | 'SHORT',
    });

    return NextResponse.json({
      result,
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error('[AI Judge Error]', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}
