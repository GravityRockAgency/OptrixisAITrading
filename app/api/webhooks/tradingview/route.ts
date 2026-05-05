import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  try {
    const secret = request.headers.get('x-webhook-secret');
    const expectedSecret = process.env.TRADINGVIEW_WEBHOOK_SECRET;

    if (expectedSecret && secret !== expectedSecret) {
      return NextResponse.json(
        { error: 'Unauthorized — invalid webhook secret' },
        { status: 401 }
      );
    }

    const payload = await request.json();

    // Log the webhook payload (replace with DB storage in production)
    console.log('[TradingView Webhook]', JSON.stringify(payload, null, 2));

    // TODO: Parse alert payload, create/update setup, trigger notifications

    return NextResponse.json({
      success: true,
      message: 'Webhook received',
      timestamp: new Date().toISOString(),
    });
  } catch (error) {
    console.error('[TradingView Webhook Error]', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}
