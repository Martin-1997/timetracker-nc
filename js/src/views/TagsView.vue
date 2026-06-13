<template>
	<div class="tt-view">
		<div class="tt-toolbar">
			<form @submit.prevent="addTag">
				<NcTextField v-model="newName" label="Tag name" placeholder="Tag name" />
				<NcButton type="primary" native-type="submit" :disabled="!newName.trim()">
					Add Tag
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
				<tr v-for="(tag, i) in tags" :key="tag.id">
					<td>{{ i + 1 }}</td>
					<td>{{ tag.name }}</td>
					<td class="tt-actions">
						<NcActions>
							<NcActionButton @click="openEdit(tag)">
								<template #icon>
									<NcIconSvgWrapper :path="mdiPencil" :size="20" />
								</template>
								Edit
							</NcActionButton>
							<NcActionButton @click="openDelete(tag)">
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

		<NcModal v-if="showEdit" name="Edit tag" @close="showEdit = false">
			<div class="tt-modal-body">
				<h2>Edit tag</h2>
				<NcTextField v-model="editName" label="Name" />
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

		<NcModal v-if="showDelete" name="Delete tag" @close="showDelete = false">
			<div class="tt-modal-body">
				<h2>Delete tag</h2>
				<p>Delete tag "{{ deleteTarget?.name }}"? This cannot be undone.</p>
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

const tags = ref([])
const loading = ref(true)
const newName = ref('')
const showEdit = ref(false)
const editId = ref(null)
const editName = ref('')
const showDelete = ref(false)
const deleteTarget = ref(null)

async function fetchTags() {
	loading.value = true
	const { data } = await api.getTags()
	tags.value = data.Tags
	loading.value = false
}

async function addTag() {
	if (!newName.value.trim()) return
	await api.addTag(newName.value.trim())
	newName.value = ''
	await fetchTags()
}

function openEdit(tag) {
	editId.value = tag.id
	editName.value = tag.name
	showEdit.value = true
}

async function saveEdit() {
	await api.editTag(editId.value, editName.value)
	showEdit.value = false
	await fetchTags()
}

function openDelete(tag) {
	deleteTarget.value = tag
	showDelete.value = true
}

async function doDelete() {
	await api.deleteTag(deleteTarget.value.id)
	showDelete.value = false
	deleteTarget.value = null
	await fetchTags()
}

onMounted(fetchTags)
</script>
