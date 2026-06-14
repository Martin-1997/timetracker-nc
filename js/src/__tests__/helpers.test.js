/**
 * Tests for pure helper functions extracted from TimerView.vue.
 * The functions are inlined here to avoid importing the component (which
 * pulls in @nextcloud/vue and triggers complex Nextcloud globals) and to
 * keep the tests self-contained.  If the implementations ever change,
 * these copies must be updated to match.
 *
 * Source: js/src/views/TimerView.vue — formatDuration() and truncate()
 */

import { describe, it, expect } from 'vitest'

// ---- Copy of formatDuration from TimerView.vue ----
function formatDuration(seconds) {
  const d = Math.floor(seconds / 86400)
  const h = Math.floor((seconds % 86400) / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  const pad = (n) => String(n).padStart(2, '0')
  if (d > 0) return `${d} days ${pad(h)}:${pad(m)}:${pad(s)}`
  return `${pad(h)}:${pad(m)}:${pad(s)}`
}

// ---- Copy of truncate from TimerView.vue ----
function truncate(s, n) {
  if (!s) return ''
  return s.length < n ? s : s.substring(0, n - 4) + ' ...'
}

// ----------------------------------------------------------------
// formatDuration tests
// ----------------------------------------------------------------

describe('formatDuration', () => {
  it('formats zero seconds as 00:00:00', () => {
    expect(formatDuration(0)).toBe('00:00:00')
  })

  it('formats sub-minute seconds', () => {
    expect(formatDuration(45)).toBe('00:00:45')
  })

  it('formats exactly one minute', () => {
    expect(formatDuration(60)).toBe('00:01:00')
  })

  it('formats exactly one hour', () => {
    expect(formatDuration(3600)).toBe('01:00:00')
  })

  it('pads hours, minutes, seconds to two digits', () => {
    // 1 h 2 m 3 s
    expect(formatDuration(3600 + 120 + 3)).toBe('01:02:03')
  })

  it('formats a realistic work duration (2h 30m 15s)', () => {
    const seconds = 2 * 3600 + 30 * 60 + 15
    expect(formatDuration(seconds)).toBe('02:30:15')
  })

  it('formats exactly one day using "days" prefix', () => {
    expect(formatDuration(86400)).toBe('1 days 00:00:00')
  })

  it('formats multiple days with remaining hours/minutes/seconds', () => {
    // 2 days + 3 hours + 4 minutes + 5 seconds
    const seconds = 2 * 86400 + 3 * 3600 + 4 * 60 + 5
    expect(formatDuration(seconds)).toBe('2 days 03:04:05')
  })

  it('does not show days prefix when value is under 86400 seconds', () => {
    // 23h 59m 59s — one second before midnight
    expect(formatDuration(86399)).toBe('23:59:59')
  })

  it('pads single-digit seconds correctly', () => {
    expect(formatDuration(9)).toBe('00:00:09')
  })

  it('handles exactly 10 hours', () => {
    expect(formatDuration(36000)).toBe('10:00:00')
  })
})

// ----------------------------------------------------------------
// truncate tests
// ----------------------------------------------------------------

describe('truncate', () => {
  it('returns empty string for falsy input (null)', () => {
    expect(truncate(null, 10)).toBe('')
  })

  it('returns empty string for falsy input (undefined)', () => {
    expect(truncate(undefined, 10)).toBe('')
  })

  it('returns empty string for empty string input', () => {
    expect(truncate('', 10)).toBe('')
  })

  it('returns the string unchanged when shorter than n', () => {
    expect(truncate('hello', 10)).toBe('hello')
  })

  it('returns the string unchanged when length equals n - 1 (strictly less than n)', () => {
    // length 9 < 10 → returned as-is
    expect(truncate('123456789', 10)).toBe('123456789')
  })

  it('truncates when string length equals n (not strictly less)', () => {
    // length 10 is NOT < 10, so truncation applies: first (10-4)=6 chars + " ..."
    expect(truncate('1234567890', 10)).toBe('123456 ...')
  })

  it('truncates a long string and appends " ..."', () => {
    const long = 'The quick brown fox jumps over the lazy dog'
    const result = truncate(long, 20)
    expect(result).toBe('The quick brown  ...')
    expect(result.length).toBe(20)
  })

  it('suffix is exactly " ..." (space + three dots)', () => {
    const result = truncate('abcdefgh', 6)
    expect(result.endsWith(' ...')).toBe(true)
  })

  it('truncated result has length equal to n', () => {
    const str = 'a'.repeat(100)
    const n = 30
    const result = truncate(str, n)
    expect(result.length).toBe(n)
  })

  it('handles n smaller than 4 without crashing (edge case)', () => {
    // n=3: n-4 = -1, substring(0, -1) = '' in JS → '' + ' ...'
    const result = truncate('hello', 3)
    expect(typeof result).toBe('string')
  })
})
