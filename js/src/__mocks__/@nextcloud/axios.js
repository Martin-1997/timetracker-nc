/**
 * Minimal mock for @nextcloud/axios.
 * The real package wraps axios with Nextcloud-specific interceptors that
 * read window.OC globals which do not exist in a jsdom test environment.
 * Tests that need specific responses should override these with vi.fn().mockResolvedValue(...)
 */
import { vi } from 'vitest'

export default {
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
  patch: vi.fn(),
}
