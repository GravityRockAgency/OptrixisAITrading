'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard,
  TrendingUp,
  Newspaper,
  Shield,
  BookOpen,
  Brain,
  Settings,
  Zap,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const navItems = [
  { href: '/dashboard', label: 'Market Radar', icon: LayoutDashboard },
  { href: '/setups/setup_001', label: 'Live Setup', icon: TrendingUp },
  { href: '/news', label: 'News Center', icon: Newspaper },
  { href: '/risk', label: 'Risk Manager', icon: Shield },
  { href: '/journal', label: 'Trading Journal', icon: BookOpen },
  { href: '/review', label: 'AI Review', icon: Brain },
  { href: '/settings', label: 'Settings', icon: Settings },
];

export function Sidebar() {
  const pathname = usePathname();

  return (
    <aside
      className="flex flex-col h-full w-60 shrink-0 border-r"
      style={{ background: '#090E1A', borderColor: '#24314F' }}
    >
      {/* Logo */}
      <div className="px-5 py-5 border-b" style={{ borderColor: '#24314F' }}>
        <div className="flex items-center gap-2.5">
          <div
            className="flex h-9 w-9 items-center justify-center rounded-lg"
            style={{ background: 'linear-gradient(135deg, #38BDF8, #A78BFA)' }}
          >
            <Zap className="h-5 w-5 text-white" />
          </div>
          <div>
            <p className="text-sm font-bold leading-tight" style={{ color: '#F8FAFC' }}>
              Tradyos EdgePilot
            </p>
            <p className="text-[10px] font-medium" style={{ color: '#64748B' }}>
              by Optrixis
            </p>
          </div>
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        {navItems.map(({ href, label, icon: Icon }) => {
          const dashboardActive = href === '/dashboard' && pathname === '/dashboard';
          const active = href === '/dashboard' ? dashboardActive : pathname.startsWith(`/${href.split('/')[1]}`);

          return (
            <Link
              key={href}
              href={href}
              className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150',
                active
                  ? 'text-white'
                  : 'hover:bg-white/5'
              )}
              style={
                active
                  ? { background: 'rgba(56, 189, 248, 0.12)', color: '#38BDF8' }
                  : { color: '#94A3B8' }
              }
            >
              <Icon
                className="h-4 w-4 shrink-0"
                style={active ? { color: '#38BDF8' } : { color: '#64748B' }}
              />
              {label}
              {active && (
                <span
                  className="ml-auto h-1.5 w-1.5 rounded-full"
                  style={{ background: '#38BDF8' }}
                />
              )}
            </Link>
          );
        })}
      </nav>

      {/* Prop Firm Status Card */}
      <div className="p-3 border-t" style={{ borderColor: '#24314F' }}>
        <div
          className="rounded-lg p-3 border"
          style={{ background: '#0D1220', borderColor: '#24314F' }}
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold" style={{ color: '#F8FAFC' }}>
              Prop Firm Mode
            </span>
            <span
              className="text-[10px] font-bold px-1.5 py-0.5 rounded uppercase"
              style={{ background: 'rgba(56, 189, 248, 0.15)', color: '#38BDF8' }}
            >
              Active
            </span>
          </div>
          <p className="text-xs mb-2.5" style={{ color: '#64748B' }}>
            Challenge 50K
          </p>
          <div className="flex items-center gap-1.5">
            <span
              className="h-2 w-2 rounded-full animate-pulse"
              style={{ background: '#22C55E' }}
            />
            <span className="text-xs font-medium" style={{ color: '#22C55E' }}>
              Protection ON
            </span>
          </div>
        </div>
      </div>
    </aside>
  );
}
