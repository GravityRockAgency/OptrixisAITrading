'use client';

import { useState } from 'react';
import { AppLayout } from '@/components/layout/AppLayout';
import { mockUserProfile, mockRiskSettings } from '@/lib/mock-data';
import { AlertTriangle, Save, User, BarChart2, Shield, Newspaper, Bell, List } from 'lucide-react';

type Tab = 'profile' | 'assets' | 'strategy' | 'risk' | 'news' | 'notifications';

const TABS: { id: Tab; label: string; icon: React.ElementType }[] = [
  { id: 'profile', label: 'Profile', icon: User },
  { id: 'assets', label: 'Assets', icon: List },
  { id: 'strategy', label: 'Strategy Rules', icon: BarChart2 },
  { id: 'risk', label: 'Risk Settings', icon: Shield },
  { id: 'news', label: 'News Settings', icon: Newspaper },
  { id: 'notifications', label: 'Notifications', icon: Bell },
];

const WATCHED_ASSETS = ['XAUUSD', 'EURUSD', 'NQ', 'GBPUSD', 'USOIL', 'USDJPY', 'SPX500', 'BTCUSD'];

function ToggleSwitch({ enabled, onToggle }: { enabled: boolean; onToggle: () => void }) {
  return (
    <button
      onClick={onToggle}
      className="relative inline-flex h-5 w-9 items-center rounded-full transition-all"
      style={{ background: enabled ? '#38BDF8' : '#24314F' }}
    >
      <span
        className="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
        style={{ transform: enabled ? 'translateX(18px)' : 'translateX(2px)' }}
      />
    </button>
  );
}

function InputField({
  label,
  value,
  onChange,
  type = 'text',
}: {
  label: string;
  value: string | number;
  onChange: (v: string) => void;
  type?: string;
}) {
  return (
    <div>
      <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
        {label}
      </label>
      <input
        type={type}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className="w-full rounded-lg px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-sky-500/50"
        style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
      />
    </div>
  );
}

export default function SettingsPage() {
  const [activeTab, setActiveTab] = useState<Tab>('profile');
  const [saved, setSaved] = useState(false);
  const [watchedAssets, setWatchedAssets] = useState(['XAUUSD', 'EURUSD', 'NQ', 'GBPUSD', 'USOIL']);
  const [profile, setProfile] = useState({
    name: mockUserProfile.name,
    email: mockUserProfile.email,
    firmMode: mockUserProfile.firmMode,
    challengeLabel: mockUserProfile.challengeLabel,
  });
  const [strategyRules, setStrategyRules] = useState({
    useSMC: true,
    useOBFVG: true,
    useM1CHoCH: true,
    minScore: 80,
    minRR: 2.0,
    requireHTFBias: true,
    requireSweep: false,
    useKillZones: true,
  });
  const [newsSettings, setNewsSettings] = useState({
    blockHighImpact: true,
    blockPreNews: 15,
    cooldownMinutes: 30,
    trackUSD: true,
    trackEUR: true,
    trackGBP: true,
    trackJPY: false,
  });
  const [notifSettings, setNotifSettings] = useState({
    telegram: false,
    emailAlerts: false,
    setupAlerts: true,
    newsAlerts: true,
    riskAlerts: true,
  });

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2500);
  };

  const toggleAsset = (asset: string) => {
    setWatchedAssets((prev) =>
      prev.includes(asset) ? prev.filter((a) => a !== asset) : [...prev, asset]
    );
  };

  return (
    <AppLayout
      title="Settings"
      subtitle="Configure your trading profile, strategy rules, and risk parameters."
    >
      <div className="flex gap-4">
        {/* Sidebar Tabs */}
        <div
          className="rounded-xl border p-2 shrink-0 w-44 h-fit"
          style={{ background: '#101828', borderColor: '#24314F' }}
        >
          {TABS.map(({ id, label, icon: Icon }) => (
            <button
              key={id}
              onClick={() => setActiveTab(id)}
              className="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-all text-left"
              style={
                activeTab === id
                  ? { background: 'rgba(56, 189, 248, 0.12)', color: '#38BDF8' }
                  : { color: '#94A3B8' }
              }
            >
              <Icon className="h-3.5 w-3.5 shrink-0" />
              {label}
            </button>
          ))}
        </div>

        {/* Tab Content */}
        <div className="flex-1 min-w-0">
          <div
            className="rounded-xl border p-5"
            style={{ background: '#101828', borderColor: '#24314F' }}
          >
            {/* Profile Tab */}
            {activeTab === 'profile' && (
              <div className="space-y-4">
                <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                  Profile Settings
                </h3>
                <div className="grid grid-cols-2 gap-4">
                  <InputField
                    label="Full Name"
                    value={profile.name}
                    onChange={(v) => setProfile((p) => ({ ...p, name: v }))}
                  />
                  <InputField
                    label="Email Address"
                    value={profile.email}
                    onChange={(v) => setProfile((p) => ({ ...p, email: v }))}
                    type="email"
                  />
                  <div>
                    <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                      Account Mode
                    </label>
                    <select
                      value={profile.firmMode}
                      onChange={(e) => setProfile((p) => ({ ...p, firmMode: e.target.value as 'Personal' | 'PropFirm' }))}
                      className="w-full rounded-lg px-3 py-2 text-sm outline-none"
                      style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                    >
                      <option value="Personal">Personal Account</option>
                      <option value="PropFirm">Prop Firm Challenge</option>
                    </select>
                  </div>
                  <InputField
                    label="Challenge Label"
                    value={profile.challengeLabel}
                    onChange={(v) => setProfile((p) => ({ ...p, challengeLabel: v }))}
                  />
                </div>
              </div>
            )}

            {/* Assets Tab */}
            {activeTab === 'assets' && (
              <div className="space-y-4">
                <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                  Watched Assets
                </h3>
                <p className="text-xs" style={{ color: '#94A3B8' }}>
                  Select the assets you want to monitor and receive setup alerts for.
                </p>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-2">
                  {WATCHED_ASSETS.map((asset) => (
                    <button
                      key={asset}
                      onClick={() => toggleAsset(asset)}
                      className="flex items-center gap-2 p-3 rounded-lg border transition-all text-left"
                      style={
                        watchedAssets.includes(asset)
                          ? { background: 'rgba(56, 189, 248, 0.1)', borderColor: 'rgba(56, 189, 248, 0.3)', color: '#38BDF8' }
                          : { background: '#0D1220', borderColor: '#24314F', color: '#64748B' }
                      }
                    >
                      <div
                        className="h-4 w-4 rounded flex items-center justify-center"
                        style={{
                          background: watchedAssets.includes(asset) ? '#38BDF8' : 'transparent',
                          border: watchedAssets.includes(asset) ? 'none' : '1.5px solid #24314F',
                        }}
                      >
                        {watchedAssets.includes(asset) && (
                          <svg className="h-2.5 w-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                          </svg>
                        )}
                      </div>
                      <span className="text-xs font-semibold">{asset}</span>
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Strategy Rules Tab */}
            {activeTab === 'strategy' && (
              <div className="space-y-4">
                <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                  Strategy Rules
                </h3>
                <div className="space-y-3">
                  {[
                    { key: 'useSMC' as const, label: 'Smart Money Concepts (SMC)', desc: 'Use order blocks, FVGs, and CHoCH patterns' },
                    { key: 'useOBFVG' as const, label: 'OB + FVG Confluence', desc: 'Require order block and fair value gap alignment' },
                    { key: 'useM1CHoCH' as const, label: 'M1 CHoCH Confirmation', desc: 'Require M1 timeframe change of character before entry' },
                    { key: 'requireHTFBias' as const, label: 'HTF Bias Alignment', desc: 'Only trade in direction of higher timeframe trend' },
                    { key: 'requireSweep' as const, label: 'Liquidity Sweep Required', desc: 'Require liquidity sweep before entry' },
                    { key: 'useKillZones' as const, label: 'Kill Zones Only', desc: 'Only take setups during London/NY kill zones' },
                  ].map(({ key, label, desc }) => (
                    <div
                      key={key}
                      className="flex items-center justify-between p-3 rounded-lg border"
                      style={{ background: '#0D1220', borderColor: '#24314F' }}
                    >
                      <div>
                        <p className="text-sm font-medium" style={{ color: '#F8FAFC' }}>
                          {label}
                        </p>
                        <p className="text-xs" style={{ color: '#64748B' }}>
                          {desc}
                        </p>
                      </div>
                      <ToggleSwitch
                        enabled={strategyRules[key]}
                        onToggle={() =>
                          setStrategyRules((s) => ({ ...s, [key]: !s[key] }))
                        }
                      />
                    </div>
                  ))}

                  <div className="grid grid-cols-2 gap-3 mt-3">
                    <div>
                      <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                        Min Technical Score (0–100)
                      </label>
                      <input
                        type="number"
                        min={50}
                        max={95}
                        value={strategyRules.minScore}
                        onChange={(e) =>
                          setStrategyRules((s) => ({ ...s, minScore: parseInt(e.target.value) }))
                        }
                        className="w-full rounded-lg px-3 py-2 text-sm outline-none"
                        style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                      />
                      <p className="text-[10px] mt-1" style={{ color: '#64748B' }}>
                        GO requires score ≥ {strategyRules.minScore}
                      </p>
                    </div>
                    <div>
                      <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                        Minimum RR Ratio
                      </label>
                      <input
                        type="number"
                        min={1.5}
                        max={5}
                        step={0.5}
                        value={strategyRules.minRR}
                        onChange={(e) =>
                          setStrategyRules((s) => ({ ...s, minRR: parseFloat(e.target.value) }))
                        }
                        className="w-full rounded-lg px-3 py-2 text-sm outline-none"
                        style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                      />
                      <p className="text-[10px] mt-1" style={{ color: '#64748B' }}>
                        Minimum 1:{strategyRules.minRR} required
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Risk Settings Tab */}
            {activeTab === 'risk' && (
              <div className="space-y-4">
                <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                  Risk Settings
                </h3>
                <div className="grid grid-cols-2 gap-4">
                  <InputField label="Account Size ($)" value={mockRiskSettings.accountSize} onChange={() => {}} type="number" />
                  <InputField label="Risk Per Trade (%)" value={mockRiskSettings.riskPerTrade} onChange={() => {}} type="number" />
                  <InputField label="Max Daily Loss (%)" value={mockRiskSettings.maxDailyLoss} onChange={() => {}} type="number" />
                  <InputField label="Max Trades Per Day" value={mockRiskSettings.maxTradesPerDay} onChange={() => {}} type="number" />
                  <InputField label="Max Consecutive Losses" value={mockRiskSettings.maxConsecutiveLosses} onChange={() => {}} type="number" />
                  <InputField label="Minimum RR" value={mockRiskSettings.minimumRR} onChange={() => {}} type="number" />
                </div>
              </div>
            )}

            {/* News Settings Tab */}
            {activeTab === 'news' && (
              <div className="space-y-4">
                <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                  News Filter Settings
                </h3>
                <div className="space-y-3">
                  {[
                    { key: 'blockHighImpact' as const, label: 'Block trades near High-Impact events' },
                    { key: 'trackUSD' as const, label: 'Track USD news events' },
                    { key: 'trackEUR' as const, label: 'Track EUR news events' },
                    { key: 'trackGBP' as const, label: 'Track GBP news events' },
                    { key: 'trackJPY' as const, label: 'Track JPY news events' },
                  ].map(({ key, label }) => (
                    <div
                      key={key}
                      className="flex items-center justify-between p-3 rounded-lg border"
                      style={{ background: '#0D1220', borderColor: '#24314F' }}
                    >
                      <span className="text-sm" style={{ color: '#F8FAFC' }}>
                        {label}
                      </span>
                      <ToggleSwitch
                        enabled={newsSettings[key]}
                        onToggle={() => setNewsSettings((s) => ({ ...s, [key]: !s[key] }))}
                      />
                    </div>
                  ))}

                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                        Pre-news block window (minutes)
                      </label>
                      <input
                        type="number"
                        min={5}
                        max={60}
                        value={newsSettings.blockPreNews}
                        onChange={(e) =>
                          setNewsSettings((s) => ({ ...s, blockPreNews: parseInt(e.target.value) }))
                        }
                        className="w-full rounded-lg px-3 py-2 text-sm outline-none"
                        style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                      />
                    </div>
                    <div>
                      <label className="block text-xs font-medium mb-1.5" style={{ color: '#94A3B8' }}>
                        Post-news cooldown (minutes)
                      </label>
                      <input
                        type="number"
                        min={10}
                        max={120}
                        value={newsSettings.cooldownMinutes}
                        onChange={(e) =>
                          setNewsSettings((s) => ({ ...s, cooldownMinutes: parseInt(e.target.value) }))
                        }
                        className="w-full rounded-lg px-3 py-2 text-sm outline-none"
                        style={{ background: '#0D1220', border: '1px solid #24314F', color: '#F8FAFC' }}
                      />
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Notifications Tab */}
            {activeTab === 'notifications' && (
              <div className="space-y-4">
                <h3 className="text-sm font-semibold" style={{ color: '#F8FAFC' }}>
                  Notification Settings
                </h3>
                <div className="space-y-3">
                  {[
                    { key: 'telegram' as const, label: 'Telegram Alerts', desc: 'Send setup signals via Telegram bot' },
                    { key: 'emailAlerts' as const, label: 'Email Alerts', desc: 'Daily summary emails' },
                    { key: 'setupAlerts' as const, label: 'Setup Notifications', desc: 'Alert when new setup is detected' },
                    { key: 'newsAlerts' as const, label: 'News Notifications', desc: 'Alert before high-impact news' },
                    { key: 'riskAlerts' as const, label: 'Risk Alerts', desc: 'Alert when approaching drawdown limits' },
                  ].map(({ key, label, desc }) => (
                    <div
                      key={key}
                      className="flex items-center justify-between p-3 rounded-lg border"
                      style={{ background: '#0D1220', borderColor: '#24314F' }}
                    >
                      <div>
                        <p className="text-sm font-medium" style={{ color: '#F8FAFC' }}>
                          {label}
                        </p>
                        <p className="text-xs" style={{ color: '#64748B' }}>
                          {desc}
                        </p>
                      </div>
                      <ToggleSwitch
                        enabled={notifSettings[key]}
                        onToggle={() => setNotifSettings((s) => ({ ...s, [key]: !s[key] }))}
                      />
                    </div>
                  ))}
                </div>

                {notifSettings.telegram && (
                  <div
                    className="rounded-lg p-3 border"
                    style={{ background: '#0D1220', borderColor: '#24314F' }}
                  >
                    <p className="text-xs font-semibold mb-2" style={{ color: '#94A3B8' }}>
                      Telegram Configuration
                    </p>
                    <InputField label="Bot Token" value="Not configured" onChange={() => {}} />
                    <div className="mt-2">
                      <InputField label="Chat ID" value="Not configured" onChange={() => {}} />
                    </div>
                    <button
                      className="mt-3 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all"
                      style={{ background: 'rgba(56, 189, 248, 0.12)', color: '#38BDF8', border: '1px solid rgba(56, 189, 248, 0.25)' }}
                    >
                      Send Test Message
                    </button>
                  </div>
                )}
              </div>
            )}

            {/* Save Button */}
            <div className="mt-5 flex justify-end">
              <button
                onClick={handleSave}
                className="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                style={{
                  background: saved ? 'rgba(34, 197, 94, 0.15)' : '#38BDF8',
                  color: saved ? '#22C55E' : '#070A12',
                }}
              >
                <Save className="h-4 w-4" />
                {saved ? 'Saved!' : 'Save Settings'}
              </button>
            </div>
          </div>

          {/* Disclaimer */}
          <div
            className="mt-4 rounded-xl border p-4 flex gap-3"
            style={{ background: 'rgba(245, 158, 11, 0.06)', borderColor: 'rgba(245, 158, 11, 0.25)' }}
          >
            <AlertTriangle className="h-5 w-5 shrink-0 mt-0.5" style={{ color: '#F59E0B' }} />
            <div>
              <p className="text-sm font-semibold mb-1" style={{ color: '#F59E0B' }}>
                Risk Disclaimer
              </p>
              <p className="text-xs leading-relaxed" style={{ color: '#94A3B8' }}>
                Tradyos EdgePilot is a decision-support tool for educational and analytical purposes only. It does not
                constitute financial advice and does not execute trades automatically. Trading financial instruments
                involves significant risk of loss and may not be suitable for all investors. Past performance is not
                indicative of future results. Always conduct your own research and consult a licensed financial advisor
                before making any investment decisions. Optrixis LLC accepts no liability for trading decisions made
                based on this software.
              </p>
            </div>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
