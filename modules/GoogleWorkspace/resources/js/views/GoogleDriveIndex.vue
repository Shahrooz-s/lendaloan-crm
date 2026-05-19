<template>
    <MainLayout>
        <template #actions>
            <NavbarSeparator class="hidden lg:block" />
            <NavbarItems>
                <IButton
                    variant="primary"
                    icon="Upload"
                    :text="$t('googleworkspace::google.upload_drive_file')"
                    @click="openUploadModal"
                    class="ml-2"
                />
                <IButton
                    variant="primary"
                    icon="RefreshIcon"
                    :disabled="!resourceInformation.authorizedToCreate || syncing"
                    :text="syncing ? $t('googleworkspace::google.syncing') : $t('googleworkspace::google.sync_drive')"
                    @click="syncGoogleDrive"
                    class="ml-2"
                />
            </NavbarItems>
        </template>

      <IOverlay v-if="!tableLoaded" show />

      <div v-show="!tableEmpty">
        <ResourceTable :resource-name="resourceName" @loaded="handleTableLoaded">
          <template #is_available="{ row }">
            <div class="flex items-center space-x-2">
              <Icon icon="Eye" :class="`ml-2 mt-px size-6 cursor-pointer ${availability(row.is_available)}`"
                    v-i-tooltip.bottom.light="'Preview Document'" @click="navigateToPreview(getDriveId(row.is_available))" />
              <Icon icon="Trash" class="ml-2 mt-px size-6 cursor-pointer"
                    v-i-tooltip.bottom.light="'Delete Document'" @click="deleteFile(getDriveId(row.is_available))" />
            </div>
          </template>
        </ResourceTable>

      </div>

        <CreateGoogleDriveFileModal
          v-model:visible="showCreateModal"
          @created="handleDriveFileCreated"
        />
        <UploadGoogleDriveFileModal
          v-model:visible="showUploadModal"
          @created="handleDriveFileUploaded"
        />
    </MainLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { useRouter } from 'vue-router';
import { useTable } from "@/Core/composables/useTable";
import { useI18n } from "vue-i18n";
import CreateGoogleDriveFileModal from '../components/docs/CreateGoogleDriveFileModal.vue'
import UploadGoogleDriveFileModal from '../components/docs/UploadGoogleDriveFileModal.vue'

const { t } = useI18n();
const resourceName = Innoclapps.resourceName('google-drives')
const resourceInformation = Innoclapps.resource(resourceName);
const router = useRouter();

const { reloadTable } = useTable(resourceName);

const tableEmpty = ref(true);
const tableLoaded = ref(false);
const showCreateModal = ref(false)
const showUploadModal = ref(false)
const syncing = ref(false)
const deleting = ref(false)

const deleteFile = (driveId) => {
  if (deleting.value) return;
  Innoclapps.confirm({
    message: t('googleworkspace::google.google_drive_confirm_delete'),
    confirmText: t('core::app.delete')
  }).then(async (result) => {
    if (result === false) return;
    deleting.value = true;
    try {
      await Innoclapps.request().delete(`/google/drives/${driveId}`)
      Innoclapps.success(t('googleworkspace::google.google_drive_delete_success'))
      reloadTable();
    } catch (e) {
      console.error(e)
      Innoclapps.error(t('googleworkspace::google.google_drive_delete_failed'))
    } finally {
      deleting.value = false;
    }
  });
}

const navigateToPreview = (driveId) => {
    if (!driveId) {
        Innoclapps.error(t('googleworkspace::google.missing_drive_id'));
        return;
    }

    router.push(`/google/preview/drive/${driveId}`);
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

const getDriveId = (available) => {
    try {
        const parsed = JSON.parse(available);
        return parsed[0];
    } catch (error) {
        console.error('Error extracting drive ID:', error);
        return null;
    }
};

const syncGoogleDrive = async () => {
    syncing.value = true
    try {
        await Innoclapps.request().get('/google/sync-drives');
        Innoclapps.success(t('googleworkspace::google.google_drive_sync_success'))
        refreshIndex();

    } catch (e) {
        console.error(e);
        Innoclapps.error(t('googleworkspace::google.google_drive_sync_failed'))
    } finally {
        syncing.value = false
    }
}

function handleTableLoaded(e) {
    tableEmpty.value = e.isPreEmpty;
    tableLoaded.value = true;
}

function refreshIndex() {
    reloadTable();
}

function openCreateModal() {
  showCreateModal.value = true
}
function openUploadModal() {
  showUploadModal.value = true
}

function handleDriveFileUploaded() {
  reloadTable()
  showUploadModal.value = false
  Innoclapps.success(t('googleworkspace::google.google_drive_upload_success'))
}
</script>

<style>
.dimmed-eye {
    opacity: 0.5;
    pointer-events: none; /* Optional: Prevent interaction with dimmed icons */
}
</style>
