<template>
	<div class="tt-view">
		<h2>Timelines (Admin)</h2>

		<NcLoadingIcon v-if="loading" :size="40" />

		<table v-else class="tt-table">
			<thead>
				<tr>
					<th>#</th>
					<th>ID</th>
					<th>User</th>
					<th>Status</th>
					<th>When</th>
					<th>Duration</th>
					<th>Created At</th>
					<th>Download</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="(tl, i) in timelines" :key="tl.id">
					<td>{{ i + 1 }}</td>
					<td>{{ tl.id }}</td>
					<td>{{ tl.userUid }}</td>
					<td>{{ tl.status }}</td>
					<td>{{ tl.timeInterval }}</td>
					<td>{{ formatDuration(tl.totalDuration) }}</td>
					<td>{{ new Date(tl.createdAt * 1000).toLocaleString() }}</td>
					<td><a :href="downloadUrl(tl.id)" target="_blank">Download</a></td>
					<td class="tt-actions">
						<NcActions>
							<NcActionButton @click="openEdit(tl)">
								<template #icon>
									<span class="icon-rename" />
								</template>
								Edit status
							</NcActionButton>
						</NcActions>
					</td>
				</tr>
			</tbody>
		</table>

		<NcModal v-if="showEdit" name="Edit timeline" @close="showEdit = false">
			<div class="tt-modal-body">
				<h2>Edit timeline status</h2>
				<label>
					Status
					<input v-model="editStatus" type="text" class="tt-input">
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
	</div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { NcButton, NcModal, NcActions, NcActionButton, NcLoadingIcon } from '@nextcloud/vue'
import api from '../api/index.js'

const timelines = ref([])
const loading = ref(true)
const showEdit = ref(false)
const editId = ref(null)
const editStatus = ref('')

function formatDuration(seconds) {
	const h = Math.floor(seconds / 3600)
	const m = Math.floor((seconds % 3600) / 60)
	const s = seconds % 60
	return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

function downloadUrl(id) {
	return api.downloadTimelineUrl(id)
}

function openEdit(tl) {
	editId.value = tl.id
	editStatus.value = tl.status
	showEdit.value = true
}

async function saveEdit() {
	await api.editTimeline(editId.value, { status: editStatus.value })
	showEdit.value = false
	await fetchTimelines()
}

async function fetchTimelines() {
	loading.value = true
	const { data } = await api.getTimelinesAdmin()
	timelines.value = data.Timelines
	loading.value = false
}

onMounted(fetchTimelines)
</script>
