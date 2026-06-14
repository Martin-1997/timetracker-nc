import { describe, it, expect } from 'vitest'
import { startOfDay, endOfDay } from '../date.js'

describe('startOfDay', () => {
  it('returns unix seconds at 00:00:00 of the given date', () => {
    const date = new Date(2024, 0, 15) // 2024-01-15 in local time
    const result = startOfDay(date)
    const expected = Math.floor(new Date(2024, 0, 15, 0, 0, 0).getTime() / 1000)
    expect(result).toBe(expected)
  })

  it('returns an integer (no fractional seconds)', () => {
    const date = new Date(2024, 5, 1)
    expect(Number.isInteger(startOfDay(date))).toBe(true)
  })

  it('midnight of the result date has hours/minutes/seconds of zero', () => {
    const date = new Date(2024, 11, 31)
    const result = startOfDay(date)
    const reconstructed = new Date(result * 1000)
    expect(reconstructed.getHours()).toBe(0)
    expect(reconstructed.getMinutes()).toBe(0)
    expect(reconstructed.getSeconds()).toBe(0)
  })

  it('is always less than or equal to endOfDay for the same date', () => {
    const date = new Date(2024, 3, 10)
    expect(startOfDay(date)).toBeLessThan(endOfDay(date))
  })

  it('handles the first day of a month', () => {
    const date = new Date(2023, 0, 1) // 2023-01-01
    const result = startOfDay(date)
    const reconstructed = new Date(result * 1000)
    expect(reconstructed.getDate()).toBe(1)
    expect(reconstructed.getMonth()).toBe(0)
    expect(reconstructed.getFullYear()).toBe(2023)
  })
})

describe('endOfDay', () => {
  it('returns unix seconds at 23:59:59 of the given date', () => {
    const date = new Date(2024, 0, 15) // 2024-01-15 in local time
    const result = endOfDay(date)
    const expected = Math.floor(new Date(2024, 0, 15, 23, 59, 59).getTime() / 1000)
    expect(result).toBe(expected)
  })

  it('returns an integer (no fractional seconds)', () => {
    const date = new Date(2024, 5, 1)
    expect(Number.isInteger(endOfDay(date))).toBe(true)
  })

  it('the result maps to 23:59:59 in local time', () => {
    const date = new Date(2024, 11, 31)
    const result = endOfDay(date)
    const reconstructed = new Date(result * 1000)
    expect(reconstructed.getHours()).toBe(23)
    expect(reconstructed.getMinutes()).toBe(59)
    expect(reconstructed.getSeconds()).toBe(59)
  })

  it('is exactly 86399 seconds after startOfDay', () => {
    const date = new Date(2024, 6, 4)
    expect(endOfDay(date) - startOfDay(date)).toBe(86399)
  })

  it('handles the last day of a year', () => {
    const date = new Date(2023, 11, 31) // 2023-12-31
    const result = endOfDay(date)
    const reconstructed = new Date(result * 1000)
    expect(reconstructed.getDate()).toBe(31)
    expect(reconstructed.getMonth()).toBe(11)
    expect(reconstructed.getFullYear()).toBe(2023)
  })
})
