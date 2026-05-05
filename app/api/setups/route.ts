import { NextResponse } from 'next/server';
import { mockSetups } from '@/lib/mock-data';

export async function GET() {
  return NextResponse.json({
    setups: mockSetups,
    count: mockSetups.length,
    timestamp: new Date().toISOString(),
  });
}
