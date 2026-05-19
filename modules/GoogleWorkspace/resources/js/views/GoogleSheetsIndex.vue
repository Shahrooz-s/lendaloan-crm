<template>
  <MainLayout>
    <template #actions>
      <NavbarItems>
        <IButton
          variant="primary"
          icon="Plus"
          :text="$t('googleworkspace::google.create_google_sheet')"
          @click="openCreateModal"
          class="ml-2"
        />
        <IButton
          variant="primary"
          icon="RefreshIcon"
          :text="
            syncing
              ? $t('googleworkspace::google.syncing')
              : $t('googleworkspace::google.sync_sheets', {
                  resource: resourceInformation.singularLabel,
                })
          "
          @click="syncGoogleSheets"
          class="ml-2"
        />
      </NavbarItems>
    </template>
    <CreateGoogleSheetModal
      v-model:visible="showCreateModal"
      @created="handleSheetCreated"
    />

    <ResourceTable :resource-name="resourceName" @loaded="handleTableLoaded">
      <template #is_available="{ row }">
        <div class="flex items-center space-x-2">
          <Icon
            v-i-tooltip.bottom.light="'Preview Document'"
            icon="Eye"
            :class="`ml-2 mt-px size-6 cursor-pointer ${availability(row.is_available)}`"
            @click="navigateToPreview(getDriveId(row.is_available))"
          />

          <Icon
            v-i-tooltip.bottom.light="'Delete Document'"
            icon="Trash"
            class="ml-2 mt-px size-6 cursor-pointer"
            @click="deleteSheet(getDriveId(row.is_available))"
          />
        </div>
      </template>
    </ResourceTable>
  </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useTable } from '@/Core/composables/useTable'
import CreateGoogleSheetModal from '../components/docs/CreateGoogleSheetModal.vue'

const { t } = useI18n()
const resourceName = 'google-sheets'
const resourceInformation = Innoclapps.resource(resourceName)
const router = useRouter()

const { reloadTable } = useTable(resourceName)

const tableEmpty = ref(true)
const tableLoaded = ref(false)
const showCreateModal = ref(false)
const createBusy = ref(false)
const deleting = ref(false)

const navigateToPreview = driveId => {
  if (!driveId) {
    Innoclapps.error(t('googleworkspace::google.missing_drive_id'))

    return
  }

  router.push(`/google/preview/sheet/${driveId}`)
}

const deleteSheet = (driveId) => {
  if (deleting.value) return;
  Innoclapps.confirm({
    message: t('googleworkspace::google.google_sheets_confirm_delete'),
    confirmText: t('core::app.delete')
  }).then(async (result) => {
    // Accept both boolean and undefined (for legacy confirm)
    if (result === false) return;
    deleting.value = true;
    try {
      await Innoclapps.request().delete(`/google/sheets/${driveId}`)
      Innoclapps.success(t('googleworkspace::google.delete_successful'))
      reloadTable()
    } catch (e) {
      console.error(e)
      Innoclapps.error('Failed to delete document.')
    } finally {
      deleting.value = false;
    }
  });
}

const shouldShowEmptyState = computed(
  () => tableEmpty.value && tableLoaded.value
)

const syncing = ref(false)

const syncGoogleSheets = async () => {
  syncing.value = true

  try {
    await Innoclapps.request().get('/google/sync-sheets')
    Innoclapps.success(t('googleworkspace::google.google_sheets_sync_success'))
    refreshIndex()
  } catch (e) {
    console.error(e)
    Innoclapps.error(t('googleworkspace::google.google_sheets_sync_failed'))
  } finally {
    syncing.value = false
  }
}

function handleTableLoaded(e) {
  tableEmpty.value = e.isPreEmpty
  tableLoaded.value = true
}

function refreshIndex() {
  reloadTable()
}

const availability = available => {
  try {
    const parsed = JSON.parse(available)

    // Ensure the eye icon is not dimmed if the second value is 1 and the drive ID exists
    return (parsed[1] == 1)? 'text-primary-500': ''
  } catch (error) {
    console.error('Error parsing availability:', error)

    return true // Default to dimmed-eye if parsing fails
  }
}

const getDriveId = available => {
  try {
    const parsed = JSON.parse(available)

    return parsed[0]
  } catch (error) {
    console.error('Error extracting drive ID:', error)

    return null
  }
}

function openCreateModal() {
  createBusy.value = true
  showCreateModal.value = true
}
function handleSheetCreated() {
  reloadTable()
  showCreateModal.value = false
  createBusy.value = false
  Innoclapps.success(t('googleworkspace::google.google_sheets_create_success'))
}
</script>

<style>
.dimmed-eye {
  opacity: 0.5;
  pointer-events: none; /* Optional: Prevent interaction with dimmed icons */
}
</style>
