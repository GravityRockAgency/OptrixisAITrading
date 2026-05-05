import { NextRequest, NextResponse } from 'next/server';
import { mockSetups } from '@/lib/mock-data';
import type { SetupStatus } from '@/lib/mock-data';

interface RouteContext {
  params: Promise<{ id: string }>;
}

export async function GET(
  _request: NextRequest,
  context: RouteContext
) {
  const { id } = await context.params;
  const setup = mockSetups.find((s) => s.id === id);

  if (!setup) {
    return NextResponse.json(
      { error: `Setup with id "${id}" not found` },
      { status: 404 }
    );
  }

  return NextResponse.json({ setup });
}

export async function PATCH(
  request: NextRequest,
  context: RouteContext
) {
  const { id } = await context.params;
  const setup = mockSetups.find((s) => s.id === id);

  if (!setup) {
    return NextResponse.json(
      { error: `Setup with id "${id}" not found` },
      { status: 404 }
    );
  }

  const body = await request.json();
  const allowedStatuses: SetupStatus[] = ['detected', 'taken', 'skipped', 'blocked', 'invalidated'];

  if (body.status && !allowedStatuses.includes(body.status)) {
    return NextResponse.json(
      { error: `Invalid status. Must be one of: ${allowedStatuses.join(', ')}` },
      { status: 400 }
    );
  }

  // In production, update the DB record here
  const updated = { ...setup, ...body, updatedAt: new Date().toISOString() };

  return NextResponse.json({
    setup: updated,
    message: 'Setup updated successfully',
  });
}
