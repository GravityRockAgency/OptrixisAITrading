import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const channel = body.channel ?? 'telegram';

    // Placeholder — Telegram integration not yet implemented
    console.log(`[Notification Test] Channel: ${channel}`, body);

    if (channel === 'telegram') {
      const botToken = process.env.TELEGRAM_BOT_TOKEN;
      const chatId = process.env.TELEGRAM_CHAT_ID;

      if (!botToken || !chatId) {
        return NextResponse.json({
          success: false,
          message: 'Telegram not configured. Set TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID environment variables.',
        });
      }

      // TODO: Send actual Telegram message via API
      return NextResponse.json({
        success: true,
        message: 'Telegram test message would be sent here (not implemented yet)',
        channel,
      });
    }

    return NextResponse.json({
      success: false,
      message: `Channel "${channel}" not supported yet`,
    });
  } catch (error) {
    console.error('[Notification Test Error]', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}
