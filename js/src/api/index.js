import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const url = (path) => generateUrl('/apps/timetracker' + path)

export default {
	getWorkIntervals: (from, to) =>
		axios.get(url('/api/v1/work-intervals'), { params: { from, to, tzoffset: new Date().getTimezoneOffset() } }),
	startTimer: (name, projectId, tags) =>
		axios.post(url('/api/v1/timer/start'), { name, projectId, tags }),
	stopTimer: (name) =>
		axios.post(url('/api/v1/timer/stop'), { name }),
	addWorkInterval: (name, start, end, details) =>
		axios.post(url('/api/v1/work-intervals'), {
			name, start, end, tzoffset: new Date().getTimezoneOffset(), details,
		}),
	updateWorkInterval: (id, data) =>
		axios.put(url(`/api/v1/work-intervals/${id}`), data),
	deleteWorkInterval: (id) =>
		axios.delete(url(`/api/v1/work-intervals/${id}`)),

	getClients: () => axios.get(url('/api/v1/clients')),
	addClient: (name) => axios.post(url('/api/v1/clients'), { name }),
	editClient: (id, name) => axios.put(url(`/api/v1/clients/${id}`), { name }),
	deleteClient: (id) => axios.delete(url(`/api/v1/clients/${id}`)),

	getTags: (workItemId) => axios.get(url('/api/v1/tags'), workItemId ? { params: { workItem: workItemId } } : {}),
	addTag: (name) => axios.post(url('/api/v1/tags'), { name }),
	editTag: (id, name) => axios.put(url(`/api/v1/tags/${id}`), { name }),
	deleteTag: (id) => axios.delete(url(`/api/v1/tags/${id}`)),

	getProjects: () => axios.get(url('/api/v1/projects')),
	getProjectsTable: (archived) =>
		axios.get(url('/api/v1/projects'), { params: { view: 'table', archived: archived ? 1 : 0 } }),
	addProject: (name, clientId, color) =>
		axios.post(url('/api/v1/projects'), { name, clientId, color }),
	editProject: (id, data) => axios.put(url(`/api/v1/projects/${id}`), data),
	deleteProject: (id) => axios.delete(url(`/api/v1/projects/${id}`)),

	getGoals: () => axios.get(url('/api/v1/goals')),
	addGoal: (projectId, hours, interval) =>
		axios.post(url('/api/v1/goals'), { projectId, hours, interval }),
	deleteGoal: (id) => axios.delete(url(`/api/v1/goals/${id}`)),

	getReport: (params) => axios.get(url('/api/v1/report'), { params }),

	getTimelines: () => axios.get(url('/api/v1/timelines')),
	getTimelinesAdmin: () => axios.get(url('/api/v1/timelines-admin')),
	createTimeline: (data) => axios.post(url('/api/v1/timelines'), data),
	emailTimeline: (data) => axios.post(url('/api/v1/timelines'), data),
	emailTimelineById: (id, data) => axios.post(url(`/api/v1/timelines/${id}/email`), data),
	deleteTimeline: (id) => axios.delete(url(`/api/v1/timelines/${id}`)),
	editTimeline: (id, data) => axios.put(url(`/api/v1/timelines/${id}`), data),
	downloadTimelineUrl: (id) => url(`/api/v1/timelines/${id}/download`),

	getUsers: () => axios.get(
		generateUrl('/ocs/v2.php/cloud/users/details'),
		{ headers: { 'OCS-APIRequest': 'true' } },
	),
}
