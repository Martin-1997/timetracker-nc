/**
 * Minimal mock for @nextcloud/vue.
 *
 * The real package ships pre-built ES modules that include CSS imports
 * (e.g. NcActionButton-DLer-aUY.css) which Vitest / Node cannot handle
 * without a dedicated CSS transform plugin.
 *
 * We export stub Vue components for every component used across the
 * timetracker views so that mounting those views in jsdom works without
 * requiring any Nextcloud-specific browser globals or CSS pipeline.
 *
 * Add entries here if new @nextcloud/vue components are imported in tests.
 */
import { defineComponent, h } from 'vue'

/** Creates a pass-through stub component with a given name. */
function stub(name) {
  return defineComponent({
    name,
    // Accept any props without warnings
    props: { modelValue: null },
    emits: ['update:modelValue', 'close', 'change', 'click'],
    setup(_, { slots }) {
      return () => h('div', { class: `nc-stub nc-stub-${name.toLowerCase()}` }, slots.default?.())
    },
  })
}

export const NcButton = stub('NcButton')
export const NcModal = stub('NcModal')
export const NcActions = stub('NcActions')
export const NcActionButton = stub('NcActionButton')
export const NcLoadingIcon = defineComponent({
  name: 'NcLoadingIcon',
  props: { size: Number },
  setup() {
    return () => h('div', { class: 'nc-loading-icon-stub' })
  },
})
export const NcTextField = defineComponent({
  name: 'NcTextField',
  props: ['modelValue', 'label', 'placeholder'],
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () => h('input', {
      value: props.modelValue,
      onInput: (e) => emit('update:modelValue', e.target.value),
    })
  },
})
export const NcIconSvgWrapper = defineComponent({
  name: 'NcIconSvgWrapper',
  props: ['path', 'size'],
  setup() {
    return () => h('span', { class: 'nc-icon-stub' })
  },
})
export const NcDateTimePicker = defineComponent({
  name: 'NcDateTimePicker',
  props: ['modelValue', 'type', 'minuteStep', 'appendToBody'],
  emits: ['update:modelValue'],
  setup() {
    return () => h('input', { type: 'text', class: 'nc-datetimepicker-stub' })
  },
})
export const NcAppNavigation = stub('NcAppNavigation')
export const NcAppNavigationItem = stub('NcAppNavigationItem')
export const NcAppContent = stub('NcAppContent')
export const NcContent = stub('NcContent')
