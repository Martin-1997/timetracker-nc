<template>
	<NcContent app-name="timetracker">
		<NcAppNavigation>
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
import { NcContent, NcAppNavigation, NcAppNavigationItem, NcAppContent } from '@nextcloud/vue'
import { RouterView } from 'vue-router'

const isAdmin = window.oc_isadmin ?? false
</script>

<style>
#timetracker-app {
	height: 100%;
	display: flex;
	flex-direction: column;
}
</style>
