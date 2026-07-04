import '../css/app.css';
import { createInertiaApp, Link, router, useForm, usePage } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { useEffect, useState } from 'react';
import { DndContext, useDraggable, useDroppable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { Activity, Bot, Briefcase, Building2, CheckSquare, Download, LayoutDashboard, LogOut, Menu, MoreHorizontal, Paperclip, Plus, Settings, Upload, Users, X } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import clsx from 'clsx';

const leadStatuses = ['new', 'contacted', 'qualified', 'lost', 'converted'];
const leadSources = ['website', 'referral', 'email', 'LinkedIn', 'cold call'];
const dealStages = ['prospecting', 'negotiation', 'proposal', 'won', 'lost'];
const colors = ['#059669', '#2563eb', '#f59e0b', '#dc2626', '#7c3aed'];

const nav = [
  ['Dashboard', '/dashboard', LayoutDashboard],
  ['Leads', '/leads', Users],
  ['Customers', '/customers', Building2],
  ['Deals', '/deals', Briefcase],
  ['Tasks', '/tasks', CheckSquare],
  ['Activity', '/activity', Activity],
  ['Reports', '/reports', LayoutDashboard],
  ['AI Assistant', '/ai-assistant', Bot],
  ['Profile', '/profile', Settings],
];

function AppLayout({ children }) {
  const { auth, workspace, workspaces, flash } = usePage().props;
  const [open, setOpen] = useState(false);
  const navItems = <nav className="space-y-1 p-3">{nav.map(([label, href, Icon]) => <Link key={href} href={href} className={clsx('flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium', location.pathname.startsWith(href) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100')}><Icon className="size-4" />{label}</Link>)}</nav>;

  return <div className="min-h-screen bg-slate-50 text-slate-950">
    <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 bg-white lg:block"><Brand />{navItems}</aside>
    {open && <div className="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" onClick={() => setOpen(false)}><aside className="h-full w-72 bg-white" onClick={(e) => e.stopPropagation()}><Brand />{navItems}</aside></div>}
    <main className="lg:pl-64">
      <header className="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white/95 px-4 backdrop-blur lg:px-8">
        <div className="flex items-center gap-3"><button onClick={() => setOpen(true)} className="grid size-9 place-items-center rounded-md border border-slate-200 lg:hidden"><Menu className="size-4" /></button><div><div className="text-sm font-semibold">{workspace?.name ?? 'No workspace'}</div><div className="text-xs text-slate-500">{auth.user?.name}</div></div></div>
        <div className="flex items-center gap-2">{workspaces?.map((item) => <button key={item.id} onClick={() => router.post(`/workspaces/${item.id}/switch`)} className="hidden rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium hover:bg-slate-100 sm:block">{item.name}</button>)}{workspace && <Link href={`/workspaces/${workspace.id}/settings`} className="rounded-md p-2 hover:bg-slate-100" title="Workspace settings"><Settings className="size-4" /></Link>}<button onClick={() => router.post('/logout')} className="rounded-md p-2 hover:bg-slate-100" title="Logout"><LogOut className="size-4" /></button></div>
      </header>
      <Toast flash={flash} />
      <div className="p-4 lg:p-8">{children}</div>
    </main>
  </div>;
}

function Brand() {
  return <div className="flex h-16 items-center gap-3 border-b border-slate-200 px-5"><div className="grid size-9 place-items-center rounded-lg bg-emerald-600 text-sm font-bold text-white">LF</div><div><div className="font-semibold">LeadFlow AI</div><div className="text-xs text-slate-500">SaaS CRM</div></div></div>;
}

function Toast({ flash }) {
  const [visible, setVisible] = useState(Boolean(flash?.success || flash?.error));
  useEffect(() => {
    setVisible(Boolean(flash?.success || flash?.error));
    const timer = setTimeout(() => setVisible(false), 4200);
    return () => clearTimeout(timer);
  }, [flash?.success, flash?.error]);
  if (!visible || (!flash?.success && !flash?.error)) return null;
  const good = Boolean(flash?.success);
  return <div className={clsx('fixed right-4 top-20 z-50 w-[min(420px,calc(100vw-2rem))] rounded-lg border bg-white p-4 text-sm shadow-lg', good ? 'border-emerald-200 text-emerald-900' : 'border-red-200 text-red-900')}><div className="flex items-start justify-between gap-3"><span>{flash.success || flash.error}</span><button onClick={() => setVisible(false)}><X className="size-4" /></button></div></div>;
}

function AuthLayout({ children, title, subtitle }) { return <div className="min-h-screen bg-slate-950 text-white"><div className="mx-auto grid min-h-screen max-w-6xl items-center gap-10 px-6 py-10 lg:grid-cols-[1fr_440px]"><section className="hidden lg:block"><div className="mb-8 flex items-center gap-3"><div className="grid size-12 place-items-center rounded-lg bg-emerald-500 text-lg font-bold">LF</div><div><div className="text-xl font-semibold">LeadFlow AI CRM</div><div className="text-sm text-slate-400">Multi-tenant sales command center</div></div></div><h1 className="max-w-xl text-5xl font-semibold leading-tight tracking-normal">{title}</h1><p className="mt-5 max-w-lg text-base leading-7 text-slate-300">{subtitle}</p><div className="mt-8 grid max-w-lg grid-cols-3 gap-3 text-sm text-slate-300"><div className="rounded-lg border border-white/10 p-4">Pipeline</div><div className="rounded-lg border border-white/10 p-4">AI scoring</div><div className="rounded-lg border border-white/10 p-4">Reports</div></div></section><div className="w-full">{children}</div></div></div>; }
function Field({ label, error, ...props }) { return <label className="block text-sm font-medium text-slate-700">{label}<input {...props} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none" />{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }
function Textarea({ label, ...props }) { return <label className="block text-sm font-medium text-slate-700">{label}<textarea {...props} className="mt-1 min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none" /></label>; }
function Select({ label, children, ...props }) { return <label className="block text-sm font-medium text-slate-700">{label}<select {...props} className="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-600 focus:outline-none">{children}</select></label>; }
function PageTitle({ title, action }) { return <div className="mb-6 flex flex-wrap items-center justify-between gap-3"><h1 className="text-2xl font-semibold tracking-normal">{title}</h1>{action}</div>; }
function Card({ children, className }) { return <div className={clsx('rounded-lg border border-slate-200 bg-white p-5 shadow-sm', className)}>{children}</div>; }
function Empty({ title, action }) { return <div className="rounded-md border border-dashed border-slate-300 bg-slate-50 p-8 text-center"><div className="text-sm font-medium text-slate-700">{title}</div>{action && <div className="mt-4">{action}</div>}</div>; }
function StatusBadge({ value }) {
  const styles = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-amber-100 text-amber-800',
    qualified: 'bg-green-100 text-green-800',
    lost: 'bg-red-100 text-red-800',
    converted: 'bg-emerald-100 text-emerald-800',
    won: 'bg-emerald-100 text-emerald-800',
    proposal: 'bg-amber-100 text-amber-800',
    completed: 'bg-emerald-100 text-emerald-800',
    pending: 'bg-blue-100 text-blue-800',
  };
  return <span className={clsx('rounded-full px-2 py-0.5 text-xs font-semibold capitalize', styles[value] || 'bg-slate-100 text-slate-700')}>{value}</span>;
}

function Modal({ open, title, children, onClose }) {
  if (!open) return null;
  return <div className="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4"><div className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-lg bg-white shadow-xl"><div className="flex items-center justify-between border-b border-slate-200 px-5 py-4"><h2 className="font-semibold">{title}</h2><button onClick={onClose} className="rounded-md p-1 hover:bg-slate-100"><X className="size-4" /></button></div><div className="p-5">{children}</div></div></div>;
}

function Avatar({ name }) {
  const initial = (name || '?').slice(0, 1).toUpperCase();
  return <span className="inline-grid size-7 place-items-center rounded-full bg-slate-900 text-xs font-semibold text-white">{initial}</span>;
}

function Login() {
  const form = useForm({ email: '', password: '', remember: false });
  return <AuthLayout title="Run your sales workspace with sharper visibility." subtitle="Track leads, deals, tasks, files, emails, reports, and AI-assisted follow-ups from one polished SaaS CRM."><div className="mb-6 flex items-center gap-3 lg:hidden"><div className="grid size-10 place-items-center rounded-lg bg-emerald-500 font-bold">LF</div><div><div className="font-semibold">LeadFlow AI CRM</div><div className="text-xs text-slate-400">Sales workspace</div></div></div><form onSubmit={(e) => { e.preventDefault(); form.post('/login'); }} className="space-y-4 rounded-lg bg-white p-6 text-slate-950 shadow-2xl"><div><h2 className="text-2xl font-semibold">Welcome back</h2><p className="mt-1 text-sm text-slate-500">Sign in to your CRM command center.</p></div><Field label="Email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} /><Field label="Password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} /><button className="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Login</button><Link href="/forgot-password" className="block text-center text-sm text-slate-500">Forgot password?</Link><Link href="/register" className="block text-center text-sm text-emerald-700">Create an account</Link></form></AuthLayout>;
}

function Register() {
  const form = useForm({ name: '', email: '', password: '', password_confirmation: '', workspace_name: '' });
  return <AuthLayout title="Launch a portfolio-grade CRM workspace." subtitle="Create a company workspace, invite your team, and manage the full sales cycle with multi-tenant isolation."><form onSubmit={(e) => { e.preventDefault(); form.post('/register'); }} className="space-y-4 rounded-lg bg-white p-6 text-slate-950 shadow-2xl"><div><h2 className="text-2xl font-semibold">Create workspace</h2><p className="mt-1 text-sm text-slate-500">Your first workspace is created with you as owner.</p></div><Field label="Name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /><Field label="Email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} error={form.errors.email} /><Field label="Workspace" value={form.data.workspace_name} onChange={(e) => form.setData('workspace_name', e.target.value)} /><Field label="Password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} /><Field label="Confirm password" type="password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} /><button className="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Create workspace</button><Link href="/login" className="block text-center text-sm text-emerald-700">I already have an account</Link></form></AuthLayout>;
}

function VerifyEmail() { return <AuthLayout><div className="rounded-lg bg-white p-6 text-slate-950"><h1 className="text-xl font-semibold">Verify your email</h1><p className="mt-3 text-sm text-slate-600">Check your inbox for the verification link before entering the CRM workspace.</p><button onClick={() => router.post('/email/verification-notification')} className="mt-5 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Resend link</button></div></AuthLayout>; }
function ForgotPassword() { const form = useForm({ email: '' }); return <AuthLayout><form onSubmit={(e) => { e.preventDefault(); form.post('/forgot-password'); }} className="space-y-4 rounded-lg bg-white p-6 text-slate-950"><h1 className="text-xl font-semibold">Reset password</h1><Field label="Email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} /><button className="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Send reset link</button></form></AuthLayout>; }
function ResetPassword({ token, email }) { const form = useForm({ token, email: email ?? '', password: '', password_confirmation: '' }); return <AuthLayout><form onSubmit={(e) => { e.preventDefault(); form.post('/reset-password'); }} className="space-y-4 rounded-lg bg-white p-6 text-slate-950"><h1 className="text-xl font-semibold">Choose new password</h1><Field label="Email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} /><Field label="Password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} /><Field label="Confirm password" type="password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} /><button className="w-full rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Reset password</button></form></AuthLayout>; }

function Dashboard({ metrics, pipeline, leadSources, monthlyRevenue, taskStats, recentTasks, recentActivities }) {
  return <AppLayout><PageTitle title="Dashboard" action={<div className="flex gap-2"><Link href="/leads" className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold">Leads</Link><Link href="/deals" className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Pipeline</Link></div>} /><div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">{Object.entries(metrics).map(([k, v]) => <Card key={k}><div className="text-xs uppercase text-slate-500">{k.replace(/([A-Z])/g, ' $1')}</div><div className="mt-2 text-2xl font-semibold">{typeof v === 'number' && k === 'revenueWon' ? `$${v.toLocaleString()}` : v}</div></Card>)}</div><div className="mt-6 grid gap-6 xl:grid-cols-[1.3fr_.7fr]"><ChartCard title="Revenue Trend"><ResponsiveContainer width="100%" height={300}><LineChart data={monthlyRevenue}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="month" /><YAxis /><Tooltip /><Line type="monotone" dataKey="revenue" stroke="#059669" strokeWidth={3} /></LineChart></ResponsiveContainer></ChartCard><Card><h2 className="font-semibold">Pipeline Summary</h2><div className="mt-4 space-y-3">{pipeline.length ? pipeline.map((p) => <div key={p.stage} className="rounded-md border border-slate-200 p-3"><div className="flex items-center justify-between"><StatusBadge value={p.stage} /><span className="text-sm font-semibold">{p.count} deals</span></div><div className="mt-2 text-lg font-semibold">${Number(p.value || 0).toLocaleString()}</div></div>) : <Empty title="No deals yet. Create your first deal from the pipeline." />}</div></Card></div><div className="mt-6 grid gap-6 xl:grid-cols-3"><ChartCard title="Lead Sources"><ResponsiveContainer width="100%" height={250}><BarChart data={leadSources}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="source" /><YAxis /><Tooltip /><Bar dataKey="count" fill="#2563eb" /></BarChart></ResponsiveContainer></ChartCard><Card><h2 className="font-semibold">Recent Tasks</h2><div className="mt-4 space-y-3">{recentTasks.length ? recentTasks.map((task) => <div key={task.id} className="rounded-md border border-slate-200 p-3 text-sm"><div className="flex items-center justify-between gap-3"><span className="font-medium">{task.title}</span><StatusBadge value={task.status} /></div><div className="mt-2 flex items-center gap-2 text-xs text-slate-500"><Avatar name={task.assigned_to?.name} />{task.assigned_to?.name ?? 'Unassigned'} · {task.due_date ?? 'No due date'}</div></div>) : <Empty title="No pending tasks. Add tasks to keep follow-ups moving." />}</div></Card><Card><h2 className="font-semibold">Recent Activity</h2><div className="mt-4 space-y-3">{recentActivities.length ? recentActivities.map((a) => <div key={a.id} className="text-sm"><div className="font-medium">{a.description}</div><div className="text-xs text-slate-500">{a.event}</div></div>) : <Empty title="No activity yet. CRM actions will appear here." />}</div></Card></div></AppLayout>;
}

function ChartCard({ title, children }) { return <Card><h2 className="mb-4 font-semibold">{title}</h2>{children}</Card>; }
function MetricList({ title, rows, nameKey, valueKey, money }) { return <Card><h2 className="font-semibold">{title}</h2><div className="mt-4 space-y-3">{rows.length ? rows.map((r) => <div key={r[nameKey]} className="flex justify-between text-sm"><span className="capitalize">{r[nameKey]}</span><b>{money ? `$${Number(r[valueKey] || 0).toLocaleString()}` : r[valueKey]}</b></div>) : <Empty title={`No ${title.toLowerCase()} yet`} />}</div></Card>; }

function FilterBar({ filters = {}, members = [], type }) {
  const form = useForm({ q: filters.q ?? '', status: filters.status ?? '', source: filters.source ?? '', stage: filters.stage ?? '', assigned_to_id: filters.assigned_to_id ?? '', from: filters.from ?? '', to: filters.to ?? '' });
  const path = type === 'deals' ? '/deals' : type === 'customers' ? '/customers' : '/leads';
  return <Card className="mb-6"><form onSubmit={(e) => { e.preventDefault(); router.get(path, form.data, { preserveState: true }); }} className="grid gap-3 md:grid-cols-6"><input placeholder="Search" value={form.data.q} onChange={(e) => form.setData('q', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm md:col-span-2" />{type === 'leads' && <select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="">All status</option>{leadStatuses.map(s => <option key={s}>{s}</option>)}</select>}{type === 'leads' && <select value={form.data.source} onChange={(e) => form.setData('source', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="">All sources</option>{leadSources.map(s => <option key={s}>{s}</option>)}</select>}{type === 'deals' && <select value={form.data.stage} onChange={(e) => form.setData('stage', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="">All stages</option>{dealStages.map(s => <option key={s}>{s}</option>)}</select>}<select value={form.data.assigned_to_id} onChange={(e) => form.setData('assigned_to_id', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm"><option value="">All owners</option>{members.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}</select><input type="date" value={form.data.from} onChange={(e) => form.setData('from', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm" /><input type="date" value={form.data.to} onChange={(e) => form.setData('to', e.target.value)} className="rounded-md border border-slate-300 px-3 py-2 text-sm" /><div className="flex gap-2 md:col-span-6"><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button><Link href={path} className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold">Reset</Link></div></form></Card>;
}

function LeadsIndex({ leads, members, filters }) {
  const form = useForm({ name: '', company: '', email: '', phone: '', status: 'new', source: 'website', value: 0, assigned_to_id: '', notes: '' });
  const upload = useForm({ file: null });
  const [modal, setModal] = useState(null);
  return <AppLayout><PageTitle title="Leads" action={<div className="flex flex-wrap gap-2"><button onClick={() => setModal('import')} className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold"><Upload className="mr-2 inline size-4" />Import</button><a href="/leads-export" className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold"><Download className="mr-2 inline size-4" />Export</a><button onClick={() => setModal('lead')} className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white"><Plus className="mr-2 inline size-4" />Lead</button></div>} /><FilterBar filters={filters} members={members} type="leads" /><Card><Table rows={leads.data} columns={['name', 'company', 'status', 'source', 'assigned_to', 'ai_score']} linkPrefix="/leads" emptyTitle="No leads yet. Add your first lead or import from CSV." sortBase="/leads" /><Pagination links={leads.links} /></Card><Modal open={modal === 'import'} title="Import leads" onClose={() => setModal(null)}><form onSubmit={(e) => { e.preventDefault(); upload.post('/leads/import', { forceFormData: true, onSuccess: () => setModal(null) }); }} className="space-y-3"><input type="file" accept=".csv,text/csv" onChange={(e) => upload.setData('file', e.target.files[0])} className="w-full text-sm" /><button className="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold"><Upload className="mr-2 inline size-4" />Import CSV</button></form></Modal><Modal open={modal === 'lead'} title="Create lead" onClose={() => setModal(null)}><form onSubmit={(e) => { e.preventDefault(); form.post('/leads', { preserveScroll: true, onSuccess: () => setModal(null) }); }} className="space-y-3"><Field label="Name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /><Field label="Company" value={form.data.company} onChange={(e) => form.setData('company', e.target.value)} /><Field label="Email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} /><Select label="Status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>{leadStatuses.map(x => <option key={x}>{x}</option>)}</Select><Select label="Source" value={form.data.source} onChange={(e) => form.setData('source', e.target.value)}>{leadSources.map(x => <option key={x}>{x}</option>)}</Select><Select label="Owner" value={form.data.assigned_to_id} onChange={(e) => form.setData('assigned_to_id', e.target.value)}><option value="">Unassigned</option>{members.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}</Select><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save lead</button></form></Modal></AppLayout>;
}

function Table({ rows, columns, linkPrefix, emptyTitle = 'No records found', sortBase }) {
  const sortable = ['name', 'company', 'company_name', 'status', 'source', 'stage', 'created_at', 'due_date'];
  return <div className="overflow-x-auto"><table className="w-full text-left text-sm"><thead><tr className="border-b border-slate-200 text-xs uppercase text-slate-500">{columns.map(c => <th className="py-2 pr-4" key={c}>{sortable.includes(c) && sortBase ? <button onClick={() => router.get(sortBase, { sort: c }, { preserveState: true })} className="font-semibold uppercase hover:text-slate-900">{label(c)} ↑↓</button> : label(c)}</th>)}<th className="py-2 text-right">Actions</th></tr></thead><tbody>{rows.length ? rows.map(row => <tr key={row.id} className="border-b border-slate-100 hover:bg-slate-50">{columns.map((c, i) => <td className="py-3 pr-4" key={c}>{cell(row, c, i, linkPrefix)}</td>)}<td className="py-3 text-right"><RowActions row={row} linkPrefix={linkPrefix} /></td></tr>) : <tr><td className="py-6" colSpan={columns.length + 1}><Empty title={emptyTitle} /></td></tr>}</tbody></table></div>;
}

function label(value) { return value.replaceAll('_', ' '); }
function cell(row, c, i, linkPrefix) {
  if (i === 0 && linkPrefix) return <Link className="font-medium text-emerald-700" href={`${linkPrefix}/${row.id}`}>{row[c]}</Link>;
  if (['status', 'stage'].includes(c)) return <StatusBadge value={row[c]} />;
  if (c === 'assigned_to') return <Owner user={row.assigned_to} />;
  if (c === 'owner') return <Owner user={row.owner} />;
  return String(row[c] ?? '');
}
function Owner({ user }) { return <span className="inline-flex items-center gap-2 text-sm"><Avatar name={user?.name} />{user?.name ?? 'Unassigned'}</span>; }
function RowActions({ row, linkPrefix }) {
  const [open, setOpen] = useState(false);
  if (!linkPrefix) return null;
  return <div className="relative inline-block"><button onClick={() => setOpen(!open)} className="rounded-md p-1 hover:bg-slate-100"><MoreHorizontal className="size-4" /></button>{open && <div className="absolute right-0 z-20 mt-2 w-32 rounded-md border border-slate-200 bg-white p-1 text-left shadow-lg"><Link href={`${linkPrefix}/${row.id}`} className="block rounded px-3 py-2 text-sm hover:bg-slate-100">Open</Link></div>}</div>;
}

function Pagination({ links = [] }) { return <div className="mt-4 flex flex-wrap gap-2">{links.map((link, i) => <button key={i} disabled={!link.url} onClick={() => link.url && router.visit(link.url)} className={clsx('rounded-md border px-3 py-1 text-xs', link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 text-slate-600 disabled:opacity-40')} dangerouslySetInnerHTML={{ __html: link.label }} />)}</div>; }

function CustomerIndex({ customers, members, filters }) { return <AppLayout><PageTitle title="Customers" /><FilterBar filters={filters} members={members} type="customers" /><Card><Table rows={customers.data} columns={['name', 'company_name', 'email', 'phone', 'owner']} linkPrefix="/customers" emptyTitle="No customers yet. Convert a lead or create a customer profile." sortBase="/customers" /><Pagination links={customers.links} /></Card></AppLayout>; }
function TasksIndex({ tasks, members }) {
  const [open, setOpen] = useState(false);
  const form = useForm({ title: '', description: '', assigned_to_id: '', due_date: '', priority: 'medium', status: 'pending' });
  return <AppLayout><PageTitle title="Tasks" action={<button onClick={() => setOpen(true)} className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white"><Plus className="mr-2 inline size-4" />Task</button>} /><Card><Table rows={tasks.data ?? tasks} columns={['title', 'priority', 'status', 'due_date', 'assigned_to']} emptyTitle="No tasks yet. Create a follow-up task for your sales team." sortBase="/tasks" /><Pagination links={tasks.links} /></Card><Modal open={open} title="Create task" onClose={() => setOpen(false)}><form onSubmit={(e) => { e.preventDefault(); form.post('/tasks', { onSuccess: () => setOpen(false) }); }} className="space-y-3"><Field label="Title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} /><Textarea label="Description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} /><Select label="Owner" value={form.data.assigned_to_id} onChange={(e) => form.setData('assigned_to_id', e.target.value)}><option value="">Unassigned</option>{members.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}</Select><Field label="Due date" type="datetime-local" value={form.data.due_date} onChange={(e) => form.setData('due_date', e.target.value)} /><Select label="Priority" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)}><option>low</option><option>medium</option><option>high</option></Select><Select label="Status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}><option>pending</option><option>in progress</option><option>completed</option></Select><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save task</button></form></Modal></AppLayout>;
}
function ActivityIndex({ activities }) { return <GenericIndex title="Activity Timeline" items={activities} columns={['event', 'description', 'created_at']} />; }
function GenericIndex({ title, items, columns }) { return <AppLayout><PageTitle title={title} /><Card><Table rows={items.data ?? items} columns={columns} /><Pagination links={items.links} /></Card></AppLayout>; }

function Pipeline({ deals, members, customers, filters }) {
  const [open, setOpen] = useState(false);
  const form = useForm({ title: '', customer_id: '', lead_id: '', owner_id: '', stage: 'prospecting', value: 0, expected_close_date: '', probability: 25, description: '' });
  function onDragEnd(event) {
    const dealId = String(event.active.id).replace('deal-', '');
    const stage = event.over?.id;
    if (dealId && stage && dealStages.includes(stage)) {
      router.patch(`/deals/${dealId}/stage`, { stage }, { preserveScroll: true });
    }
  }
  return <AppLayout><PageTitle title="Deals Pipeline" action={<button onClick={() => setOpen(true)} className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white"><Plus className="mr-2 inline size-4" />Deal</button>} /><FilterBar filters={filters} members={members} type="deals" /><DndContext onDragEnd={onDragEnd}><div className="grid gap-4 xl:grid-cols-5">{dealStages.map(stage => <DealColumn key={stage} stage={stage} deals={deals[stage] || []} />)}</div></DndContext><Modal open={open} title="Create deal" onClose={() => setOpen(false)}><form onSubmit={(e) => { e.preventDefault(); form.post('/deals', { onSuccess: () => setOpen(false) }); }} className="space-y-3"><Field label="Title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} /><Select label="Customer" value={form.data.customer_id} onChange={(e) => form.setData('customer_id', e.target.value)}><option value="">No customer</option>{customers.map(c => <option key={c.id} value={c.id}>{c.company_name ?? c.name}</option>)}</Select><Select label="Owner" value={form.data.owner_id} onChange={(e) => form.setData('owner_id', e.target.value)}><option value="">Unassigned</option>{members.map(m => <option key={m.id} value={m.id}>{m.name}</option>)}</Select><Select label="Stage" value={form.data.stage} onChange={(e) => form.setData('stage', e.target.value)}>{dealStages.map(s => <option key={s}>{s}</option>)}</Select><Field label="Value" type="number" value={form.data.value} onChange={(e) => form.setData('value', e.target.value)} /><Field label="Expected close date" type="date" value={form.data.expected_close_date} onChange={(e) => form.setData('expected_close_date', e.target.value)} /><Field label="Probability" type="number" min="0" max="100" value={form.data.probability} onChange={(e) => form.setData('probability', e.target.value)} /><Textarea label="Description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} /><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save deal</button></form></Modal></AppLayout>;
}

function DealColumn({ stage, deals }) {
  const { setNodeRef, isOver } = useDroppable({ id: stage });
  return <div ref={setNodeRef} className={clsx('min-h-96 rounded-lg border border-slate-200 bg-white p-4 shadow-sm', isOver && 'border-emerald-500 bg-emerald-50')}><h2 className="mb-4 flex items-center justify-between font-semibold capitalize">{stage}<span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{deals.length}</span></h2><div className="space-y-3">{deals.map(d => <DealCard key={d.id} deal={d} />)}{!deals.length && <Empty title="Drop deals here" />}</div></div>;
}

function DealCard({ deal }) {
  const { attributes, listeners, setNodeRef, transform } = useDraggable({ id: `deal-${deal.id}` });
  return <div ref={setNodeRef} style={{ transform: CSS.Translate.toString(transform) }} {...listeners} {...attributes} className="cursor-grab rounded-md border border-slate-200 bg-white p-3 shadow-sm active:cursor-grabbing"><Link href={`/deals/${deal.id}`} className="font-medium text-emerald-700">{deal.title}</Link><div className="mt-1 text-sm text-slate-500">${Number(deal.value).toLocaleString()} · {deal.probability}%</div><div className="mt-2 text-xs text-slate-500">{deal.customer?.company_name ?? deal.customer?.name ?? 'No customer'}</div></div>;
}

function ShowLead({ lead, tasks, emails, files }) {
  return <CrmDetail type="lead" record={lead} title={lead.name} subtitle={lead.company} tasks={tasks} emails={emails} files={files} extra={<button onClick={() => router.post(`/leads/${lead.id}/convert`)} className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Convert</button>} />;
}
function ShowCustomer({ customer, tasks, emails, files }) { return <CrmDetail type="customer" record={customer} title={customer.name} subtitle={customer.company_name} tasks={tasks} emails={emails} files={files} />; }
function ShowDeal({ deal, tasks, emails, files }) { return <CrmDetail type="deal" record={deal} title={deal.title} subtitle={deal.customer?.company_name ?? deal.customer?.name} tasks={tasks} emails={emails} files={files} />; }

function CrmDetail({ type, record, title, subtitle, tasks, emails, files, extra }) {
  const tabs = ['Overview', 'Notes', 'Tasks', 'Emails', 'Files', 'Activity'];
  const [tab, setTab] = useState('Overview');
  return <AppLayout><PageTitle title={title} action={extra} /><div className="mb-6 flex gap-2 overflow-x-auto">{tabs.map(t => <button key={t} onClick={() => setTab(t)} className={clsx('rounded-md px-3 py-2 text-sm font-semibold', tab === t ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white')}>{t}</button>)}</div>{tab === 'Overview' && <Overview record={record} subtitle={subtitle} />}{tab === 'Notes' && <NotesPanel type={type} record={record} />}{tab === 'Tasks' && <ListPanel rows={tasks} columns={['title', 'priority', 'status', 'due_date']} />}{tab === 'Emails' && <EmailsPanel type={type} record={record} emails={emails} />}{tab === 'Files' && <FilesPanel type={type} record={record} files={files} />}{tab === 'Activity' && <ActivityPanel rows={record.activities || []} />}</AppLayout>;
}

function Overview({ record, subtitle }) { return <div className="grid gap-6 xl:grid-cols-3"><Card><div className="text-sm text-slate-500">Primary</div><div className="mt-2 text-xl font-semibold">{record.name ?? record.title}</div><p className="mt-2 text-sm text-slate-600">{subtitle}</p>{record.status && <div className="mt-4"><StatusBadge value={record.status} /></div>}{record.stage && <div className="mt-4"><StatusBadge value={record.stage} /></div>}</Card>{record.ai_score !== undefined && <Card><div className="text-sm text-slate-500">AI score</div><div className="mt-2 text-4xl font-semibold">{record.ai_score}</div><p className="mt-3 text-sm">{record.ai_reason}</p></Card>}<Card><h2 className="font-semibold">Contact</h2><p className="mt-3 text-sm">{record.email ?? 'No email'}</p><p className="text-sm">{record.phone ?? 'No phone'}</p></Card></div>; }

function NotesPanel({ type, record }) {
  const form = useForm({ type, id: record.id, body: '' });
  const [open, setOpen] = useState(false);
  return <div><div className="mb-4 flex justify-end"><button onClick={() => setOpen(true)} className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white"><Plus className="mr-2 inline size-4" />Note</button></div><ListPanel rows={record.notes || []} columns={['body', 'created_at']} /><Modal open={open} title="Add note" onClose={() => setOpen(false)}><form onSubmit={(e) => { e.preventDefault(); form.post('/crm/notes', { preserveScroll: true, onSuccess: () => { form.reset('body'); setOpen(false); } }); }} className="space-y-3"><Textarea label="Note" value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} /><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save note</button></form></Modal></div>;
}

function EmailsPanel({ type, record, emails }) {
  const form = useForm({ type, id: record.id, direction: 'sent', subject: '', body: '', sender: '', receiver: record.email ?? '' });
  return <div className="grid gap-6 xl:grid-cols-[1fr_420px]"><ListPanel rows={emails} columns={['subject', 'direction', 'summary', 'created_at']} /><Card><h2 className="mb-4 font-semibold">Log email</h2><form onSubmit={(e) => { e.preventDefault(); form.post('/crm/emails', { preserveScroll: true }); }} className="space-y-3"><Select label="Direction" value={form.data.direction} onChange={(e) => form.setData('direction', e.target.value)}><option>sent</option><option>received</option></Select><Field label="Subject" value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} /><Field label="Sender" type="email" value={form.data.sender} onChange={(e) => form.setData('sender', e.target.value)} /><Field label="Receiver" type="email" value={form.data.receiver} onChange={(e) => form.setData('receiver', e.target.value)} /><Textarea label="Body" value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} /><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Log email</button></form></Card></div>;
}

function FilesPanel({ type, record, files }) {
  const form = useForm({ type, id: record.id, file: null });
  return <div className="grid gap-6 xl:grid-cols-[1fr_360px]"><Card><div className="space-y-3">{files.length ? files.map(file => <div key={file.id} className="flex items-center justify-between rounded-md border border-slate-200 p-3 text-sm"><span><Paperclip className="mr-2 inline size-4" />{file.name}</span><a className="font-semibold text-emerald-700" href={`/crm/files/${file.id}/download`}>Download</a></div>) : <Empty title="No files uploaded" />}</div></Card><Card><h2 className="mb-4 font-semibold">Upload file</h2><form onSubmit={(e) => { e.preventDefault(); form.post('/crm/files', { forceFormData: true, preserveScroll: true }); }} className="space-y-3"><input type="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onChange={(e) => form.setData('file', e.target.files[0])} className="w-full text-sm" /><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white"><Upload className="mr-2 inline size-4" />Upload</button></form></Card></div>;
}

function ListPanel({ rows, columns }) { return <Card><Table rows={rows || []} columns={columns} /></Card>; }
function ActivityPanel({ rows }) { return <Card><div className="space-y-3">{rows.length ? rows.map(a => <div key={a.id} className="rounded-md border border-slate-200 p-3 text-sm"><div className="font-medium">{a.description}</div><div className="text-xs text-slate-500">{a.event} · {a.created_at}</div></div>) : <Empty title="No activity yet" />}</div></Card>; }

function Reports({ winLoss, salesByUser, taskStats, monthlyRevenue, leadSources }) {
  const winLossData = [{ name: 'Won', value: winLoss.won }, { name: 'Lost', value: winLoss.lost }];
  return <AppLayout><PageTitle title="Reports" /><div className="grid gap-6 xl:grid-cols-2"><ChartCard title="Monthly Revenue"><ResponsiveContainer width="100%" height={280}><LineChart data={monthlyRevenue}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="month" /><YAxis /><Tooltip /><Line type="monotone" dataKey="revenue" stroke="#059669" strokeWidth={3} /></LineChart></ResponsiveContainer></ChartCard><ChartCard title="Lead Sources"><ResponsiveContainer width="100%" height={280}><BarChart data={leadSources}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="source" /><YAxis /><Tooltip /><Bar dataKey="count" fill="#2563eb" /></BarChart></ResponsiveContainer></ChartCard><ChartCard title="Deal Win / Loss"><ResponsiveContainer width="100%" height={280}><PieChart><Pie data={winLossData} dataKey="value" nameKey="name" outerRadius={90} label>{winLossData.map((_, index) => <Cell key={index} fill={colors[index]} />)}</Pie><Tooltip /></PieChart></ResponsiveContainer></ChartCard><ChartCard title="Task Completion"><ResponsiveContainer width="100%" height={280}><BarChart data={taskStats}><CartesianGrid strokeDasharray="3 3" /><XAxis dataKey="status" /><YAxis /><Tooltip /><Bar dataKey="count" fill="#7c3aed" /></BarChart></ResponsiveContainer></ChartCard></div><div className="mt-6"><MetricList title="Sales Performance" rows={salesByUser.map(r => ({ name: r.owner?.name ?? 'Unassigned', revenue: r.revenue }))} nameKey="name" valueKey="revenue" money /></div></AppLayout>;
}

function Assistant({ answer, question }) { const form = useForm({ question: question ?? '' }); return <AppLayout><PageTitle title="AI Sales Assistant" /><Card><form onSubmit={(e) => { e.preventDefault(); form.post('/ai-assistant'); }} className="flex gap-3"><input value={form.data.question} onChange={(e) => form.setData('question', e.target.value)} className="min-w-0 flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm" placeholder="Which leads should I follow up today?" /><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Ask</button></form>{answer && <div className="mt-6 rounded-md bg-emerald-50 p-4 text-sm text-emerald-900">{answer}</div>}</Card></AppLayout>; }

function SettingsPage({ settingsWorkspace, members, invitations }) {
  const invite = useForm({ email: '', role: 'Sales Executive' });
  return <AppLayout><PageTitle title="Workspace Settings" /><div className="grid gap-6 lg:grid-cols-2"><Card><h2 className="font-semibold">Team Members</h2>{members.map(m => <p key={m.id} className="mt-3 text-sm">{m.name} · {m.email}</p>)}</Card><Card><h2 className="font-semibold">Invite teammate</h2><form onSubmit={(e) => { e.preventDefault(); invite.post(`/workspaces/${settingsWorkspace.id}/invitations`, { preserveScroll: true }); }} className="mt-4 space-y-3"><Field label="Email" type="email" value={invite.data.email} onChange={(e) => invite.setData('email', e.target.value)} /><Select label="Role" value={invite.data.role} onChange={(e) => invite.setData('role', e.target.value)}>{['Admin', 'Manager', 'Sales Executive', 'Viewer'].map(r => <option key={r}>{r}</option>)}</Select><button className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Send invitation</button></form></Card><Card className="lg:col-span-2"><h2 className="font-semibold">Invitations</h2>{invitations.map(i => <p key={i.id} className="mt-3 text-sm">{i.email} · {i.role}</p>)}</Card></div></AppLayout>;
}

function CreateWorkspace() { const form = useForm({ name: '' }); return <AppLayout><PageTitle title="Create Workspace" /><Card><form onSubmit={(e) => { e.preventDefault(); form.post('/workspaces'); }} className="space-y-4"><Field label="Workspace name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /><button className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Create</button></form></Card></AppLayout>; }
function ProfileEdit() { const { auth } = usePage().props; const form = useForm({ name: auth.user?.name ?? '', email: auth.user?.email ?? '' }); return <AppLayout><PageTitle title="Profile Settings" /><Card><form onSubmit={(e) => { e.preventDefault(); form.patch('/profile'); }} className="max-w-lg space-y-4"><Field label="Name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /><Field label="Email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} /><button className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Save profile</button></form></Card></AppLayout>; }

const pages = {
  'Auth/Login': Login,
  'Auth/Register': Register,
  'Auth/VerifyEmail': VerifyEmail,
  'Auth/ForgotPassword': ForgotPassword,
  'Auth/ResetPassword': ResetPassword,
  Dashboard,
  'Leads/Index': LeadsIndex,
  'Leads/Show': ShowLead,
  'Customers/Index': CustomerIndex,
  'Customers/Show': ShowCustomer,
  'Deals/Pipeline': Pipeline,
  'Deals/Show': ShowDeal,
  'Tasks/Index': TasksIndex,
  'Activity/Index': ActivityIndex,
  'Reports/Index': Reports,
  'AI/Assistant': Assistant,
  'Workspaces/Settings': SettingsPage,
  'Workspaces/Create': CreateWorkspace,
  'Profile/Edit': ProfileEdit,
};

createInertiaApp({
  resolve: (name) => pages[name],
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
});
