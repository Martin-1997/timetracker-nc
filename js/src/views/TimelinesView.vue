<template>
	<div class="tt-view">
		<div class="tt-date-range">
			<input v-model="startDate" type="date" class="tt-input" @change="fetchReport">
			<span>–</span>
			<input v-model="endDate" type="date" class="tt-input" @change="fetchReport">
			<div class="tt-quick-ranges">
				<NcButton size="small" @click="setRange(29)">
					Last 30 days
				</NcButton>
				<NcButton size="small" @click="setThisMonth">
					This month
				</NcButton>
			</div>
		</div>

		<div class="tt-report-controls">
			<label>
				Group 1
				<select v-model="group1" class="tt-select">
					<option value="">None</option>
					<option value="project">Project</option>
					<option value="user">User</option>
					<option value="client">Client</option>
				</select>
			</label>
			<label>
				Group 2
				<select v-model="group2" class="tt-select">
					<option value="">None</option>
					<option value="project">Project</option>
					<option value="user">User</option>
					<option value="client">Client</option>
				</select>
			</label>
			<label>
				Time group
				<select v-model="group3" class="tt-select">
					<option value="">None</option>
					<option value="day">Day</option>
					<option value="week">Week</option>
					<option value="month">Month</option>
					<option value="year">Year</option>
				</select>
			</label>
			<label>
				Project filter
				<select v-model="filterProjectId" class="tt-select">
					<option value="">All projects</option>
					<option v-for="p in projects" :key="p.id" :value="p.id">
						{{ p.name }}
					</option>
				</select>
			</label>
			<label>
				Client filter
				<select v-model="filterClientId" class="tt-select">
					<option value="">All clients</option>
					<option v-for="c in clients" :key="c.id" :value="c.id">
						{{ c.name }}
					</option>
				</select>
			</label>
		</div>

		<div class="tt-report-export">
			<NcButton type="primary" @click="createTimeline">
				Create Timeline
			</NcButton>
			<NcButton @click="emailTimelineNew">
				Send by Email
			</NcButton>
		</div>

		<h3>Timelines</h3>
		<NcLoadingIcon v-if="loading" :size="40" />
		<table v-else class="tt-table">
			<thead>
				<tr>
					<th>#</th>
					<th>ID</th>
					<th>Status</th>
					<th>When</th>
					<th>Duration</th>
					<th>Created At</th>
					<th>Download</th>
					<th>Email</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="(tl, i) in timelines" :key="tl.id">
					<td>{{ i + 1 }}</td>
					<td>{{ tl.id }}</td>
					<td>{{ tl.status }}</td>
					<td>{{ tl.timeInterval }}</td>
					<td>{{ formatDuration(tl.totalDuration) }}</td>
					<td>{{ new Date(tl.createdAt * 1000).toLocaleString() }}</td>
					<td><a :href="downloadUrl(tl.id)" target="_blank">Download</a></td>
					<td>
						<NcButton size="small" @click="openEmailDialog(tl)">
							Email
						</NcButton>
					</td>
					<td>
						<NcButton size="small" @click="openDelete(tl)">
							Delete
						</NcButton>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Email Dialog -->
		<NcModal v-if="showEmail" name="Email timeline" @close="showEmail = false">
			<div class="tt-modal-body">
				<h2>Email timeline</h2>
				<label>
					Email
					<input v-model="emailAddress" type="email" class="tt-input">
				</label>
				<label>
					Subject
					<input v-model="emailSubject" type="text" class="tt-input">
				</label>
				<label>
					Content
					<textarea v-model="emailContent" class="tt-textarea" rows="5" />
				</label>
				<div class="tt-modal-actions">
					<NcButton type="primary" @click="sendEmail">
						Send
					</NcButton>
					<NcButton @click="showEmail = false">
						Cancel
					</NcButton>
				</div>
			</div>
		</NcModal>

		<!-- Delete Confirm -->
		<NcModal v-if="showDelete" name="Delete timeline" @close="showDelete = false">
			<div class="tt-modal-body">
				<h2>Delete timeline</h2>
				<p>Delete this timeline? This cannot be undone.</p>
				<div class="tt-modal-actions">
					<NcButton type="error" @click="doDelete">
						Delete
					</NcButton>
					<NcButton @click="showDelete = false">
						Cancel
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { NcButton, NcModal, NcLoadingIcon } from '@nextcloud/vue'
import api from '../api/index.js'

const today = new Date()
const thirtyAgo = new Date(today.getTime() - 29 * 86400000)
const startDate = ref(thirtyAgo.toISOString().split('T')[0])
const endDate = ref(today.toISOString().split('T')[0])
const group1 = ref('project')
const group2 = ref('user')
const group3 = ref('day')
const filterProjectId = ref('')
const filterClientId = ref('')

const projects = ref([])
const clients = ref([])
const timelines = ref([])
const loading = ref(false)

const showEmail = ref(false)
const emailTimelineId = ref(null)
const emailAddress = ref('')
const emailSubject = ref('')
const emailContent = ref('')

const showDelete = ref(false)
const deleteTarget = ref(null)

function setRange(daysBack) {
	const now = new Date()
	startDate.value = new Date(now.getTime() - daysBack * 86400000).toISOString().split('T')[0]
	endDate.value = now.toISOString().split('T')[0]
}

function setThisMonth() {
	const now = new Date()
	startDate.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
	endDate.value = now.toISOString().split('T')[0]
}

function formatDuration(seconds) {
	const h = Math.floor(seconds / 3600)
	const m = Math.floor((seconds % 3600) / 60)
	const s = seconds % 60
	return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function downloadUrl(id) {
	return api.downloadTimelineUrl(id)
}

function getReportParams() {
	return {
		from: Math.floor(new Date(startDate.value).getTime() / 1000),
		to: Math.floor(new Date(endDate.value + 'T23:59:59').getTime() / 1000),
		group1: group1.value,
		group2: group2.value,
		timegroup: group3.value,
		filterProjectId: filterProjectId.value,
		filterClientId: filterClientId.value,
	}
}

async function fetchReport() {
	// no-op: report results shown in TimelinesView come from the timelines list
}

async function fetchTimelines() {
	loading.value = true
	const { data } = await api.getTimelines()
	timelines.value = data.Timelines
	loading.value = false
}

async function createTimeline() {
	await api.createTimeline(getReportParams())
	await fetchTimelines()
}

async function emailTimelineNew() {
	await api.emailTimeline(getReportParams())
	await fetchTimelines()
}

function openEmailDialog(tl) {
	emailTimelineId.value = tl.id
	emailAddress.value = ''
	emailSubject.value = ''
	emailContent.value = ''
	showEmail.value = true
}

async function sendEmail() {
	await api.emailTimelineById(emailTimelineId.value, {
		email: emailAddress.value,
		subject: emailSubject.value,
		content: emailContent.value,
	})
	showEmail.value = false
}

function openDelete(tl) {
	deleteTarget.value = tl
	showDelete.value = true
}

async function doDelete() {
	await api.deleteTimeline(deleteTarget.value.id)
	showDelete.value = false
	deleteTarget.value = null
	await fetchTimelines()
}

onMounted(async () => {
	const [pr, cr] = await Promise.all([api.getProjects(), api.getClients()])
	projects.value = pr.data.Projects
	clients.value = cr.data.Clients
	await fetchTimelines()
})
</script>
