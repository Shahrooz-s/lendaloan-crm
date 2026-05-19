<template>
  <MainLayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <NavbarItems>
        <IDropdownMinimal
            :placement="tableEmpty ? 'bottom-end' : 'bottom'"
            horizontal
        >
          <IDropdownItem
              v-if="resourceInformation.authorizedToImport"
              icon="DocumentAdd"
              :to="{
              name: 'import-resource',
              params: { resourceName },
            }"
              :text="
              $t('core::resource.import', {
                resource: resourceInformation.label,
              })
            "
          />

          <IDropdownItem
              v-if="resourceInformation.authorizedToExport"
              icon="DocumentDownload"
              :text="
              $t('core::resource.export', {
                resource: resourceInformation.label,
              })
            "
              @click="$dialog.show('export-modal')"
          />

          <IDropdownItem
              v-if="resourceInformation.usesSoftDeletes"
              icon="Trash"
              :to="{
              name: 'trashed-resource-records',
              params: { resourceName },
            }"
              :text="
              $t('core::resource.trashed', {
                resource: resourceInformation.label,
              })
            "
          />
        </IDropdownMinimal>

        <IButton
            v-show="!tableEmpty"
            variant="primary"
            icon="PlusSolid"
            :disabled="!resourceInformation.authorizedToCreate"
            :to="{ name: 'create-invoice' }"
            :text="
            $t('core::resource.create', {
              resource: resourceInformation.singularLabel,
            })
          "
        />
      </NavbarItems>
    </template>

    <IOverlay v-if="!tableLoaded" show />


    <div v-if="shouldShowEmptyState" class="m-auto mt-8 max-w-5xl">
      <IEmptyState
          v-bind="{
          to: { name: 'create-invoice' },
          title: $t('invoice::invoice.empty_state.title'),
          buttonText: $t('invoice::invoice.create'),
          description: $t('invoice::invoice.empty_state.description'),
          secondButtonText: $t('core::import.from_file', { file_type: 'CSV' }),
          secondButtonIcon: 'DocumentAdd',
          secondButtonTo: {
            name: 'import-resource',
            params: { resourceName },
          },
        }"
      />
    </div>

    <div v-show="!tableEmpty">
      <ResourceTable :resource-name="resourceName" @loaded="handleTableLoaded">
        <template #status="{ row, column }">
          <IBadge
              :variant="column.status[row.status]?.badge"
              :text="$t(column.status[row.status]?.name)"
          />
        </template>

        <template #invoice_number="{ row, column }">
          <a class="text-base/6 no-underline sm:text-sm/6 focus:outline-none text-primary-600 hover:text-primary-900 dark:text-primary-300 dark:hover:text-primary-400 mr-1.5 last:mr-0" :href="`/invoices/${row.id}/pay`" target="_blank"> {{ row.invoice_number }} </a>
        </template>
      </ResourceTable>
    </div>

    <ResourceExport :resource-name="resourceName" />

    <!-- Create -->
    <RouterView
        :redirect-to-view="true"
        @created="
        ({ isRegularAction }) => (!isRegularAction ? refreshIndex() : undefined)
      "
        @hidden="$router.back"
    />
  </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { emitGlobal } from '@/Core/composables/useGlobalEventListener'
import { useTable } from '@/Core/composables/useTable'

const resourceName = Innoclapps.resourceName('invoices')
const resourceInformation = Innoclapps.resource(resourceName)

const { reloadTable } = useTable(resourceName)

const tableEmpty = ref(true)
const tableLoaded = ref(false)

const shouldShowEmptyState = computed(
    () => tableEmpty.value && tableLoaded.value
)

const { te } = useI18n()

if (!te('invoice::invoice.create')) {
  Innoclapps.request()
      .post('/generate-translations')
      .then(() => window.location.reload())
      .catch(error => console.error(error))
}

function handleTableLoaded(e) {
  tableEmpty.value = e.isPreEmpty
  tableLoaded.value = true
}

function refreshIndex() {
  emitGlobal('refresh-cards')
  reloadTable()
}
</script>
