import { createRouter, createWebHashHistory } from 'vue-router'
import TimerView from '../views/TimerView.vue'
import ClientsView from '../views/ClientsView.vue'
import ProjectsView from '../views/ProjectsView.vue'
import TagsView from '../views/TagsView.vue'
import GoalsView from '../views/GoalsView.vue'
import ReportsView from '../views/ReportsView.vue'
import DashboardView from '../views/DashboardView.vue'
import TimelinesView from '../views/TimelinesView.vue'
import TimelinesAdminView from '../views/TimelinesAdminView.vue'

export default createRouter({
	history: createWebHashHistory(),
	routes: [
		{ path: '/', redirect: '/timer' },
		{ path: '/timer', component: TimerView },
		{ path: '/clients', component: ClientsView },
		{ path: '/projects', component: ProjectsView },
		{ path: '/tags', component: TagsView },
		{ path: '/goals', component: GoalsView },
		{ path: '/reports', component: ReportsView },
		{ path: '/dashboard', component: DashboardView },
		{ path: '/timelines', component: TimelinesView },
		{ path: '/timelines-admin', component: TimelinesAdminView },
	],
})
