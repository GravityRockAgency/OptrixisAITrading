import { NextResponse } from 'next/server';
import { mockNewsEvents } from '@/lib/mock-data';
import { assessNewsRisk } from '@/lib/news-guard';

export async function GET() {
  const riskAssessment = assessNewsRisk(mockNewsEvents);

  return NextResponse.json({
    events: mockNewsEvents,
    count: mockNewsEvents.length,
    riskAssessment,
    date: new Date().toISOString().split('T')[0],
    timestamp: new Date().toISOString(),
  });
}
