<template>
	<div class="tt-view">
		<div class="tt-toolbar">
			<form @submit.prevent="addClient">
				<input
					v-model="newName"
					type="text"
					placeholder="Client name"
					class="tt-input"
				>
				<NcButton type="primary" native-type="submit" :disabled="!newName.trim()">
					Add Client
				</NcButton>
			</form>
		</div>

		<NcLoadingIcon v-if="loading" :size="40" />

		<table v-else class="tt-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Name</th>
					<th />
				</tr>
			</thead>
			<tbody>
				<tr v-for="(client, i) in clients" :key="client.id">
					<td>{{ i + 1 }}</td>
					<td>{{ client.name }}</td>
					<td class="tt-actions">
						<NcActions>
							<NcActionButton @click="openEdit(client)">
								<template #icon>
									<span class="icon-rename" />
								</template>
								Edit
							</NcActionButton>
							<NcActionButton @click="openDelete(client)">
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

		<NcModal v-if="showEdit" name="Edit client" @close="showEdit = false">
			<div class="tt-modal-body">
				<h2>Edit client</h2>
				<label>
					Name
					<input v-model="editName" type="text" class="tt-input">
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

		<NcModal v-if="showDelete" name="Delete client" @close="showDelete = false">
			<div class="tt-modal-body">
				<h2>Delete client</h2>
				<p>Delete client "{{ deleteTarget?.name }}"? This cannot be undone.</p>
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

const clients = ref([])
const loading = ref(true)
const newName = ref('')
const showEdit = ref(false)
const editId = ref(null)
const editName = ref('')
const showDelete = ref(false)
const deleteTarget = ref(null)

async function fetchClients() {
	loading.value = true
	const { data } = await api.getClients()
	clients.value = data.Clients
	loading.value = false
}

async function addClient() {
	if (!newName.value.trim()) return
	await api.addClient(newName.value.trim())
	newName.value = ''
	await fetchClients()
}

function openEdit(client) {
	editId.value = client.id
	editName.value = client.name
	showEdit.value = true
}

async function saveEdit() {
	await api.editClient(editId.value, editName.value)
	showEdit.value = false
	await fetchClients()
}

function openDelete(client) {
	deleteTarget.value = client
	showDelete.value = true
}

async function doDelete() {
	await api.deleteClient(deleteTarget.value.id)
	showDelete.value = false
	deleteTarget.value = null
	await fetchClients()
}

onMounted(fetchClients)
</script>
