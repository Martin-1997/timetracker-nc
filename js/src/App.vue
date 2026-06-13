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
						<NcIconSvgWrapper :path="mdiTimer" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Dashboard" :to="{ path: '/dashboard' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiViewDashboard" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Goals" :to="{ path: '/goals' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiBullseye" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Reports" :to="{ path: '/reports' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiChartBar" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Timelines" :to="{ path: '/timelines' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiChartTimelineVariant" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="isAdmin" name="Timelines Admin" :to="{ path: '/timelines-admin' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiChartTimelineVariant" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Projects" :to="{ path: '/projects' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiBriefcase" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Clients" :to="{ path: '/clients' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiAccountGroup" :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem name="Tags" :to="{ path: '/tags' }">
					<template #icon>
						<NcIconSvgWrapper :path="mdiTag" :size="20" />
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

const mdiMenuOpen = 'M21,15.61L19.59,17L14.58,12L19.59,7L21,8.39L17.44,12L21,15.61M3,6H16V8H3V6M3,13V11H13V13H3M3,18V16H16V18H3Z'
const mdiTimer = 'M15,1H9V3H15V1M11,14H13V8H11V14M19.03,7.39L20.45,5.97C20,5.46 19.55,5 19.04,4.56L17.62,6C16.07,4.74 14.12,4 12,4A9,9 0 0,0 3,13A9,9 0 0,0 12,22C17,22 21,17.97 21,13C21,10.88 20.26,8.93 19.03,7.39Z'
const mdiViewDashboard = 'M13,3V9H21V3M13,21H21V11H13M3,21H11V15H3M3,13H11V3H3V13Z'
const mdiBullseye = 'M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10Z'
const mdiChartBar = 'M22,21H2V3H4V19H6V10H10V19H12V6H16V19H18V14H22V21Z'
const mdiChartTimelineVariant = 'M16,11.78L20.24,4.45L21.97,5.45L16.74,14.5L10.23,10.75L5.46,19H22V21H2V3H4V17.54L9.5,8L16,11.78Z'
const mdiBriefcase = 'M20,6C20.58,6 21.05,6.2 21.42,6.59C21.8,6.97 22,7.42 22,7.92V19.08C22,19.58 21.8,20.03 21.42,20.41C21.05,20.8 20.58,21 20,21H4C3.42,21 2.95,20.8 2.58,20.41C2.2,20.03 2,19.58 2,19.08V7.92C2,7.42 2.2,6.97 2.58,6.59C2.95,6.2 3.42,6 4,6H8V4C8,3.42 8.2,2.95 8.58,2.58C8.95,2.2 9.42,2 10,2H14C14.58,2 15.05,2.2 15.42,2.58C15.8,2.95 16,3.42 16,4V6H20M4,8V19H20V8H4M14,6V4H10V6H14Z'
const mdiAccountGroup = 'M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25M0,20V18.5C0,17.11 1.89,15.94 4.45,15.6C3.86,16.28 3.5,17.22 3.5,18.25V20H0M24,20H20.5V18.25C20.5,17.22 20.14,16.28 19.55,15.6C22.11,15.94 24,17.11 24,18.5V20Z'
const mdiTag = 'M5.5,7A1.5,1.5 0 0,1 4,5.5A1.5,1.5 0 0,1 5.5,4A1.5,1.5 0 0,1 7,5.5A1.5,1.5 0 0,1 5.5,7M21.41,11.58L12.41,2.58C12.05,2.22 11.55,2 11,2H4C2.89,2 2,2.89 2,4V11C2,11.55 2.22,12.05 2.59,12.41L11.59,21.41C11.95,21.77 12.45,22 13,22C13.55,22 14.05,21.77 14.41,21.41L21.41,14.41C21.77,14.05 22,13.55 22,13C22,12.45 21.77,11.95 21.41,11.58Z'

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
