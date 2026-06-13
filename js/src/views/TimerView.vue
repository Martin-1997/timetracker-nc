<template>
	<div class="tt-view tt-timer-view">
		<!-- Top bar: name input, project/tag selects, timer, start/stop button -->
		<div id="top-work-bar">
			<div id="work-input-container">
				<form @submit.prevent="startOrStop">
					<NcTextField
						v-model="workName"
						label="What have you done?"
						placeholder="What have you done?"
					/>
				</form>
			</div>

			<div class="tt-top-selects">
				<select v-model="selectedProjectId" class="tt-select tt-project-select" aria-label="Project" @change="onNewProjectChange">
					<option value="">No project</option>
					<option v-for="p in projects" :key="p.id" :value="p.id">
						{{ p.name }}
					</option>
				</select>
				<select v-model="selectedTagIds" class="tt-select" multiple size="1" aria-label="Tags" @change="onNewTagChange">
					<option v-for="t in tags" :key="t.id" :value="t.id">
						{{ t.name }}
					</option>
				</select>
			</div>

			<div id="top-work-bar-right">
				<div id="timer">
					{{ timerDisplay }}
				</div>
				<NcButton
					id="start-tracking"
					variant="tertiary"
					:aria-label="isRunning ? 'Stop timer' : 'Start timer'"
					@click.prevent="startOrStop"
				>
					<template #icon>
						<NcIconSvgWrapper :path="isRunning ? mdiStop : mdiPlay" :size="22" />
					</template>
				</NcButton>
			</div>
		</div>

		<!-- Manual entry and date range -->
		<div class="tt-secondary-bar">
			<NcButton @click="showManualEntry = true">
				Manual entry
			</NcButton>
			<div class="tt-date-range">
				<NcDateTimePicker
					v-model="startDate"
					type="date"
					append-to-body
					@update:model-value="fetchWorkItems"
				/>
				<span>–</span>
				<NcDateTimePicker
					v-model="endDate"
					type="date"
					append-to-body
					@update:model-value="fetchWorkItems"
				/>
				<div class="tt-quick-ranges">
					<NcButton size="small" @click="setRange(0)">
						Today
					</NcButton>
					<NcButton size="small" @click="setRange(6)">
						7 days
					</NcButton>
					<NcButton size="small" @click="setRange(29)">
						30 days
					</NcButton>
					<NcButton size="small" @click="setRange(89)">
						90 days
					</NcButton>
				</div>
			</div>
		</div>

		<!-- Work intervals list -->
		<NcLoadingIcon v-if="loading" :size="40" />

		<div v-else id="work-intervals">
			<div v-for="day in workDays" :key="day.dayName" class="day-work-intervals">
				<div class="day-name">
					{{ day.dayName }}
				</div>
				<div v-for="group in day.groups" :key="group.name" class="work-item">
					<div class="work-item-header">
						<div class="wi-len">
							{{ group.children.length > 1 ? group.children.length : '&nbsp;' }}
						</div>
						<div class="wi-name">
							{{ group.name }}
						</div>
						<div class="wi-duration">
							{{ formatDuration(group.totalTime) }}
						</div>
					</div>
					<ul>
						<li v-for="child in group.children" :key="child.id" class="wi-child-li">
							<div class="wi-child">
								<div class="wi-child-element">
									<div
										class="wi-child-name clickable"
										@click="openEdit(child)"
									>
										{{ truncate(child.name, 64) }}
										<div class="wi-child-details">
											{{ truncate(child.details, 64) }}
										</div>
									</div>
									<NcButton variant="tertiary" aria-label="Delete" class="wi-trash" @click="openDeleteWorkItem(child)">
										<template #icon>
											<NcIconSvgWrapper :path="mdiTrashCan" :size="18" />
										</template>
									</NcButton>
									<!-- Project select inline -->
									<select
										class="tt-select tt-project-select-inline"
										:value="child.projectId || ''"
										@change="updateChildProject(child, $event.target.value)"
									>
										<option value="">No project</option>
										<option v-for="p in projects" :key="p.id" :value="p.id">
											{{ p.name }}
										</option>
									</select>
									<!-- Tag select inline (multi) -->
									<select
										class="tt-select tt-tag-select-inline"
										multiple
										size="1"
										@change="updateChildTags(child, $event)"
									>
										<option
											v-for="t in tags"
											:key="t.id"
											:value="t.id"
											:selected="child.tags.some(ct => ct.id === t.id)"
										>
											{{ t.name }}
										</option>
									</select>
									<!-- Cost -->
									<input
										class="tt-input tt-cost-input cost"
										type="text"
										placeholder="Cost"
										:value="child.cost ? (child.cost / 100).toFixed(2) : ''"
										@blur="updateChildCost(child, $event.target.value)"
									>
									<!-- Time range (editable) -->
									<div class="wi-child-hours">
										<NcDateTimePicker
											type="datetime"
											:minute-step="1"
											:model-value="new Date(child.start * 1000)"
											append-to-body
											@update:model-value="updateChildStart(child, $event)"
										/>
										<span>–</span>
										<NcDateTimePicker
											v-if="!child.running"
											type="datetime"
											:minute-step="1"
											:model-value="new Date((child.start + child.duration) * 1000)"
											append-to-body
											@update:model-value="updateChildEnd(child, $event)"
										/>
										<span v-else class="tt-running-label">running...</span>
									</div>
									<div class="wi-child-duration">
										{{ child.running ? 'running...' : formatDuration(child.duration) }}
									</div>
									<!-- Resume button -->
									<NcButton variant="tertiary" aria-label="Resume" class="wi-resume" @click="resumeWorkItem(child)">
										<template #icon>
											<NcIconSvgWrapper :path="mdiPlay" :size="18" />
										</template>
									</NcButton>
								</div>
							</div>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Edit work item dialog -->
		<NcModal v-if="showEdit" name="Edit work item" @close="showEdit = false">
			<div class="tt-modal-body">
				<h2>Edit work item</h2>
				<NcTextField v-model="editItem.name" label="Name" />
				<label>
					Details
					<textarea v-model="editItem.details" class="tt-textarea" rows="4" />
				</label>
				<div class="tt-modal-actions">
					<NcButton type="primary" @click="saveEdit">
						Save
					</NcButton>
					<NcButton @click="showEdit = false">
						Cancel
					</NcButton>
				</div>
			</div>
		</NcModal>

		<!-- Manual entry dialog -->
		<NcModal v-if="showManualEntry" name="Add work item" @close="showManualEntry = false">
			<div class="tt-modal-body">
				<h2>Add work item</h2>
				<NcTextField v-model="manualName" label="Name" />
				<label>
					Details
					<textarea v-model="manualDetails" class="tt-textarea" rows="3" />
				</label>
				<label>
					Start
					<NcDateTimePicker v-model="manualStart" type="datetime" :minute-step="1" append-to-body />
				</label>
				<label>
					End
					<NcDateTimePicker v-model="manualEnd" type="datetime" :minute-step="1" append-to-body />
				</label>
				<div class="tt-modal-actions">
					<NcButton
						type="primary"
						:disabled="!manualName.trim()"
						@click="addManualEntry"
					>
						Confirm
					</NcButton>
					<NcButton @click="showManualEntry = false">
						Cancel
					</NcButton>
				</div>
			</div>
		</NcModal>

		<!-- Delete work item confirm -->
		<NcModal v-if="showDeleteWorkItem" name="Delete work item" @close="showDeleteWorkItem = false">
			<div class="tt-modal-body">
				<h2>Delete work item</h2>
				<p>Are you sure you want to delete this work item?</p>
				<div class="tt-modal-actions">
					<NcButton type="error" @click="doDeleteWorkItem">
						Delete
					</NcButton>
					<NcButton @click="showDeleteWorkItem = false">
						Cancel
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { NcButton, NcModal, NcLoadingIcon, NcDateTimePicker, NcIconSvgWrapper, NcTextField } from '@nextcloud/vue'
import api from '../api/index.js'
import { startOfDay, endOfDay } from '../utils/date.js'

const mdiPlay = 'M8,5.14V19.14L19,12.14L8,5.14Z'
const mdiStop = 'M18,18H6V6H18V18Z'
const mdiTrashCan = 'M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z'

// --- Date range ---
const today = new Date()
const startDate = ref(new Date(today.getTime() - 29 * 86400000))
const endDate = ref(new Date(today))

// --- Input state ---
const workName = ref('')
const selectedProjectId = ref('')
const selectedTagIds = ref([])

// --- Timer state ---
const isRunning = ref(false)
const timerStartLocal = ref(0)
const timerSeconds = ref(0)
let timerInterval = null

// --- Data ---
const projects = ref([])
const tags = ref([])
const workData = ref(null)
const loading = ref(true)

// --- Dialogs ---
const showEdit = ref(false)
const editItem = ref({ id: null, name: '', details: '' })

const showManualEntry = ref(false)
const manualName = ref('')
const manualDetails = ref('')
const manualStart = ref(new Date())
const manualEnd = ref(new Date())

const showDeleteWorkItem = ref(false)
const deleteWorkItemTarget = ref(null)

// --- Computed ---
const timerDisplay = computed(() => formatDuration(timerSeconds.value))

const workDays = computed(() => {
	if (!workData.value?.days) return []
	return Object.entries(workData.value.days).map(([dayName, groups]) => ({
		dayName,
		groups: Object.entries(groups).map(([name, group]) => ({
			name,
			totalTime: group.totalTime,
			children: group.children,
		})),
	}))
})

// --- Helpers ---
function formatDuration(seconds) {
	const d = Math.floor(seconds / 86400)
	const h = Math.floor((seconds % 86400) / 3600)
	const m = Math.floor((seconds % 3600) / 60)
	const s = seconds % 60
	const pad = (n) => String(n).padStart(2, '0')
	if (d > 0) return `${d} days ${pad(h)}:${pad(m)}:${pad(s)}`
	return `${pad(h)}:${pad(m)}:${pad(s)}`
}

function truncate(s, n) {
	if (!s) return ''
	return s.length < n ? s : s.substring(0, n - 4) + ' ...'
}

function toManualFormat(date) {
	const pad = (n) => String(n).padStart(2, '0')
	return `${pad(date.getDate())}/${pad(date.getMonth() + 1)}/${String(date.getFullYear()).slice(2)} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function setRange(daysBack) {
	const now = new Date()
	startDate.value = new Date(now.getTime() - daysBack * 86400000)
	endDate.value = new Date(now)
	fetchWorkItems()
}

// --- Timer tick ---
function startTick() {
	clearInterval(timerInterval)
	timerInterval = setInterval(() => {
		if (!isRunning.value) { clearInterval(timerInterval); return }
		timerSeconds.value = Math.floor(Date.now() / 1000) - timerStartLocal.value
	}, 1000)
}

// --- API actions ---
async function fetchWorkItems() {
	loading.value = true
	const from = startOfDay(startDate.value)
	const to = endOfDay(endDate.value)
	const { data } = await api.getWorkIntervals(from, to)
	workData.value = data
	loading.value = false

	if (data.running?.length > 0) {
		const serverNow = data.now
		const clientNow = Math.floor(Date.now() / 1000)
		timerStartLocal.value = data.running[0].start + clientNow - serverNow
		isRunning.value = true
		timerSeconds.value = clientNow - timerStartLocal.value
		startTick()
	} else {
		isRunning.value = false
		timerSeconds.value = 0
		clearInterval(timerInterval)
	}
}

async function startOrStop() {
	if (isRunning.value) {
		await stopTimer()
	} else {
		await startTimer()
	}
}

async function startTimer(name = null, projectId = null, tagIds = '') {
	const n = name ?? (workName.value.trim() || 'no description')
	const pid = projectId ?? (selectedProjectId.value || null)
	const tids = tagIds !== null ? tagIds : selectedTagIds.value.join(',')
	await api.startTimer(n, pid, tids)
	isRunning.value = true
	timerStartLocal.value = Math.floor(Date.now() / 1000)
	startTick()
	await fetchWorkItems()
}

async function stopTimer() {
	const n = workName.value.trim() || 'no description'
	await api.stopTimer(n)
	isRunning.value = false
	timerSeconds.value = 0
	clearInterval(timerInterval)
	await fetchWorkItems()
}

async function resumeWorkItem(child) {
	if (isRunning.value) {
		await api.stopTimer(workName.value.trim() || 'no description')
	}
	workName.value = child.name
	selectedProjectId.value = child.projectId || ''
	await startTimer(child.name, child.projectId || null, child.tags.map(t => t.id).join(','))
}

// --- Edit dialog ---
function openEdit(child) {
	editItem.value = { id: child.id, name: child.name, details: child.details || '' }
	showEdit.value = true
}

async function saveEdit() {
	await api.updateWorkInterval(editItem.value.id, { name: editItem.value.name, details: editItem.value.details })
	showEdit.value = false
	await fetchWorkItems()
}

// --- Manual entry ---
async function addManualEntry() {
	if (!manualName.value.trim()) return
	const startFormatted = toManualFormat(manualStart.value)
	const endFormatted = toManualFormat(manualEnd.value)
	await api.addWorkInterval(manualName.value.trim(), startFormatted, endFormatted, manualDetails.value)
	showManualEntry.value = false
	manualName.value = ''
	manualDetails.value = ''
	manualStart.value = new Date()
	manualEnd.value = new Date()
	await fetchWorkItems()
}

// --- Delete work item ---
function openDeleteWorkItem(child) {
	deleteWorkItemTarget.value = child
	showDeleteWorkItem.value = true
}

async function doDeleteWorkItem() {
	await api.deleteWorkInterval(deleteWorkItemTarget.value.id)
	showDeleteWorkItem.value = false
	deleteWorkItemTarget.value = null
	await fetchWorkItems()
}

// --- Inline updates ---
async function updateChildProject(child, projectId) {
	await api.updateWorkInterval(child.id, { projectId: projectId || '' })
	await fetchWorkItems()
}

async function updateChildTags(child, event) {
	const ids = Array.from(event.target.selectedOptions).map(o => o.value)
	await api.updateWorkInterval(child.id, { tagId: ids.join(',') })
	await fetchWorkItems()
}

async function updateChildCost(child, costStr) {
	await api.updateWorkInterval(child.id, { cost: costStr })
	await fetchWorkItems()
}

async function updateChildStart(child, newStartDate) {
	const end = new Date((child.start + child.duration) * 1000)
	await api.updateWorkInterval(child.id, {
		start: toManualFormat(newStartDate),
		end: toManualFormat(end),
		tzoffset: new Date().getTimezoneOffset(),
	})
	await fetchWorkItems()
}

async function updateChildEnd(child, newEndDate) {
	const start = new Date(child.start * 1000)
	await api.updateWorkInterval(child.id, {
		start: toManualFormat(start),
		end: toManualFormat(newEndDate),
		tzoffset: new Date().getTimezoneOffset(),
	})
	await fetchWorkItems()
}

function onNewProjectChange() {}
function onNewTagChange() {}

onMounted(async () => {
	const [projectsRes, tagsRes] = await Promise.all([api.getProjects(), api.getTags()])
	projects.value = projectsRes.data.Projects
	tags.value = tagsRes.data.Tags
	await fetchWorkItems()

	const now = new Date()
	manualStart.value = new Date(now)
	manualEnd.value = new Date(now)
})

onUnmounted(() => {
	clearInterval(timerInterval)
})
</script>

<style scoped>
.tt-timer-view {
	padding: 0;
}

.tt-top-selects {
	display: flex;
	gap: 8px;
	align-items: center;
	flex-wrap: wrap;
}

.tt-project-select-inline,
.tt-tag-select-inline {
	max-width: 160px;
}

.tt-secondary-bar {
	display: flex;
	align-items: center;
	gap: 16px;
	padding: 8px 16px;
	flex-wrap: wrap;
}

.tt-running-label {
	font-style: italic;
	color: var(--color-text-maxcontrast);
}

.tt-cost-input {
	width: 80px;
}

#start-tracking {
	flex-shrink: 0;
}

.wi-child-li {
	list-style: none;
}

.wi-child-hours {
	display: flex;
	align-items: center;
	gap: 4px;
}
</style>
