'use client';

import {
  ComposedChart,
  XAxis,
  YAxis,
  CartesianGrid,
  ReferenceLine,
  ReferenceArea,
  ResponsiveContainer,
  Bar,
  Tooltip,
} from 'recharts';
import { generateXAUUSDCandles, type CandleData } from '@/lib/mock-data';

const candles = generateXAUUSDCandles();

interface CustomTooltipProps {
  active?: boolean;
  payload?: Array<{ payload: CandleData }>;
  label?: string;
}

function CustomTooltip({ active, payload, label }: CustomTooltipProps) {
  if (!active || !payload?.length) return null;
  const d = payload[0]?.payload;
  if (!d) return null;
  const isGreen = d.close >= d.open;
  return (
    <div
      className="rounded-lg border px-3 py-2 text-xs"
      style={{ background: '#101828', borderColor: '#24314F', color: '#F8FAFC' }}
    >
      <p className="font-semibold mb-1" style={{ color: '#94A3B8' }}>{label}</p>
      <div className="grid grid-cols-2 gap-x-3 gap-y-0.5">
        <span style={{ color: '#64748B' }}>O</span>
        <span style={{ color: isGreen ? '#22C55E' : '#EF4444' }}>{d.open.toFixed(2)}</span>
        <span style={{ color: '#64748B' }}>H</span>
        <span style={{ color: '#22C55E' }}>{d.high.toFixed(2)}</span>
        <span style={{ color: '#64748B' }}>L</span>
        <span style={{ color: '#EF4444' }}>{d.low.toFixed(2)}</span>
        <span style={{ color: '#64748B' }}>C</span>
        <span style={{ color: isGreen ? '#22C55E' : '#EF4444' }}>{d.close.toFixed(2)}</span>
      </div>
    </div>
  );
}

type ChartCandle = CandleData & {
  bodyLow: number;
  bodyHigh: number;
  bodyRange: [number, number];
  isGreen: boolean;
};

function transformCandlesForChart(rawCandles: CandleData[]): ChartCandle[] {
  return rawCandles.map((c) => ({
    ...c,
    bodyLow: Math.min(c.open, c.close),
    bodyHigh: Math.max(c.open, c.close),
    bodyRange: [Math.min(c.open, c.close), Math.max(c.open, c.close)] as [number, number],
    isGreen: c.close >= c.open,
  }));
}

const chartData = transformCandlesForChart(candles);

const allLows = candles.map((c) => c.low);
const allHighs = candles.map((c) => c.high);
const yMin = Math.min(...allLows) - 1;
const yMax = Math.max(...allHighs) + 1;

const ENTRY_LOW = 2325.5;
const ENTRY_HIGH = 2327.2;
const STOP_LOSS = 2323.8;
const TAKE_PROFIT = 2332.0;
const OB_LOW = 2320.0;
const OB_HIGH = 2323.5;
const FVG_LOW = 2324.8;
const FVG_HIGH = 2326.2;

// Single custom shape that draws both wick lines and candle body
function CandleShape(props: {
  x?: number;
  y?: number;
  width?: number;
  height?: number;
  payload?: ChartCandle;
}) {
  const { x = 0, y = 0, width = 0, height = 0, payload } = props;
  if (!payload || width === 0) return null;

  const color = payload.isGreen ? '#22C55E' : '#EF4444';
  const cx = x + width / 2;
  const bw = Math.max(width - 2, 2);

  // Range bar: y = top of body in pixels (= bodyHigh in data), y+|height| = bottom (= bodyLow)
  const bodyTop = y;
  const bodyH = Math.max(Math.abs(height), 1);
  const bodyBottom = bodyTop + bodyH;

  // Compute wick pixel lengths proportionally from body span
  const bodyDataRange = payload.bodyHigh - payload.bodyLow;
  let upperWickPx = 0;
  let lowerWickPx = 0;

  if (bodyDataRange > 0.001) {
    const pxPerUnit = bodyH / bodyDataRange;
    upperWickPx = (payload.high - payload.bodyHigh) * pxPerUnit;
    lowerWickPx = (payload.bodyLow - payload.low) * pxPerUnit;
  } else {
    // Doji candle: approximate from overall price range
    const totalRange = payload.high - payload.low;
    if (totalRange > 0) {
      const approx = 6 / totalRange;
      upperWickPx = (payload.high - payload.bodyHigh) * approx;
      lowerWickPx = (payload.bodyLow - payload.low) * approx;
    }
  }

  return (
    <g>
      {upperWickPx > 0 && (
        <line
          x1={cx} y1={bodyTop - upperWickPx}
          x2={cx} y2={bodyTop}
          stroke={color} strokeWidth={1}
        />
      )}
      {lowerWickPx > 0 && (
        <line
          x1={cx} y1={bodyBottom}
          x2={cx} y2={bodyBottom + lowerWickPx}
          stroke={color} strokeWidth={1}
        />
      )}
      <rect
        x={x + (width - bw) / 2}
        y={bodyTop}
        width={bw}
        height={bodyH}
        fill={color}
        fillOpacity={payload.isGreen ? 0.9 : 0.85}
      />
    </g>
  );
}

// Recharts passes viewBox to label elements, not x/y directly
function ReferenceLabel({
  viewBox,
  value,
  fill,
}: {
  viewBox?: { x: number; y: number; width: number; height: number };
  value: string;
  fill: string;
}) {
  const lx = (viewBox?.x ?? 0) + (viewBox?.width ?? 0) - 4;
  const ly = (viewBox?.y ?? 0) - 5;
  return (
    <text x={lx} y={ly} fill={fill} fontSize={9} fontWeight={600} textAnchor="end">
      {value}
    </text>
  );
}

export function TradingChartMock() {
  return (
    <div
      className="rounded-xl border flex flex-col"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      {/* Header */}
      <div
        className="flex items-center justify-between px-4 py-3 border-b"
        style={{ borderColor: '#24314F' }}
      >
        <div className="flex items-center gap-3">
          <span className="text-sm font-bold" style={{ color: '#F8FAFC' }}>XAUUSD</span>
          <span
            className="text-xs font-semibold px-2 py-0.5 rounded"
            style={{ background: 'rgba(56, 189, 248, 0.12)', color: '#38BDF8' }}
          >
            M5
          </span>
          <span className="text-xs" style={{ color: '#94A3B8' }}>Gold / US Dollar</span>
        </div>
        <div className="flex items-center gap-3 text-xs" style={{ color: '#64748B' }}>
          <span><span style={{ color: '#22C55E' }}>●</span> OB Zone</span>
          <span><span style={{ color: '#A78BFA' }}>●</span> FVG</span>
          <span><span style={{ color: '#F59E0B' }}>—</span> Entry</span>
          <span><span style={{ color: '#EF4444' }}>—</span> SL</span>
          <span><span style={{ color: '#22C55E' }}>—</span> TP</span>
        </div>
      </div>

      {/* Chart body */}
      <div className="flex-1 p-2" style={{ height: 340 }}>
        <ResponsiveContainer width="100%" height="100%">
          <ComposedChart
            data={chartData}
            margin={{ top: 10, right: 68, bottom: 5, left: 10 }}
            barCategoryGap={1}
          >
            <CartesianGrid
              strokeDasharray="3 3"
              stroke="#24314F"
              strokeOpacity={0.4}
              vertical={false}
            />
            <XAxis
              dataKey="time"
              tick={{ fill: '#64748B', fontSize: 9 }}
              tickLine={false}
              axisLine={{ stroke: '#24314F' }}
              interval={7}
            />
            <YAxis
              orientation="right"
              domain={[yMin, yMax]}
              tick={{ fill: '#64748B', fontSize: 9 }}
              tickLine={false}
              axisLine={false}
              tickFormatter={(v: number) => v.toFixed(1)}
              width={58}
            />
            <Tooltip content={<CustomTooltip />} />

            {/* OB zone */}
            <ReferenceArea
              y1={OB_LOW} y2={OB_HIGH}
              fill="#22C55E" fillOpacity={0.08}
              stroke="#22C55E" strokeOpacity={0.25} strokeWidth={1}
            />
            {/* FVG zone */}
            <ReferenceArea
              y1={FVG_LOW} y2={FVG_HIGH}
              fill="#A78BFA" fillOpacity={0.1}
              stroke="#A78BFA" strokeOpacity={0.3} strokeWidth={1}
            />
            {/* Entry zone */}
            <ReferenceArea
              y1={ENTRY_LOW} y2={ENTRY_HIGH}
              fill="#F59E0B" fillOpacity={0.07}
              stroke="#F59E0B" strokeOpacity={0.4}
              strokeDasharray="4 4" strokeWidth={1}
            />

            {/* Stop Loss */}
            <ReferenceLine
              y={STOP_LOSS}
              stroke="#EF4444" strokeDasharray="5 3" strokeWidth={1.5}
              label={<ReferenceLabel value={`SL ${STOP_LOSS}`} fill="#EF4444" />}
            />
            {/* Take Profit */}
            <ReferenceLine
              y={TAKE_PROFIT}
              stroke="#22C55E" strokeDasharray="5 3" strokeWidth={1.5}
              label={<ReferenceLabel value={`TP ${TAKE_PROFIT}`} fill="#22C55E" />}
            />
            {/* Entry midline */}
            <ReferenceLine
              y={(ENTRY_LOW + ENTRY_HIGH) / 2}
              stroke="#F59E0B" strokeDasharray="5 3" strokeWidth={1.5}
              label={<ReferenceLabel value="Entry" fill="#F59E0B" />}
            />

            {/* Candlesticks: single Bar with custom shape renders body + wicks */}
            <Bar
              dataKey="bodyRange"
              barSize={8}
              isAnimationActive={false}
              shape={(props: unknown) =>
                <CandleShape {...(props as Parameters<typeof CandleShape>[0])} />
              }
            />
          </ComposedChart>
        </ResponsiveContainer>
      </div>

      {/* Footer */}
      <div
        className="flex items-center justify-between px-4 py-2.5 border-t text-xs"
        style={{ borderColor: '#24314F', color: '#64748B' }}
      >
        <div className="flex items-center gap-4">
          <span>OB: <span style={{ color: '#22C55E' }}>{OB_LOW}–{OB_HIGH}</span></span>
          <span>FVG: <span style={{ color: '#A78BFA' }}>{FVG_LOW}–{FVG_HIGH}</span></span>
        </div>
        <span className="font-bold" style={{ color: '#38BDF8' }}>RR 1:2.6</span>
      </div>
    </div>
  );
}
