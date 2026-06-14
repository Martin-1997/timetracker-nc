/**
 * Smoke tests for GoalsView.vue.
 *
 * Goals:
 *  - Component mounts without throwing
 *  - Shows a loading indicator initially (before the API responds)
 *  - Does NOT show the goals table while loading
 *  - Renders goal rows after the API resolves
 *
 * All @nextcloud/vue components are stubbed so no Nextcloud globals
 * are required.  The api module is mocked so no real HTTP requests
 * are made.
 */

import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import GoalsView from '../views/GoalsView.vue'

vi.mock('../api/index.js', () => ({
  default: {
    getGoals: vi.fn(() => new Promise(() => {})),    // pending forever for loading tests
    getProjects: vi.fn(() => new Promise(() => {})), // pending forever for loading tests
    addGoal: vi.fn(),
    deleteGoal: vi.fn(),
  },
}))

const ncStubs = {
  NcLoadingIcon: {
    name: 'NcLoadingIcon',
    template: '<div class="nc-loading-icon-stub" />',
  },
  NcButton: {
    name: 'NcButton',
    template: '<button><slot /></button>',
    props: ['type', 'nativeType', 'disabled'],
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
  NcIconSvgWrapper: {
    name: 'NcIconSvgWrapper',
    template: '<span />',
  },
}

describe('GoalsView', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(GoalsView, {
      global: { stubs: ncStubs },
    })
  })

  it('mounts without throwing', () => {
    expect(wrapper.exists()).toBe(true)
  })

  it('shows the loading indicator while the API request is in flight', () => {
    expect(wrapper.find('.nc-loading-icon-stub').exists()).toBe(true)
  })

  it('does not show the goals table while loading', () => {
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('has a toolbar with the Add Goal form', () => {
    expect(wrapper.find('.tt-toolbar').exists()).toBe(true)
  })

  it('shows the goals table after the API resolves', async () => {
    const api = await import('../api/index.js')
    api.default.getGoals.mockResolvedValueOnce({ data: { Goals: [] } })
    api.default.getProjects.mockResolvedValueOnce({ data: { Projects: [] } })

    const w = mount(GoalsView, { global: { stubs: ncStubs } })
    await flushPromises()

    expect(w.find('table').exists()).toBe(true)
    expect(w.find('.nc-loading-icon-stub').exists()).toBe(false)
  })

  it('renders a row for each goal returned by the API', async () => {
    const api = await import('../api/index.js')
    const mockGoals = [
      {
        id: 1,
        projectName: 'Alpha',
        hours: 40,
        interval: 'weekly',
        createdAt: 1700000000,
        workedHoursCurrentPeriod: 10,
        debtHours: 30,
        remainingHours: 30,
        totalRemainingHours: 60,
      },
      {
        id: 2,
        projectName: 'Beta',
        hours: 20,
        interval: 'monthly',
        createdAt: 1700000000,
        workedHoursCurrentPeriod: 5,
        debtHours: 15,
        remainingHours: 15,
        totalRemainingHours: 30,
      },
    ]
    api.default.getGoals.mockResolvedValueOnce({ data: { Goals: mockGoals } })
    api.default.getProjects.mockResolvedValueOnce({ data: { Projects: [] } })

    const w = mount(GoalsView, { global: { stubs: ncStubs } })
    await flushPromises()

    const rows = w.findAll('tbody tr')
    expect(rows).toHaveLength(2)
    expect(rows[0].text()).toContain('Alpha')
    expect(rows[0].text()).toContain('weekly')
    expect(rows[1].text()).toContain('Beta')
    expect(rows[1].text()).toContain('monthly')
  })

  it('renders the correct table column headers', async () => {
    const api = await import('../api/index.js')
    api.default.getGoals.mockResolvedValueOnce({ data: { Goals: [] } })
    api.default.getProjects.mockResolvedValueOnce({ data: { Projects: [] } })

    const w = mount(GoalsView, { global: { stubs: ncStubs } })
    await flushPromises()

    const headers = w.findAll('thead th')
    const headerTexts = headers.map(h => h.text())
    expect(headerTexts).toContain('Project')
    expect(headerTexts).toContain('Target Hours')
    expect(headerTexts).toContain('Interval')
  })

  it('populates the project dropdown from the projects API', async () => {
    const api = await import('../api/index.js')
    api.default.getGoals.mockResolvedValueOnce({ data: { Goals: [] } })
    api.default.getProjects.mockResolvedValueOnce({
      data: {
        Projects: [
          { id: 10, name: 'Project X' },
          { id: 20, name: 'Project Y' },
        ],
      },
    })

    const w = mount(GoalsView, { global: { stubs: ncStubs } })
    await flushPromises()

    const options = w.findAll('select option')
    const optionTexts = options.map(o => o.text())
    expect(optionTexts).toContain('Project X')
    expect(optionTexts).toContain('Project Y')
  })
})
