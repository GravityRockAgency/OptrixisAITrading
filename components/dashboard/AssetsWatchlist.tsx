'use client';

import { TrendingUp, TrendingDown } from 'lucide-react';
import { StatusBadge } from '@/components/ui/StatusBadge';
import { mockAssetWatchlist, type AssetWatchlistItem } from '@/lib/mock-data';

function AssetRow({ item }: { item: AssetWatchlistItem }) {
  const isPositive = item.change >= 0;
  return (
    <tr
      className="border-b hover:bg-white/[0.02] transition-colors"
      style={{ borderColor: '#24314F' }}
    >
      {/* Asset */}
      <td className="px-4 py-3">
        <div>
          <p className="text-sm font-bold" style={{ color: '#F8FAFC' }}>
            {item.symbol}
          </p>
          <p className="text-[10px]" style={{ color: '#64748B' }}>
            {item.name}
          </p>
        </div>
      </td>

      {/* Price */}
      <td className="px-3 py-3 text-right">
        <p className="text-sm font-semibold tabular-nums" style={{ color: '#F8FAFC' }}>
          {item.symbol === 'EURUSD' || item.symbol === 'GBPUSD'
            ? item.price.toFixed(5)
            : item.price.toFixed(2)}
        </p>
        <div
          className={`flex items-center justify-end gap-0.5 text-[10px] font-medium`}
          style={{ color: isPositive ? '#22C55E' : '#EF4444' }}
        >
          {isPositive ? <TrendingUp className="h-2.5 w-2.5" /> : <TrendingDown className="h-2.5 w-2.5" />}
          {Math.abs(item.changePercent).toFixed(2)}%
        </div>
      </td>

      {/* Decision */}
      <td className="px-3 py-3">
        <StatusBadge variant={item.decision} size="sm" />
      </td>

      {/* Setup */}
      <td className="px-3 py-3 hidden md:table-cell">
        <span className="text-xs" style={{ color: '#94A3B8' }}>
          {item.setupType}
        </span>
      </td>

      {/* News Risk */}
      <td className="px-3 py-3 hidden lg:table-cell">
        <StatusBadge variant={item.newsRisk} size="sm" />
      </td>

      {/* Score */}
      <td className="px-3 py-3 hidden md:table-cell">
        <div className="flex items-center gap-2">
          <div
            className="h-1.5 w-16 rounded-full overflow-hidden"
            style={{ background: '#0D1220' }}
          >
            <div
              className="h-full rounded-full"
              style={{
                width: `${item.score}%`,
                background:
                  item.score >= 80
                    ? '#22C55E'
                    : item.score >= 65
                    ? '#F59E0B'
                    : '#EF4444',
              }}
            />
          </div>
          <span
            className="text-xs font-semibold tabular-nums"
            style={{
              color:
                item.score >= 80
                  ? '#22C55E'
                  : item.score >= 65
                  ? '#F59E0B'
                  : '#EF4444',
            }}
          >
            {item.score}
          </span>
        </div>
      </td>

      {/* Action */}
      <td className="px-3 py-3">
        <span className="text-xs" style={{ color: '#94A3B8' }}>
          {item.action}
        </span>
      </td>
    </tr>
  );
}

export function AssetsWatchlist() {
  return (
    <div
      className="rounded-xl border overflow-hidden"
      style={{ background: '#101828', borderColor: '#24314F' }}
    >
      <div
        className="flex items-center justify-between px-4 py-3 border-b"
        style={{ borderColor: '#24314F' }}
      >
        <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
          Assets Watchlist
        </h3>
        <span className="text-xs" style={{ color: '#64748B' }}>
          {mockAssetWatchlist.length} assets
        </span>
      </div>

      <div className="overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr style={{ borderBottom: `1px solid #24314F` }}>
              {['Asset', 'Price', 'Decision', 'Setup', 'News Risk', 'Score', 'Action'].map(
                (col) => (
                  <th
                    key={col}
                    className={`px-3 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider ${
                      col === 'Asset' ? 'px-4' : ''
                    } ${col === 'Setup' ? 'hidden md:table-cell' : ''} ${
                      col === 'News Risk' ? 'hidden lg:table-cell' : col === 'Score' ? 'hidden md:table-cell' : ''
                    } ${col === 'Price' ? 'text-right' : ''}`}
                    style={{ color: '#64748B' }}
                  >
                    {col}
                  </th>
                )
              )}
            </tr>
          </thead>
          <tbody>
            {mockAssetWatchlist.map((item) => (
              <AssetRow key={item.id} item={item} />
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
