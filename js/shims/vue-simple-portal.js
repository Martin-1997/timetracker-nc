/**
 * Vue 3 compatible shim for @linusborg/vue-simple-portal@0.1.5
 *
 * The original package uses Vue.extend() at module evaluation time,
 * which crashes with Vue 3 (no default export). This shim replaces it
 * with defineComponent-based equivalents that work with Vue 3 Teleport.
 */
import { defineComponent, h, Teleport, ref } from 'vue'

const transports = ref({})

export const Wormhole = {
	transports: transports.value,
	open(transport) {
		transports.value[transport.to] = transport.passengers
	},
	close(transport) {
		delete transports.value[transport.to]
	},
	hasTarget(to) {
		return Object.prototype.hasOwnProperty.call(transports.value, to)
	},
}

export const PortalTarget = defineComponent({
	name: 'PortalTarget',
	props: {
		name: { type: String, required: true },
		multiple: { type: Boolean, default: false },
		slim: { type: Boolean, default: false },
		slotProps: { type: Object, default: () => ({}) },
		tag: { type: String, default: 'div' },
		transition: { type: Object, default: null },
		transitionEvents: { type: Object, default: null },
	},
	setup(props, { slots }) {
		return () => h(props.tag || 'div', {
			'data-portal-target': props.name,
			class: 'portal-target',
		}, slots.default?.())
	},
})

export const Portal = defineComponent({
	name: 'Portal',
	props: {
		to: { type: String, required: true },
		disabled: { type: Boolean, default: false },
		slim: { type: Boolean, default: false },
		tag: { type: String, default: 'div' },
	},
	setup(props, { slots }) {
		return () => {
			if (props.disabled) {
				return slots.default?.()
			}
			// Use Vue 3 Teleport to body as a safe fallback
			try {
				return h(Teleport, { to: 'body' }, slots.default?.())
			} catch (_e) {
				return slots.default?.()
			}
		}
	},
})

export default { Portal, PortalTarget, Wormhole }
