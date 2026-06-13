<template>
	<div class="tt-view">
		<div class="tt-toolbar">
			<form @submit.prevent="addGoal">
				<select v-model="newProjectId" class="tt-select">
					<option value="">Select project…</option>
					<option v-for="p in projects" :key="p.id" :value="p.id">
						{{ p.name }}
					</option>
				</select>
				<input
					v-model="newHours"
					type="number"
					min="0"
					step="0.5"
					placeholder="Target hours"
					class="tt-input tt-input-small"
				>
				<select v-model="newInterval" class="tt-select">
					<option value="weekly">Weekly</option>
					<option value="monthly">Monthly</option>
					<option value="yearly">Yearly</option>
				</select>
				<NcButton type="primary" native-type="submit" :disabled="!newProjectId || !newHours">
					Add Goal
				</NcButton>
			</form>
		</div>

		<NcLoadingIcon v-if="loading" :size="40" />

		<table v-else class="tt-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Project</th>
					<th>Target Hours</th>
					<th>Interval</th>
					<th>Started At</th>
					<th>Hours This Period</th>
					<th>Past Debt (h)</th>
					<th>Remaining (h)</th>
					<th>Total Remaining (h)</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="(goal, i) in goals" :key="goal.id">
					<td>{{ i + 1 }}</td>
					<td>{{ goal.projectName }}</td>
					<td>{{ goal.hours }}</td>
					<td>{{ goal.interval }}</td>
					<td>{{ new Date(goal.createdAt * 1000).toLocaleDateString() }}</td>
					<td>{{ goal.workedHoursCurrentPeriod }}</td>
					<td>{{ goal.debtHours }}</td>
					<td>{{ goal.remainingHours }}</td>
					<td>{{ goal.totalRemainingHours }}</td>
					<td class="tt-actions">
						<NcActions>
							<NcActionButton @click="openDelete(goal)">
								<template #icon>
									<span class="icon-delete" />
								</template>
								Delete
							</NcActionButton>
						</NcActions>
					</td>
				</tr>
			</tbody>
		</table>

		<NcModal v-if="showDelete" name="Delete goal" @close="showDelete = false">
			<div class="tt-modal-body">
				<h2>Delete goal</h2>
				<p>Delete this goal? This cannot be undone.</p>
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
import { NcButton, NcModal, NcActions, NcActionButton, NcLoadingIcon } from '@nextcloud/vue'
import api from '../api/index.js'

const goals = ref([])
const projects = ref([])
const loading = ref(true)
const newProjectId = ref('')
const newHours = ref('')
const newInterval = ref('weekly')
const showDelete = ref(false)
const deleteTarget = ref(null)

async function fetchAll() {
	loading.value = true
	const [goalsRes, projectsRes] = await Promise.all([api.getGoals(), api.getProjects()])
	goals.value = goalsRes.data.Goals
	projects.value = projectsRes.data.Projects
	loading.value = false
}

async function addGoal() {
	if (!newProjectId.value || !newHours.value) return
	await api.addGoal(newProjectId.value, newHours.value, newInterval.value)
	newProjectId.value = ''
	newHours.value = ''
	newInterval.value = 'weekly'
	await fetchAll()
}

function openDelete(goal) {
	deleteTarget.value = goal
	showDelete.value = true
}

async function doDelete() {
	await api.deleteGoal(deleteTarget.value.id)
	showDelete.value = false
	deleteTarget.value = null
	await fetchAll()
}

onMounted(fetchAll)
</script>
