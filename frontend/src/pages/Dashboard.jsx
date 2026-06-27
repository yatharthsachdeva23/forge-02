import { useState, useEffect, useCallback } from 'react'
import { useAuth } from '../hooks/useAuth.jsx'
import api from '../api/client.js'
import {
  LogOut, Search, Plus, MessageSquare, User, Clock,
  Tag, ChevronLeft, Send, CheckCircle, AlertCircle, ShieldCheck, History, Edit, ClipboardList
} from 'lucide-react'

const statusColors = {
  open: 'bg-emerald-100 text-emerald-700',
  pending: 'bg-amber-100 text-amber-700',
  resolved: 'bg-blue-100 text-blue-700',
  closed: 'bg-slate-100 text-slate-600',
}

const priorityColors = {
  low: 'bg-slate-100 text-slate-600',
  medium: 'bg-indigo-100 text-indigo-700',
  high: 'bg-orange-100 text-orange-700',
  urgent: 'bg-red-100 text-red-700',
}

export default function Dashboard() {
  const { user, logout } = useAuth()
  const [tickets, setTickets] = useState([])
  const [loading, setLoading] = useState(true)
  const [statusFilter, setStatusFilter] = useState('')
  const [priorityFilter, setPriorityFilter] = useState('')
  const [search, setSearch] = useState('')
  const [selectedTicket, setSelectedTicket] = useState(null)
  const [showCreate, setShowCreate] = useState(false)
  const [metrics, setMetrics] = useState({ open: 0, pending: 0, resolved: 0, closed: 0, total: 0 })

  const fetchMetrics = useCallback(async () => {
    try {
      const { data } = await api.get('/api/tickets/metrics')
      setMetrics(data)
    } catch (err) {
      console.error('Failed to fetch metrics', err)
    }
  }, [])

  const fetchTickets = useCallback(async () => {
    setLoading(true)
    try {
      const params = {}
      if (statusFilter) params.status = statusFilter
      if (priorityFilter) params.priority = priorityFilter
      if (search.trim()) params.search = search.trim()
      const { data } = await api.get('/api/tickets', { params })
      setTickets(data.data || [])
    } catch {
      setTickets([])
    } finally {
      setLoading(false)
    }
  }, [statusFilter, priorityFilter, search])

  useEffect(() => {
    fetchTickets()
    fetchMetrics()
  }, [fetchTickets, fetchMetrics])

  const handleCreated = () => {
    fetchTickets()
    fetchMetrics()
  }

  const handleBackToDashboard = () => {
    setSelectedTicket(null)
    fetchTickets()
    fetchMetrics()
  }

  return (
    <div className="flex h-screen bg-slate-50">
      {/* Sidebar */}
      <aside className="w-64 bg-white border-r border-slate-200 flex flex-col">
        <div className="p-5 border-b border-slate-100">
          <h1 className="text-xl font-bold text-indigo-600 flex items-center gap-2">
            <ShieldCheck size={22} /> PulseDesk
          </h1>
        </div>
        <div className="p-4 flex-1">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
              {user?.name?.charAt(0) || 'U'}
            </div>
            <div className="overflow-hidden">
              <p className="text-sm font-medium text-slate-800 truncate">{user?.name}</p>
              <p className="text-xs text-slate-500 capitalize truncate">{user?.role} - {user?.organization?.name || 'Loading Org...'}</p>
            </div>
          </div>
        </div>
        <div className="p-4 border-t border-slate-100">
          <button
            onClick={logout}
            className="flex items-center gap-2 text-sm text-slate-600 hover:text-red-600 transition"
          >
            <LogOut size={16} /> Sign Out
          </button>
        </div>
      </aside>

      {/* Main area */}
      <main className="flex-1 flex flex-col overflow-hidden">
        {selectedTicket ? (
          <TicketDetail
            ticketId={selectedTicket.id}
            onBack={handleBackToDashboard}
            user={user}
          />
        ) : (
          <>
            {/* Top bar */}
            <div className="bg-white border-b border-slate-200 p-4 flex items-center gap-3">
              <div className="relative flex-1 max-w-md">
                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                  type="text"
                  placeholder="Search tickets..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">All Status</option>
                <option value="open">Open</option>
                <option value="pending">Pending</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
              <select
                value={priorityFilter}
                onChange={(e) => setPriorityFilter(e.target.value)}
                className="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="">All Priority</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
              <button
                onClick={() => setShowCreate(true)}
                className="ml-auto flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition"
              >
                <Plus size={16} /> New Ticket
              </button>
            </div>

            {/* KPI Metrics Row */}
            <div className="p-4 grid grid-cols-2 md:grid-cols-5 gap-4 bg-slate-100 border-b border-slate-200">
              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center">
                <span className="text-xs text-slate-500 font-medium uppercase">Total</span>
                <span className="text-2xl font-bold text-slate-800">{metrics.total}</span>
              </div>
              <div className="bg-white p-4 rounded-xl border border-emerald-200 shadow-sm flex flex-col justify-center border-l-4 border-l-emerald-500">
                <span className="text-xs text-emerald-600 font-medium uppercase">Open</span>
                <span className="text-2xl font-bold text-emerald-700">{metrics.open}</span>
              </div>
              <div className="bg-white p-4 rounded-xl border border-amber-200 shadow-sm flex flex-col justify-center border-l-4 border-l-amber-500">
                <span className="text-xs text-amber-600 font-medium uppercase">Pending</span>
                <span className="text-2xl font-bold text-amber-700">{metrics.pending}</span>
              </div>
              <div className="bg-white p-4 rounded-xl border border-blue-200 shadow-sm flex flex-col justify-center border-l-4 border-l-blue-500">
                <span className="text-xs text-blue-600 font-medium uppercase">Resolved</span>
                <span className="text-2xl font-bold text-blue-700">{metrics.resolved}</span>
              </div>
              <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-center border-l-4 border-l-slate-400">
                <span className="text-xs text-slate-500 font-medium uppercase">Closed</span>
                <span className="text-2xl font-bold text-slate-700">{metrics.closed}</span>
              </div>
            </div>

            {/* Ticket grid */}
            <div className="flex-1 overflow-y-auto p-4">
              {loading ? (
                <p className="text-slate-500 text-center mt-10">Loading tickets...</p>
              ) : tickets.length === 0 ? (
                <p className="text-slate-500 text-center mt-10">No tickets found.</p>
              ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {tickets.map((t) => (
                    <div
                      key={t.id}
                      onClick={() => setSelectedTicket(t)}
                      className="bg-white rounded-xl border border-slate-200 p-4 cursor-pointer hover:shadow-md transition flex flex-col justify-between min-h-[160px]"
                    >
                      <div>
                        <div className="flex items-center justify-between mb-2">
                          <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${statusColors[t.status]}`}>
                            {t.status}
                          </span>
                          <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${priorityColors[t.priority]}`}>
                            {t.priority}
                          </span>
                        </div>
                        <h3 className="font-semibold text-slate-800 mb-1 truncate">{t.subject}</h3>
                        <p className="text-sm text-slate-500 line-clamp-2">{t.description}</p>
                      </div>

                      {/* Display Tags */}
                      {t.tags && t.tags.length > 0 && (
                        <div className="flex flex-wrap gap-1 mt-2">
                          {t.tags.map((tag) => (
                            <span key={tag} className="text-[10px] bg-slate-100 border border-slate-200 text-slate-600 px-1.5 py-0.5 rounded">
                              {tag}
                            </span>
                          ))}
                        </div>
                      )}

                      <div className="mt-3 flex items-center justify-between text-xs text-slate-400 pt-2 border-t border-slate-50">
                        <span className="flex items-center gap-1"><User size={12} /> #{t.id}</span>
                        <span className="flex items-center gap-1"><Clock size={12} /> {new Date(t.created_at).toLocaleDateString()}</span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </>
        )}
      </main>

      {showCreate && <CreateModal onClose={() => setShowCreate(false)} onCreated={handleCreated} />}
    </div>
  )
}

/* ── Ticket Detail ─────────────────────────────────────── */
function TicketDetail({ ticketId, onBack, user }) {
  const [ticket, setTicket] = useState(null)
  const [comments, setComments] = useState([])
  const [newComment, setNewComment] = useState('')
  const [isInternal, setIsInternal] = useState(false)
  const [sending, setSending] = useState(false)
  const [activity, setActivity] = useState([])
  const [updating, setUpdating] = useState(false)
  
  const isAgent = user?.role !== 'customer'

  const fetchTicketDetails = useCallback(async () => {
    try {
      const { data } = await api.get(`/api/tickets/${ticketId}`)
      setTicket(data)
    } catch (err) {
      console.error(err)
    }
  }, [ticketId])

  const fetchComments = useCallback(async () => {
    try {
      const { data } = await api.get(`/api/tickets/${ticketId}/comments`)
      setComments(data)
    } catch {
      setComments([])
    }
  }, [ticketId])

  const fetchActivity = useCallback(async () => {
    try {
      const { data } = await api.get(`/api/tickets/${ticketId}/activity`)
      setActivity(data)
    } catch {
      setActivity([])
    }
  }, [ticketId])

  useEffect(() => {
    fetchTicketDetails()
    fetchComments()
    fetchActivity()
  }, [fetchTicketDetails, fetchComments, fetchActivity])

  const sendComment = async (e) => {
    e.preventDefault()
    if (!newComment.trim()) return
    setSending(true)
    try {
      await api.post(`/api/tickets/${ticketId}/comments`, {
        body: newComment,
        ...(isAgent && { is_internal: isInternal }),
      })
      setNewComment('')
      setIsInternal(false)
      fetchComments()
      fetchActivity()
    } finally {
      setSending(false)
    }
  }

  const updateStatus = async (status) => {
    setUpdating(true)
    try {
      await api.put(`/api/tickets/${ticketId}`, { status })
      fetchTicketDetails()
      fetchActivity()
    } finally {
      setUpdating(false)
    }
  }

  if (!ticket) {
    return (
      <div className="flex-1 flex items-center justify-center">
        <p className="text-slate-500">Loading ticket details...</p>
      </div>
    )
  }

  return (
    <div className="flex-1 flex flex-col overflow-hidden">
      <div className="bg-white border-b border-slate-200 p-4 flex items-center gap-3">
        <button onClick={onBack} className="p-2 hover:bg-slate-100 rounded-lg transition">
          <ChevronLeft size={20} />
        </button>
        <h2 className="text-lg font-semibold text-slate-800">{ticket.subject}</h2>
        <span className={`ml-auto text-xs font-medium px-2 py-0.5 rounded-full ${statusColors[ticket.status]}`}>
          {ticket.status}
        </span>
      </div>

      <div className="flex-1 overflow-y-auto p-4 space-y-4">
        {/* Ticket Details Panel */}
        <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div className="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
            <span className="text-xs font-semibold text-slate-400 uppercase">Description</span>
            {ticket.sla && (
              <span className={`text-xs font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1
                ${ticket.priority === 'urgent' ? 'bg-red-50 text-red-600 border border-red-200' : 
                  ticket.priority === 'high' ? 'bg-orange-50 text-orange-600 border border-orange-200' :
                  ticket.priority === 'medium' ? 'bg-blue-50 text-blue-600 border border-blue-200' :
                  'bg-slate-50 text-slate-600 border border-slate-200'}`}
              >
                SLA: {ticket.sla.resolution_time_hours}h Resolution Target
              </span>
            )}
          </div>
          <p className="text-slate-700 whitespace-pre-wrap text-sm leading-relaxed">{ticket.description}</p>
          <div className="mt-4 flex flex-wrap gap-4 text-xs text-slate-500">
            <span className="flex items-center gap-1"><User size={14} /> Requester: {ticket.requester?.name || `User #${ticket.requester_id}`}</span>
            <span className="flex items-center gap-1"><Tag size={14} /> Priority: {ticket.priority}</span>
            <span className="flex items-center gap-1"><CheckCircle size={14} /> Assignee: {ticket.assignee?.name || 'Unassigned'}</span>
          </div>

          {/* Agent Action Controls */}
          {isAgent && (
            <div className="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-2">
              <span className="text-xs text-slate-400 font-semibold uppercase flex items-center gap-1 mr-2"><Edit size={12} /> Agent Actions:</span>
              <button 
                onClick={() => updateStatus('open')} 
                disabled={updating || ticket.status === 'open'}
                className="text-xs bg-slate-100 text-slate-600 hover:bg-emerald-100 hover:text-emerald-700 border border-slate-200 font-medium px-2.5 py-1 rounded-md transition disabled:opacity-50"
              >
                Mark Open
              </button>
              <button 
                onClick={() => updateStatus('pending')} 
                disabled={updating || ticket.status === 'pending'}
                className="text-xs bg-slate-100 text-slate-600 hover:bg-amber-100 hover:text-amber-700 border border-slate-200 font-medium px-2.5 py-1 rounded-md transition disabled:opacity-50"
              >
                Mark Pending
              </button>
              <button 
                onClick={() => updateStatus('resolved')} 
                disabled={updating || ticket.status === 'resolved'}
                className="text-xs bg-slate-100 text-slate-600 hover:bg-blue-100 hover:text-blue-700 border border-slate-200 font-medium px-2.5 py-1 rounded-md transition disabled:opacity-50"
              >
                Mark Resolved
              </button>
              <button 
                onClick={() => updateStatus('closed')} 
                disabled={updating || ticket.status === 'closed'}
                className="text-xs bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-800 border border-slate-200 font-medium px-2.5 py-1 rounded-md transition disabled:opacity-50"
              >
                Mark Closed
              </button>
            </div>
          )}
        </div>

        {/* Audit Log / Activity Trail */}
        <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-3">
          <h3 className="font-semibold text-slate-800 flex items-center gap-2 text-sm border-b border-slate-100 pb-2">
            <History size={16} className="text-slate-400" /> Ticket History (Audit Log)
          </h3>
          <div className="space-y-2.5 max-h-[160px] overflow-y-auto pr-1">
            {activity.length === 0 ? (
              <p className="text-xs text-slate-400 italic">No activity logged.</p>
            ) : (
              activity.map((log) => (
                <div key={log.id} className="flex items-start justify-between text-xs border-b border-slate-50 pb-1.5 last:border-0 last:pb-0">
                  <div className="flex flex-col">
                    <span className="text-slate-700 font-medium">{log.action_description}</span>
                    <span className="text-[10px] text-slate-400">By: {log.user?.name || 'System'}</span>
                  </div>
                  <span className="text-[10px] text-slate-400 font-medium">{new Date(log.created_at).toLocaleString()}</span>
                </div>
              ))
            )}
          </div>
        </div>

        {/* Comment Thread */}
        <div className="space-y-3">
          <h3 className="font-semibold text-slate-800 flex items-center gap-2 text-sm">
            <MessageSquare size={16} className="text-slate-400" /> Comments ({comments.length})
          </h3>
          {comments.map((c) => (
            <div
              key={c.id}
              className={`rounded-xl border p-4 shadow-sm ${c.is_internal ? 'bg-amber-50 border-amber-200' : 'bg-white border-slate-200'}`}
            >
              <div className="flex items-center justify-between mb-1">
                <span className="text-xs font-semibold text-indigo-600">User #{c.user_id}</span>
                <span className="text-[10px] text-slate-400">{new Date(c.created_at).toLocaleString()}</span>
              </div>
              <p className="text-sm text-slate-700">{c.body}</p>
              {c.is_internal && (
                <span className="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                  <AlertCircle size={10} /> Internal Note
                </span>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* New Comment Box */}
      <div className="bg-white border-t border-slate-200 p-4">
        <form onSubmit={sendComment} className="flex flex-col gap-3">
          <textarea
            value={newComment}
            onChange={(e) => setNewComment(e.target.value)}
            placeholder="Write a comment..."
            rows={2}
            className="w-full px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
          />
          <div className="flex items-center justify-between">
            {isAgent && (
              <label className="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                <input
                  type="checkbox"
                  checked={isInternal}
                  onChange={(e) => setIsInternal(e.target.checked)}
                  className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                Internal Note (Agent Only)
              </label>
            )}
            <button
              type="submit"
              disabled={sending || !newComment.trim()}
              className="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50 ml-auto"
            >
              <Send size={14} /> {sending ? 'Sending...' : 'Send'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

/* ── Create Ticket Modal ──────────────────────────────── */
function CreateModal({ onClose, onCreated }) {
  const [subject, setSubject] = useState('')
  const [description, setDescription] = useState('')
  const [priority, setPriority] = useState('medium')
  const [tagsInput, setTagsInput] = useState('')
  const [creating, setCreating] = useState(false)

  const handleCreate = async (e) => {
    e.preventDefault()
    setCreating(true)
    
    // Parse tags by split
    const tags = tagsInput.split(',')
      .map(t => t.trim())
      .filter(t => t.length > 0)

    try {
      await api.post('/api/tickets', { 
        subject, 
        description, 
        priority,
        ...(tags.length > 0 && { tags })
      })
      onCreated()
      onClose()
    } finally {
      setCreating(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div className="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
        <h2 className="text-lg font-semibold text-slate-800 mb-4">Create New Ticket</h2>
        <form onSubmit={handleCreate} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Subject</label>
            <input value={subject} onChange={(e) => setSubject(e.target.value)} required
              className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <textarea value={description} onChange={(e) => setDescription(e.target.value)} required rows={4}
              className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none text-sm" />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Priority</label>
              <select value={priority} onChange={(e) => setPriority(e.target.value)}
                className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"
              >
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Tags (comma separated)</label>
              <input value={tagsInput} onChange={(e) => setTagsInput(e.target.value)} placeholder="e.g. billing, network"
                className="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm" />
            </div>
          </div>
          <div className="flex justify-end gap-3 pt-2">
            <button type="button" onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-lg transition">
              Cancel
            </button>
            <button type="submit" disabled={creating}
              className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50"
            >
              {creating ? 'Creating...' : 'Create Ticket'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
