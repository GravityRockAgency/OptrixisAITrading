'use client';

import { StatusBadge } from '@/components/ui/StatusBadge';

interface HeaderProps {
  title: string;
  subtitle?: string;
}

export function Header({ title, subtitle }: HeaderProps) {
  return (
    <header
      className="flex items-center justify-between px-6 py-4 border-b shrink-0"
      style={{ background: '#070A12', borderColor: '#24314F' }}
    >
      <div>
        <h1 className="text-lg font-bold leading-tight" style={{ color: '#F8FAFC' }}>
          {title}
        </h1>
        {subtitle && (
          <p className="text-xs mt-0.5" style={{ color: '#94A3B8' }}>
            {subtitle}
          </p>
        )}
      </div>

      <div className="flex items-center gap-2">
        <StatusBadge variant="Live" label="LIVE" pulse size="sm" />
        <StatusBadge variant="Session" label="New York Session" size="sm" />
        <StatusBadge variant="Allowed" label="Risk Allowed" size="sm" />
      </div>
    </header>
  );
}
