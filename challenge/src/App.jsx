import { useState, useEffect } from 'react'

const STATUSES = ['all', 'active', 'inactive']

function SortIcon({ active, direction }) {
  if (!active) return <span className="ml-1 text-gray-300">↕</span>
  return <span className="ml-1">{direction === 'asc' ? '↑' : '↓'}</span>
}

export default function App() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  // Filter / sort state
  const [status, setStatus] = useState('all')
  const [searchInput, setSearchInput] = useState('')
  const [search, setSearch] = useState('')   // debounced
  const [sort, setSort] = useState('name')
  const [direction, setDirection] = useState('asc')

  // Debounce the search input by 300 ms
  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput), 300)
    return () => clearTimeout(t)
  }, [searchInput])

  // Fetch whenever any server-side param changes
  useEffect(() => {
    let cancelled = false

    async function load() {
      setLoading(true)
      setError(null)
      try {
        const params = new URLSearchParams({ status, search, sort, direction })
        const res = await fetch(`/api/items?${params}`)
        if (!res.ok) throw new Error(`Upstream error ${res.status}`)
        const json = await res.json()
        if (!cancelled) setItems(json.data)
      } catch (e) {
        if (!cancelled) setError(e.message)
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    load()
    return () => { cancelled = true }
  }, [status, search, sort, direction])

  function handleSort(column) {
    if (sort === column) {
      setDirection(d => (d === 'asc' ? 'desc' : 'asc'))
    } else {
      setSort(column)
      setDirection('asc')
    }
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-3xl mx-auto px-4 py-8">

        <h1 className="text-2xl font-semibold text-gray-800 mb-6">Items</h1>

        {/* ── Controls ── */}
        <div className="flex flex-col sm:flex-row gap-3 mb-6">

          {/* Status toggle */}
          <div className="flex rounded-md overflow-hidden border border-gray-300 shrink-0">
            {STATUSES.map(s => (
              <button
                key={s}
                onClick={() => setStatus(s)}
                className={`px-4 py-2 text-sm capitalize transition-colors ${
                  status === s
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                }`}
              >
                {s}
              </button>
            ))}
          </div>

          {/* Search */}
          <input
            type="search"
            placeholder="Search by name…"
            value={searchInput}
            onChange={e => setSearchInput(e.target.value)}
            className="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        {/* ── Error ── */}
        {error && (
          <div className="mb-4 px-4 py-3 rounded border border-red-200 bg-red-50 text-red-700 text-sm">
            Failed to load items: {error}
          </div>
        )}

        {/* ── Table ── */}
        <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th
                  onClick={() => handleSort('name')}
                  className="text-left px-4 py-3 font-medium text-gray-600 cursor-pointer select-none hover:text-gray-900"
                >
                  Name <SortIcon active={sort === 'name'} direction={direction} />
                </th>
                <th
                  onClick={() => handleSort('active')}
                  className="text-left px-4 py-3 font-medium text-gray-600 cursor-pointer select-none hover:text-gray-900 w-32"
                >
                  Status <SortIcon active={sort === 'active'} direction={direction} />
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {loading ? (
                <tr>
                  <td colSpan={2} className="px-4 py-10 text-center text-gray-400">
                    Loading…
                  </td>
                </tr>
              ) : items.length === 0 ? (
                <tr>
                  <td colSpan={2} className="px-4 py-10 text-center text-gray-400">
                    No items found.
                  </td>
                </tr>
              ) : (
                items.map((item, i) => (
                  <tr key={i} className="hover:bg-gray-50 transition-colors">
                    <td className="px-4 py-3 text-gray-800">{item.name || <span className="text-gray-400 italic">unnamed</span>}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                        item.active
                          ? 'bg-green-100 text-green-700'
                          : 'bg-gray-100 text-gray-500'
                      }`}>
                        {item.active ? 'Active' : 'Inactive'}
                      </span>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* ── Count ── */}
        {!loading && !error && (
          <p className="mt-3 text-xs text-gray-400">
            {items.length} {items.length === 1 ? 'item' : 'items'}
          </p>
        )}

      </div>
    </div>
  )
}

