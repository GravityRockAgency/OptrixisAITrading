'use client';

import { cn } from '@/lib/utils';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

interface MetricCardProps {
  label: string;
  value: string | number;
  subValue?: string;
  trend?: 'up' | 'down' | 'neutral';
  trendLabel?: string;
  icon?: LucideIcon;
  iconColor?: string;
  accent?: 'blue' | 'green' | 'red' | 'amber' | 'purple';
  className?: string;
}

const accentConfig = {
  blue: { border: 'border-sky-500/40', icon: 'text-sky-400', value: 'text-sky-400' },
  green: { border: 'border-green-500/40', icon: 'text-green-400', value: 'text-green-400' },
  red: { border: 'border-red-500/40', icon: 'text-red-400', value: 'text-red-400' },
  amber: { border: 'border-amber-500/40', icon: 'text-amber-400', value: 'text-amber-400' },
  purple: { border: 'border-purple-500/40', icon: 'text-purple-400', value: 'text-purple-400' },
};

export function MetricCard({
  label,
  value,
  subValue,
  trend,
  trendLabel,
  icon: Icon,
  accent,
  className,
}: MetricCardProps) {
  const ac = accent ? accentConfig[accent] : null;

  return (
    <div
      className={cn(
        'rounded-xl border p-4 flex flex-col gap-2',
        'border-[#24314F]',
        ac?.border,
        className
      )}
      style={{ background: '#101828' }}
    >
      <div className="flex items-center justify-between">
        <span className="text-xs font-medium uppercase tracking-wider" style={{ color: '#64748B' }}>
          {label}
        </span>
        {Icon && (
          <Icon
            className={cn('h-4 w-4', ac?.icon ?? 'text-[#64748B]')}
          />
        )}
      </div>

      <div className="flex items-end justify-between gap-2">
        <span
          className={cn(
            'text-2xl font-bold leading-none',
            ac?.value ?? 'text-[#F8FAFC]'
          )}
        >
          {value}
        </span>

        {trend && (
          <div
            className={cn(
              'flex items-center gap-1 text-xs font-medium rounded-full px-2 py-0.5',
              trend === 'up' && 'text-green-400 bg-green-500/10',
              trend === 'down' && 'text-red-400 bg-red-500/10',
              trend === 'neutral' && 'text-[#94A3B8] bg-white/5'
            )}
          >
            {trend === 'up' && <TrendingUp className="h-3 w-3" />}
            {trend === 'down' && <TrendingDown className="h-3 w-3" />}
            {trend === 'neutral' && <Minus className="h-3 w-3" />}
            {trendLabel}
          </div>
        )}
      </div>

      {subValue && (
        <p className="text-xs" style={{ color: '#94A3B8' }}>
          {subValue}
        </p>
      )}
    </div>
  );
}
