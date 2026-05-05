'use client';

import { cn } from '@/lib/utils';
import type { Decision, NewsRisk, RiskStatus } from '@/lib/mock-data';

type BadgeVariant = Decision | NewsRisk | RiskStatus | 'Live' | 'Session' | 'Info';

interface StatusBadgeProps {
  variant: BadgeVariant;
  label?: string;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
  pulse?: boolean;
}

const variantConfig: Record<BadgeVariant, { bg: string; text: string; border: string; dot: string }> = {
  GO: {
    bg: 'bg-green-500/15',
    text: 'text-green-400',
    border: 'border-green-500/30',
    dot: 'bg-green-400',
  },
  WAIT: {
    bg: 'bg-amber-500/15',
    text: 'text-amber-400',
    border: 'border-amber-500/30',
    dot: 'bg-amber-400',
  },
  NO_TRADE: {
    bg: 'bg-red-500/15',
    text: 'text-red-400',
    border: 'border-red-500/30',
    dot: 'bg-red-400',
  },
  Low: {
    bg: 'bg-sky-500/15',
    text: 'text-sky-400',
    border: 'border-sky-500/30',
    dot: 'bg-sky-400',
  },
  Medium: {
    bg: 'bg-amber-500/15',
    text: 'text-amber-400',
    border: 'border-amber-500/30',
    dot: 'bg-amber-400',
  },
  High: {
    bg: 'bg-red-500/15',
    text: 'text-red-400',
    border: 'border-red-500/30',
    dot: 'bg-red-400',
  },
  Allowed: {
    bg: 'bg-green-500/15',
    text: 'text-green-400',
    border: 'border-green-500/30',
    dot: 'bg-green-400',
  },
  Warning: {
    bg: 'bg-amber-500/15',
    text: 'text-amber-400',
    border: 'border-amber-500/30',
    dot: 'bg-amber-400',
  },
  Blocked: {
    bg: 'bg-red-500/15',
    text: 'text-red-400',
    border: 'border-red-500/30',
    dot: 'bg-red-400',
  },
  Live: {
    bg: 'bg-green-500/15',
    text: 'text-green-400',
    border: 'border-green-500/30',
    dot: 'bg-green-400',
  },
  Session: {
    bg: 'bg-sky-500/15',
    text: 'text-sky-400',
    border: 'border-sky-500/30',
    dot: 'bg-sky-400',
  },
  Info: {
    bg: 'bg-purple-500/15',
    text: 'text-purple-400',
    border: 'border-purple-500/30',
    dot: 'bg-purple-400',
  },
};

const sizeConfig = {
  sm: 'text-[10px] px-2 py-0.5 gap-1',
  md: 'text-xs px-2.5 py-1 gap-1.5',
  lg: 'text-sm px-3 py-1.5 gap-2',
};

export function StatusBadge({
  variant,
  label,
  size = 'md',
  className,
  pulse = false,
}: StatusBadgeProps) {
  const config = variantConfig[variant] ?? variantConfig.Info;
  const displayLabel = label ?? (variant === 'NO_TRADE' ? 'NO TRADE' : variant);

  return (
    <span
      className={cn(
        'inline-flex items-center rounded-full border font-semibold uppercase tracking-wide',
        config.bg,
        config.text,
        config.border,
        sizeConfig[size],
        className
      )}
    >
      <span
        className={cn(
          'rounded-full',
          size === 'sm' ? 'h-1.5 w-1.5' : size === 'lg' ? 'h-2.5 w-2.5' : 'h-2 w-2',
          config.dot,
          pulse && 'animate-pulse'
        )}
      />
      {displayLabel}
    </span>
  );
}
