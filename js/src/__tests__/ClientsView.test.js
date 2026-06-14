/**
 * Smoke tests for ClientsView.vue.
 *
 * Goals:
 *  - Component mounts without throwing
 *  - Shows a loading indicator initially (before the API responds)
 *  - Does NOT show the table while loading
 *
 * All @nextcloud/vue components are stubbed globally so we don't
 * need a real Nextcloud environment.  The api module is mocked so
 * no real HTTP requests are made.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import ClientsView from '../views/ClientsView.vue'

// Mock the API module — return a promise that never resolves during the
// synchronous "loading" phase, so we can assert the loading state.
vi.mock('../api/index.js', () => ({
  default: {
    getClients: vi.fn(() => new Promise(() => {})), // pending forever for loading tests
    addClient: vi.fn(),
    editClient: vi.fn(),
    deleteClient: vi.fn(),
  },
}))

// Common stubs for @nextcloud/vue components.
const ncStubs = {
  NcLoadingIcon: {
    name: 'NcLoadingIcon',
    template: '<div class="nc-loading-icon-stub" />',
  },
  NcButton: {
    name: 'NcButton',
    template: '<button><slot /></button>',
  },
  NcModal: {
    name: 'NcModal',
    template: '<div class="nc-modal-stub"><slot /></div>',
  },
  NcActions: {
    name: 'NcActions',
    template: '<div><slot /></div>',
  },
  NcActionButton: {
    name: 'NcActionButton',
    template: '<button><slot /></button>',
  },
  NcTextField: {
    name: 'NcTextField',
    template: '<input />',
    props: ['modelValue', 'label', 'placeholder'],
  },
  NcIconSvgWrapper: {
    name: 'NcIconSvgWrapper',
    template: '<span />',
  },
}

describe('ClientsView', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(ClientsView, {
      global: { stubs: ncStubs },
    })
  })

  it('mounts without throwing', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('shows the loading indicator while the API request is in flight', () => {
    // The component sets loading = true initially, and getClients returns a
    // never-resolving promise, so loading remains true here.
    expect(wrapper.find('.nc-loading-icon-stub').exists()).toBe(true)
  })

  it('does not show the client table while loading', () => {
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('has an "Add Client" form in the toolbar', () => {
    expect(wrapper.find('.tt-toolbar').exists()).toBe(true)
  })

  it('shows the client table after the API resolves', async () => {
    const api = await import('../api/index.js')
    api.default.getClients.mockResolvedValueOnce({
      data: { Clients: [{ id: 1, name: 'Acme Corp' }] },
    })

    // Re-mount so onMounted runs with the new mock
    const w = mount(ClientsView, { global: { stubs: ncStubs } })
    await flushPromises()

    expect(w.find('table').exists()).toBe(true)
    expect(w.find('.nc-loading-icon-stub').exists()).toBe(false)
  })

  it('renders a row for each client returned by the API', async () => {
    const api = await import('../api/index.js')
    api.default.getClients.mockResolvedValueOnce({
      data: {
        Clients: [
          { id: 1, name: 'Client A' },
          { id: 2, name: 'Client B' },
          { id: 3, name: 'Client C' },
        ],
      },
    })

    const w = mount(ClientsView, { global: { stubs: ncStubs } })
    await flushPromises()

    const rows = w.findAll('tbody tr')
    expect(rows).toHaveLength(3)
    expect(rows[0].text()).toContain('Client A')
    expect(rows[1].text()).toContain('Client B')
    expect(rows[2].text()).toContain('Client C')
  })

  it('numbers rows starting from 1', async () => {
    const api = await import('../api/index.js')
    api.default.getClients.mockResolvedValueOnce({
      data: { Clients: [{ id: 10, name: 'X' }, { id: 20, name: 'Y' }] },
    })

    const w = mount(ClientsView, { global: { stubs: ncStubs } })
    await flushPromises()

    const cells = w.findAll('tbody tr td:first-child')
    expect(cells[0].text()).toBe('1')
    expect(cells[1].text()).toBe('2')
  })
})
