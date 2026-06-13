<template>
	<NcContent app-name="timetracker">
		<NcAppNavigation aria-label="Timetracker navigation">
			<!-- Custom close button at the top of the nav panel -->
			<template #default>
				<div class="tt-nav-header">
					<NcButton
						variant="tertiary"
						aria-label="Close navigation"
						title="Close navigation"
						@click="closeNav"
					>
						<template #icon>
							<NcIconSvgWrapper :path="mdiMenuOpen" :size="20" />
						</template>
					</NcButton>
				</div>
			</template>
			<template #list>
				<NcAppNavigationItem name="Timer" :to="{ path: '/timer' }">
					<template #icon>
						<span class="nav-icon-timer svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Dashboard" :to="{ path: '/dashboard' }">
					<template #icon>
						<span class="nav-icon-dashboard svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Goals" :to="{ path: '/goals' }">
					<template #icon>
						<span class="nav-icon-goals svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Reports" :to="{ path: '/reports' }">
					<template #icon>
						<span class="nav-icon-reports svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Timelines" :to="{ path: '/timelines' }">
					<template #icon>
						<span class="nav-icon-reports svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="isAdmin" name="Timelines Admin" :to="{ path: '/timelines-admin' }">
					<template #icon>
						<span class="nav-icon-reports svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Projects" :to="{ path: '/projects' }">
					<template #icon>
						<span class="nav-icon-projects svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Clients" :to="{ path: '/clients' }">
					<template #icon>
						<span class="nav-icon-clients svg" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Tags" :to="{ path: '/tags' }">
					<template #icon>
						<span class="nav-icon-tags svg" />
					</template>
				</NcAppNavigationItem>
			</template>
		</NcAppNavigation>
		<NcAppContent>
			<RouterView />
		</NcAppContent>
	</NcContent>
</template>

<script setup>
import { NcContent, NcAppNavigation, NcAppNavigationItem, NcAppContent, NcButton, NcIconSvgWrapper } from '@nextcloud/vue'
import { RouterView } from 'vue-router'
import { emit } from '@nextcloud/event-bus'

const isAdmin = window.oc_isadmin ?? false

// MDI path for the "menu open" (hamburger with arrow) icon — same one NcAppNavigationToggle uses
const mdiMenuOpen = 'M21,15.61L19.59,17L14.58,12L19.59,7L21,8.39L17.44,12L21,15.61M3,6H16V8H3V6M3,13V11H13V13H3M3,18V16H16V18H3Z'

function closeNav() {
	emit('toggle-navigation', { open: false })
}
</script>

<style>
#timetracker-app {
	height: 100%;
	display: flex;
	flex-direction: column;
}

/* Close button at top of the nav panel */
.tt-nav-header {
	display: flex;
	justify-content: flex-end;
	padding: var(--app-navigation-padding, 8px) var(--app-navigation-padding, 8px) 0;
}

/* When nav is OPEN: hide the built-in NcAppNavigationToggle (our close button handles it) */
#timetracker-app .app-navigation:not(.app-navigation--closed) .app-navigation-toggle-wrapper {
	display: none;
}

/* When nav is CLOSED: restore the built-in toggle to its original position so it stays
   visible at the left edge of the content area and can be clicked to reopen the nav */
#timetracker-app .app-navigation--closed .app-navigation-toggle-wrapper {
	display: block;
	inset-inline-end: calc(0px - var(--app-navigation-padding, 8px));
	margin-inline-end: calc(-1 * var(--default-clickable-area, 44px));
}
</style>
