import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const url = (path) => generateUrl('/apps/timetracker' + path)

export default {
	getWorkIntervals: (from, to) =>
		axios.get(url('/ajax/work-intervals'), { params: { from, to, tzoffset: new Date().getTimezoneOffset() } }),
	startTimer: (name, projectId, tags) =>
		axios.post(url(`/ajax/start-timer/${encodeURIComponent(encodeURIComponent(name))}`), { projectId, tags }),
	stopTimer: (name) =>
		axios.post(url(`/ajax/stop-timer/${encodeURIComponent(encodeURIComponent(name))}`)),
	addWorkInterval: (name, start, end, details) =>
		axios.post(url(`/ajax/add-work-interval/${encodeURIComponent(encodeURIComponent(name))}`), {
			start, end, tzoffset: new Date().getTimezoneOffset(), details,
		}),
	updateWorkInterval: (id, data) =>
		axios.post(url(`/ajax/update-work-interval/${id}`), data),
	deleteWorkInterval: (id) =>
		axios.post(url(`/ajax/delete-work-interval/${id}`)),

	getClients: () => axios.get(url('/ajax/clients')),
	addClient: (name) => axios.post(url(`/ajax/add-client/${encodeURIComponent(name)}`)),
	editClient: (id, name) => axios.post(url(`/ajax/edit-client/${id}`), { name }),
	deleteClient: (id) => axios.post(url(`/ajax/delete-client/${id}`)),

	getTags: (workItemId) => axios.get(url('/ajax/tags'), workItemId ? { params: { workItem: workItemId } } : {}),
	addTag: (name) => axios.post(url(`/ajax/add-tag/${encodeURIComponent(name)}`)),
	editTag: (id, name) => axios.post(url(`/ajax/edit-tag/${id}`), { name }),
	deleteTag: (id) => axios.post(url(`/ajax/delete-tag/${id}`)),

	getProjects: () => axios.get(url('/ajax/projects')),
	getProjectsTable: (archived) =>
		axios.get(url('/ajax/projects-table'), { params: { archived: archived ? 1 : 0 } }),
	addProject: (name, clientId, color) =>
		axios.post(url(`/ajax/add-project/${encodeURIComponent(name)}`), { clientId, color }),
	editProject: (id, data) => axios.post(url(`/ajax/edit-project/${id}`), data),
	deleteProject: (id) => axios.post(url(`/ajax/delete-project-with-data/${id}`)),

	getGoals: () => axios.get(url('/ajax/goals')),
	addGoal: (projectId, hours, interval) =>
		axios.post(url('/ajax/add-goal'), { projectId, hours, interval }),
	deleteGoal: (id) => axios.post(url(`/ajax/delete-goal/${id}`)),

	getReport: (params) => axios.get(url('/ajax/report'), { params }),

	getTimelines: () => axios.get(url('/ajax/timelines')),
	getTimelinesAdmin: () => axios.get(url('/ajax/timelines-admin')),
	createTimeline: (data) => axios.post(url('/ajax/timeline'), data),
	emailTimeline: (data) => axios.post(url('/ajax/email-timeline'), data),
	emailTimelineById: (id, data) => axios.post(url(`/ajax/email-timeline/${id}`), data),
	deleteTimeline: (id) => axios.post(url(`/ajax/delete-timeline/${id}`)),
	editTimeline: (id, data) => axios.post(url(`/ajax/edit-timeline/${id}`), data),
	downloadTimelineUrl: (id) => url(`/ajax/download-timeline/${id}`),

	getUsers: () => axios.get(
		generateUrl('/ocs/v2.php/cloud/users/details'),
		{ headers: { 'OCS-APIRequest': 'true' } },
	),
}
