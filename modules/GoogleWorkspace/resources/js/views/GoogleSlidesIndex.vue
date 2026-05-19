<template>
    <MainLayout>
        <template #actions>
            <NavbarSeparator class="hidden lg:block" />
            <NavbarItems>
                <IButton
                    variant="primary"
                    icon="Plus"
                    :text="$t('googleworkspace::google.create_google_slide')"
                    @click="openCreateModal"
                    class="ml-2"
                />
                <IButton variant="primary" icon="RefreshIcon"
                    :disabled="!resourceInformation.authorizedToCreate || syncing"
                    :text="syncing ? $t('googleworkspace::google.syncing') : $t('googleworkspace::google.sync_slides', { resource: resourceInformation.singularLabel })"
                    @click="syncGoogleSlides"
                    class="ml-2"
                />
            </NavbarItems>
        </template>

        <ResourceTable :resource-name="resourceName" @loaded="handleTableLoaded">
            <template #is_available="{ row }">
                <div class="flex items-center space-x-2">
                    <Icon icon="Eye" :class="`ml-2 mt-px size-6 cursor-pointer ${availability(row.is_available)}`"
                    v-i-tooltip.bottom.light="'Preview Document'" @click="navigateToPreview(getDriveId(row.is_available))" />
                    <Icon icon="Trash" class="ml-2 mt-px size-6 cursor-pointer"
                        v-i-tooltip.bottom.light="'Delete Document'" @click="deleteSlide(getDriveId(row.is_available))" />
                </div>
            </template>
        </ResourceTable>

        <CreateGoogleSlideModal
          v-model:visible="showCreateModal"
          @created="handleSlideCreated"
        />
    </MainLayout>
</template>

<script setup>
import { computed, ref } from "vue";
import { useRouter } from 'vue-router';
import { useTable } from "@/Core/composables/useTable";
import { useI18n } from "vue-i18n";
import CreateGoogleSlideModal from '../components/docs/CreateGoogleSlideModal.vue'

const { t } = useI18n();
const resourceName = "google-slides";
const resourceInformation = Innoclapps.resource(resourceName);
const router = useRouter();

const { reloadTable } = useTable(resourceName);

const tableEmpty = ref(true);
const tableLoaded = ref(false);
const showCreateModal = ref(false)
const createBusy = ref(false)
const deleting = ref(false)

const navigateToPreview = (driveId) => {
    if (!driveId) {
        Innoclapps.error(t('googleworkspace::google.missing_drive_id'));
        return;
    }

    router.push(`/google/preview/slide/${driveId}`);
}

const deleteSlide = (driveId) => {
  if (deleting.value) return;
  Innoclapps.confirm({
    message: t('googleworkspace::google.google_slides_confirm_delete'),
    confirmText: t('core::app.delete')
  }).then(async (result) => {
    if (result === false) return;
    deleting.value = true;
    try {
      await Innoclapps.request().delete(`/google/slides/${driveId}`)
      Innoclapps.success(t('googleworkspace::google.google_slides_delete_success'))
      reloadTable();
    } catch (e) {
      console.error(e)
      Innoclapps.error(t('googleworkspace::google.google_slide_delete_fail'))
    } finally {
      deleting.value = false;
    }
  });
}

const availability = (available) => {
    try {
        const parsed = JSON.parse(available);
        // Ensure the eye icon is not dimmed if the second value is 1 and the drive ID exists
        return !(parsed[1] === 1 && parsed[0]);
    } catch (error) {
        console.error('Error parsing availability:', error);
        return true; // Default to dimmed-eye if parsing fails
    }
};

const getDriveId = (available) => {
    try {
        const parsed = JSON.parse(available);
        return parsed[0];
    } catch (error) {
        console.error('Error extracting drive ID:', error);
        return null;
    }
};

const shouldShowEmptyState = computed(
    () => tableEmpty.value && tableLoaded.value
);

const syncing = ref(false)

const syncGoogleSlides = async () => {
    syncing.value = true
    try {
        await Innoclapps.request().get('/google/sync-slides');
        Innoclapps.success(t('googleworkspace::google.google_slides_sync_success'))
        refreshIndex();

    } catch (e) {
        console.error(e);
        Innoclapps.error(t('googleworkspace::google.google_slides_sync_failed'))
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
  createBusy.value = true
  showCreateModal.value = true
}
function handleSlideCreated() {
  reloadTable()
  showCreateModal.value = false
  createBusy.value = false
  Innoclapps.success(t('googleworkspace::google.google_slides_create_success'))
}
</script>

<style>
.dimmed-eye {
    opacity: 0.5;
    pointer-events: none; /* Optional: Prevent interaction with dimmed icons */
}
</style>
