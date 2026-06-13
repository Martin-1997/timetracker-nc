<template>
	<div class="tt-view">
		<div class="tt-date-range">
			<input v-model="startDate" type="date" class="tt-input" @change="fetchReport">
			<span>–</span>
			<input v-model="endDate" type="date" class="tt-input" @change="fetchReport">
			<div class="tt-quick-ranges">
				<NcButton size="small" @click="setRange(0)">
					Today
				</NcButton>
				<NcButton size="small" @click="setRange(6)">
					Last 7 days
				</NcButton>
				<NcButton size="small" @click="setRange(29)">
					Last 30 days
				</NcButton>
				<NcButton size="small" @click="setRange(89)">
					Last 90 days
				</NcButton>
				<NcButton size="small" @click="setRange(364)">
					Last 365 days
				</NcButton>
				<NcButton size="small" @click="setThisMonth">
					This month
				</NcButton>
			</div>
		</div>

		<div class="tt-report-controls">
			<label>
				Group 1
				<select v-model="group1" class="tt-select" @change="fetchReport">
					<option value="">None</option>
					<option value="project">Project</option>
					<option value="user">User</option>
					<option value="client">Client</option>
				</select>
			</label>
			<label>
				Group 2
				<select v-model="group2" class="tt-select" @change="fetchReport">
					<option value="">None</option>
					<option value="project">Project</option>
					<option value="user">User</option>
					<option value="client">Client</option>
				</select>
			</label>
			<label>
				Time group
				<select v-model="group3" class="tt-select" @change="fetchReport">
					<option value="">None</option>
					<option value="day">Day</option>
					<option value="week">Week</option>
					<option value="month">Month</option>
					<option value="year">Year</option>
				</select>
			</label>
			<label>
				Project filter
				<select v-model="filterProjectId" class="tt-select" @change="fetchReport">
					<option value="">All projects</option>
					<option v-for="p in projects" :key="p.id" :value="p.id">
						{{ p.name }}
					</option>
				</select>
			</label>
			<label>
				Client filter
				<select v-model="filterClientId" class="tt-select" @change="fetchReport">
					<option value="">All clients</option>
					<option v-for="c in clients" :key="c.id" :value="c.id">
						{{ c.name }}
					</option>
				</select>
			</label>
		</div>

		<div class="tt-report-export">
			<NcButton @click="downloadCsv">
				Download CSV
			</NcButton>
			<NcButton @click="downloadJson">
				Download JSON
			</NcButton>
		</div>

		<NcLoadingIcon v-if="loading" :size="40" />

		<table v-else class="tt-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Name</th>
					<th>Details</th>
					<th>User</th>
					<th>Project</th>
					<th>Client</th>
					<th>When</th>
					<th>Cost</th>
					<th>Duration</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="(item, i) in items" :key="i">
					<td>{{ i + 1 }}</td>
					<td>{{ item.name }}</td>
					<td>{{ item.details }}</td>
					<td>{{ item.userUid }}</td>
					<td>{{ item.project }}</td>
					<td>{{ item.client }}</td>
					<td>{{ formatWhen(item.time) }}</td>
					<td>{{ item.cost ? (item.cost / 100).toFixed(2) : '' }}</td>
					<td>{{ formatDuration(item.totalDuration) }}</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="8">
						<strong>Total</strong>
					</td>
					<td>{{ formatDuration(totalDuration) }}</td>
				</tr>
			</tfoot>
		</table>
	</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
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
const items = ref([])
const loading = ref(false)

const totalDuration = computed(() => items.value.reduce((s, i) => s + i.totalDuration, 0))

function setRange(daysBack) {
	const now = new Date()
	startDate.value = new Date(now.getTime() - daysBack * 86400000).toISOString().split('T')[0]
	endDate.value = now.toISOString().split('T')[0]
	fetchReport()
}

function setThisMonth() {
	const now = new Date()
	startDate.value = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0]
	endDate.value = now.toISOString().split('T')[0]
	fetchReport()
}

function formatWhen(ts) {
	if (!ts) return ''
	const d = new Date(ts * 1000)
	if (group3.value === 'day') return d.toLocaleDateString()
	if (group3.value === 'month') return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
	if (group3.value === 'week') {
		const jan1 = new Date(d.getFullYear(), 0, 1)
		const week = Math.ceil((((d - jan1) / 86400000) + jan1.getDay() + 1) / 7)
		return `${d.getFullYear()}W${String(week).padStart(2, '0')}`
	}
	if (group3.value === 'year') return String(d.getFullYear())
	return d.toLocaleString()
}

function formatDuration(seconds) {
	if (!seconds && seconds !== 0) return ''
	const h = Math.floor(seconds / 3600)
	const m = Math.floor((seconds % 3600) / 60)
	const s = seconds % 60
	return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

async function fetchReport() {
	loading.value = true
	const from = Math.floor(new Date(startDate.value).getTime() / 1000)
	const to = Math.floor(new Date(endDate.value + 'T23:59:59').getTime() / 1000)
	const { data } = await api.getReport({
		name: '',
		from,
		to,
		group1: group1.value,
		group2: group2.value,
		timegroup: group3.value,
		filterProjectId: filterProjectId.value,
		filterClientId: filterClientId.value,
	})
	items.value = data.items || []
	loading.value = false
}

function downloadCsv() {
	const headers = ['Name', 'Details', 'User', 'Project', 'Client', 'When', 'Cost', 'Duration', 'Ended']
	const rows = items.value.map(item => [
		item.name, item.details, item.userUid, item.project || '', item.client || '',
		formatWhen(item.time),
		item.cost ? (item.cost / 100).toFixed(2) : '0',
		formatDuration(item.totalDuration),
		group3.value ? '*' : new Date((item.time + item.totalDuration) * 1000).toLocaleString(),
	])
	const csv = [headers, ...rows].map(r => r.map(v => `"${String(v || '').replace(/"/g, '""')}"`).join(',')).join('\n')
	const blob = new Blob([csv], { type: 'text/csv' })
	const a = document.createElement('a')
	a.href = URL.createObjectURL(blob)
	a.download = 'report.csv'
	a.click()
}

function downloadJson() {
	const blob = new Blob([JSON.stringify(items.value, null, 2)], { type: 'application/json' })
	const a = document.createElement('a')
	a.href = URL.createObjectURL(blob)
	a.download = 'report.json'
	a.click()
}

onMounted(async () => {
	const [projectsRes, clientsRes] = await Promise.all([api.getProjects(), api.getClients()])
	projects.value = projectsRes.data.Projects
	clients.value = clientsRes.data.Clients
	await fetchReport()
})
</script>
