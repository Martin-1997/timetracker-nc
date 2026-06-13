<template>
	<div class="tt-view">
		<div class="tt-toolbar">
			<form @submit.prevent="addProject">
				<NcTextField v-model="newName" label="Project name" placeholder="Project name" />
				<select v-model="newClientId" class="tt-select">
					<option value="">No client</option>
					<option v-for="c in clients" :key="c.id" :value="c.id">
						{{ c.name }}
					</option>
				</select>
				<input v-model="newColor" type="color" class="tt-color-picker" title="Project color">
				<NcButton type="primary" native-type="submit" :disabled="!newName.trim()">
					Add Project
				</NcButton>
			</form>
		</div>

		<label class="tt-checkbox-label">
			<input v-model="showArchived" type="checkbox" @change="fetchProjects">
			Show archived projects
		</label>

		<NcLoadingIcon v-if="loading" :size="40" />

		<table v-else class="tt-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Color</th>
					<th>Name</th>
					<th>Client</th>
					<th>Locked</th>
					<th v-if="isAdmin">
						Allowed Users
					</th>
					<th v-if="isAdmin">
						Allowed Tags
					</th>
					<th v-if="showArchived">
						Archived
					</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="(project, i) in projects" :key="project.id">
					<td>{{ i + 1 }}</td>
					<td>
						<span
							class="tt-color-dot"
							:style="{ backgroundColor: project.color || '#cccccc' }"
						/>
					</td>
					<td>{{ project.name }}</td>
					<td>{{ project.client }}</td>
					<td>{{ project.locked ? 'Yes' : 'No' }}</td>
					<td v-if="isAdmin">
						{{ Array.isArray(project.allowedUsers) ? project.allowedUsers.join(', ') : project.allowedUsers }}
					</td>
					<td v-if="isAdmin">
						{{ Array.isArray(project.allowedTags) ? project.allowedTags.map(t => t.name).join(', ') : '' }}
					</td>
					<td v-if="showArchived">
						{{ project.archived ? 'Yes' : 'No' }}
					</td>
					<td class="tt-actions">
						<NcActions>
							<NcActionButton @click="openEdit(project)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiPencil" :size="20" />
								</template>
								Edit
							</NcActionButton>
							<NcActionButton v-if="isAdmin" @click="openDelete(project)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiDelete" :size="20" />
								</template>
								Delete
							</NcActionButton>
						</NcActions>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Edit Dialog -->
		<NcModal v-if="showEdit" name="Edit project" size="large" @close="showEdit = false">
			<div class="tt-modal-body">
				<h2>Edit project</h2>
				<NcTextField v-model="editProject.name" label="Name" />
				<label>
					Client
					<select v-model="editProject.clientId" class="tt-select">
						<option :value="null">No client</option>
						<option v-for="c in clients" :key="c.id" :value="c.id">
							{{ c.name }}
						</option>
					</select>
				</label>
				<label>
					Color
					<input v-model="editProject.color" type="color" class="tt-color-picker">
				</label>
				<label class="tt-checkbox-label">
					<input v-model="editProject.archived" type="checkbox">
					Archived
				</label>
				<template v-if="isAdmin">
					<label class="tt-checkbox-label">
						<input v-model="editProject.locked" type="checkbox">
						Locked
					</label>
					<template v-if="editProject.locked">
						<label>
							Allowed tags
							<select v-model="editProject.allowedTagIds" class="tt-select" multiple>
								<option v-for="t in allTags" :key="t.id" :value="t.id">
									{{ t.name }}
								</option>
							</select>
						</label>
						<label>
							Allowed users
							<select v-model="editProject.allowedUserIds" class="tt-select" multiple>
								<option v-for="u in allUsers" :key="u.id" :value="u.id">
									{{ u.text }}
								</option>
							</select>
						</label>
					</template>
				</template>
				<div class="tt-modal-actions">
					<NcButton type="primary" @click="saveEdit">
						Save
					</NcButton>
					<NcButton v-if="isAdmin" type="error" @click="openDeleteFromEdit">
						Delete project
					</NcButton>
					<NcButton @click="showEdit = false">
						Cancel
					</NcButton>
				</div>
			</div>
		</NcModal>

		<!-- Delete Confirm -->
		<NcModal v-if="showDelete" name="Delete project" @close="showDelete = false">
			<div class="tt-modal-body">
				<h2>Delete project</h2>
				<p>Delete project "{{ deleteTarget?.name }}" and all its data? This cannot be undone.</p>
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
import { NcButton, NcModal, NcActions, NcActionButton, NcLoadingIcon, NcTextField, NcIconSvgWrapper } from '@nextcloud/vue'

const mdiPencil = 'M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z'
const mdiDelete = 'M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z'
import api from '../api/index.js'

const isAdmin = window.oc_isadmin ?? false
const projects = ref([])
const clients = ref([])
const allTags = ref([])
const allUsers = ref([])
const loading = ref(true)
const showArchived = ref(false)

const newName = ref('')
const newClientId = ref('')
const newColor = ref('#3366cc')

const showEdit = ref(false)
const editId = ref(null)
const editProject = ref({})

const showDelete = ref(false)
const deleteTarget = ref(null)

async function fetchProjects() {
	loading.value = true
	const { data } = await api.getProjectsTable(showArchived.value)
	projects.value = data.items
	loading.value = false
}

async function fetchClients() {
	const { data } = await api.getClients()
	clients.value = data.Clients
}

async function addProject() {
	if (!newName.value.trim()) return
	await api.addProject(newName.value.trim(), newClientId.value || null, newColor.value)
	newName.value = ''
	newClientId.value = ''
	newColor.value = '#3366cc'
	await fetchProjects()
}

async function openEdit(project) {
	editId.value = project.id
	editProject.value = {
		name: project.name,
		clientId: project.clientId ?? null,
		color: project.color || '#3366cc',
		locked: !!project.locked,
		archived: !!project.archived,
		allowedTagIds: (project.allowedTags || []).map(t => t.id),
		allowedUserIds: [],
	}
	showEdit.value = true

	if (isAdmin) {
		const [tagsRes, usersRes] = await Promise.all([api.getTags(), api.getUsers()])
		allTags.value = tagsRes.data.Tags
		const usersData = usersRes.data?.ocs?.data?.users || {}
		allUsers.value = Object.entries(usersData).map(([id, val]) => ({ id, text: val.displayname }))
		const allowedUsers = Array.isArray(project.allowedUsers) ? project.allowedUsers : []
		editProject.value.allowedUserIds = allUsers.value
			.filter(u => allowedUsers.includes(u.text))
			.map(u => u.id)
	}
}

async function saveEdit() {
	const p = editProject.value
	await api.editProject(editId.value, {
		name: p.name,
		clientId: p.clientId,
		color: p.color,
		locked: p.locked ? '1' : '0',
		archived: p.archived ? '1' : '0',
		allowedTags: p.allowedTagIds.join(','),
		allowedUsers: allUsers.value.filter(u => p.allowedUserIds.includes(u.id)).map(u => u.text).join(','),
	})
	showEdit.value = false
	await fetchProjects()
}

function openDelete(project) {
	deleteTarget.value = project
	showDelete.value = true
}

function openDeleteFromEdit() {
	deleteTarget.value = { id: editId.value, name: editProject.value.name }
	showEdit.value = false
	showDelete.value = true
}

async function doDelete() {
	await api.deleteProject(deleteTarget.value.id)
	showDelete.value = false
	deleteTarget.value = null
	await fetchProjects()
}

onMounted(async () => {
	await Promise.all([fetchProjects(), fetchClients()])
})
</script>
