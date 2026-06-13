<template>
	<div class="tt-view">
		<div class="tt-date-range">
			<input v-model="startDate" type="date" class="tt-input" @change="fetchData">
			<span>–</span>
			<input v-model="endDate" type="date" class="tt-input" @change="fetchData">
			<div class="tt-quick-ranges">
				<NcButton size="small" @click="setRange(0, 0)">
					Today
				</NcButton>
				<NcButton size="small" @click="setRange(6, 0)">
					Last 7 days
				</NcButton>
				<NcButton size="small" @click="setRange(29, 0)">
					Last 30 days
				</NcButton>
				<NcButton size="small" @click="setRange(89, 0)">
					Last 90 days
				</NcButton>
				<NcButton size="small" @click="setThisMonth">
					This month
				</NcButton>
				<NcButton size="small" @click="setThisYear">
					This year
				</NcButton>
			</div>
		</div>

		<NcLoadingIcon v-if="loading" :size="40" />

		<template v-else>
			<div id="summary" class="tt-summary">
				{{ summary }}
			</div>
			<div class="tt-charts">
				<div class="tt-chart-wrapper">
					<h3>Time by client / project</h3>
					<canvas id="timeChart" width="400" height="400" />
				</div>
				<div class="tt-chart-wrapper">
					<h3>Cost by client / project</h3>
					<canvas id="costChart" width="400" height="400" />
				</div>
			</div>
		</template>
	</div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import { Chart, DoughnutController, ArcElement, Tooltip, Legend } from 'chart.js'
import api from '../api/index.js'
import { localDateStr, dateStrToUnixStart, dateStrToUnixEnd } from '../utils/date.js'

Chart.register(DoughnutController, ArcElement, Tooltip, Legend)

const today = new Date()
const startDate = ref(localDateStr(new Date(today.getTime() - 29 * 86400000)))
const endDate = ref(localDateStr(today))
const loading = ref(false)
const summary = ref('')

let timeChart = null
let costChart = null

const COLORS = [
	'#3366CC', '#DC3912', '#FF9900', '#109618', '#990099',
	'#3B3EAC', '#0099C6', '#DD4477', '#66AA00', '#B82E2E',
	'#316395', '#994499', '#22AA99', '#AAAA11', '#6633CC',
	'#E67300', '#8B0707', '#329262', '#5574A6',
]

function setRange(daysBack) {
	const now = new Date()
	startDate.value = localDateStr(new Date(now.getTime() - daysBack * 86400000))
	endDate.value = localDateStr(now)
	fetchData()
}

function setThisMonth() {
	const now = new Date()
	startDate.value = localDateStr(new Date(now.getFullYear(), now.getMonth(), 1))
	endDate.value = localDateStr(now)
	fetchData()
}

function setThisYear() {
	const now = new Date()
	startDate.value = localDateStr(new Date(now.getFullYear(), 0, 1))
	endDate.value = localDateStr(now)
	fetchData()
}

async function fetchData() {
	loading.value = true
	const from = dateStrToUnixStart(startDate.value)
	const to = dateStrToUnixEnd(endDate.value)

	const { data } = await api.getReport({ from, to, group1: 'client', group2: 'project', timegroup: '', name: '' })
	loading.value = false

	const items = data.items || []
	buildCharts(items)
}

function buildCharts(items) {
	const clientMap = {}
	let nclients = 0
	let totalMinutes = 0
	let totalCost = 0

	for (const item of items) {
		const cid = item.client ?? '__none__'
		if (!clientMap[cid]) {
			clientMap[cid] = { duration: 0, cost: 0, order: nclients++, label: item.client || 'Not Set' }
		}
		clientMap[cid].duration += item.totalDuration
		clientMap[cid].cost += item.cost || 0
		totalMinutes += item.totalDuration / 60
		totalCost += item.cost || 0
	}

	const h = Math.trunc(totalMinutes / 60)
	const m = Math.trunc(totalMinutes % 60)
	summary.value = `Total time: ${h} hours ${m} minutes | Total cost: ${(totalCost / 100).toFixed(2)}`

	const timeData = { labels: [], datasets: [{ data: [], backgroundColor: [] }, { data: [], backgroundColor: [] }] }
	const costData = { labels: [], datasets: [{ data: [], backgroundColor: [] }, { data: [], backgroundColor: [] }] }

	let nindex = nclients
	for (const [cid, cm] of Object.entries(clientMap)) {
		const ci = cm.order
		timeData.labels[ci] = cm.label
		timeData.datasets[0].data[ci] = cm.duration / 60
		timeData.datasets[0].backgroundColor[ci] = COLORS[ci % COLORS.length]
		timeData.datasets[1].data[ci] = 0
		timeData.datasets[1].backgroundColor[ci] = COLORS[ci % COLORS.length]

		costData.labels[ci] = cm.label
		costData.datasets[0].data[ci] = cm.cost / 100
		costData.datasets[0].backgroundColor[ci] = COLORS[ci % COLORS.length]
		costData.datasets[1].data[ci] = 0
		costData.datasets[1].backgroundColor[ci] = COLORS[ci % COLORS.length]

		for (const item of items) {
			const ic = item.client ?? '__none__'
			if (ic === cid) {
				timeData.labels[nindex] = item.project || 'No project'
				timeData.datasets[0].data[nindex] = 0
				timeData.datasets[1].data[nindex] = item.totalDuration / 60
				timeData.datasets[0].backgroundColor[nindex] = COLORS[nindex % COLORS.length]
				timeData.datasets[1].backgroundColor[nindex] = COLORS[nindex % COLORS.length]

				costData.labels[nindex] = item.project || 'No project'
				costData.datasets[0].data[nindex] = 0
				costData.datasets[1].data[nindex] = (item.cost || 0) / 100
				costData.datasets[0].backgroundColor[nindex] = COLORS[nindex % COLORS.length]
				costData.datasets[1].backgroundColor[nindex] = COLORS[nindex % COLORS.length]
				nindex++
			}
		}
	}

	if (timeChart) timeChart.destroy()
	if (costChart) costChart.destroy()

	const timeCtx = document.getElementById('timeChart')?.getContext('2d')
	const costCtx = document.getElementById('costChart')?.getContext('2d')
	if (timeCtx) {
		timeChart = new Chart(timeCtx, {
			type: 'doughnut',
			data: timeData,
			options: {
				plugins: {
					tooltip: {
						callbacks: {
							label(ctx) {
								const mm = ctx.dataset.data[ctx.dataIndex]
								const hh = Math.trunc(mm / 60)
								const mn = Math.trunc(mm % 60)
								return `${hh}h ${mn}m`
							},
						},
					},
				},
			},
		})
	}
	if (costCtx) {
		costChart = new Chart(costCtx, {
			type: 'doughnut',
			data: costData,
			options: {
				plugins: {
					tooltip: {
						callbacks: {
							label(ctx) {
								return `Cost: ${ctx.dataset.data[ctx.dataIndex].toFixed(2)}`
							},
						},
					},
				},
			},
		})
	}
}

onMounted(fetchData)
</script>

<style scoped>
.tt-charts {
	display: flex;
	gap: 32px;
	flex-wrap: wrap;
	margin-top: 16px;
}
.tt-chart-wrapper {
	flex: 1;
	min-width: 300px;
	max-width: 500px;
}
.tt-summary {
	font-size: 1.1em;
	margin: 16px 0;
	font-weight: bold;
}
</style>
