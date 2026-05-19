<template>
  <MainLayout>
    <template #actions>
      <NavbarSeparator class="hidden lg:block" />

      <NavbarItems>
        <IButton
          v-show="!tableEmpty"
          variant="primary"
          icon="PlusSolid"
          to="/sms/create"
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
        :to="'/sms/create'"
        :title="$t('sms::sms.empty_state.title' || 'No SMS Found')"
        :button-text="$t('sms::sms.create')"
        :description="$t('sms::sms.empty_state.description')"
      />
    </div>

    <div v-show="!tableEmpty">
      <ResourceTable
        :resource-name="resourceName"
        @loaded="handleTableLoaded"
      />
    </div>
  </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'

const resourceName = 'sms'
const resourceInformation = Innoclapps.resource(resourceName)

const tableEmpty = ref(true)
const tableLoaded = ref(false)

const shouldShowEmptyState = computed(
  () => tableEmpty.value && tableLoaded.value
)

function handleTableLoaded(e) {
  tableEmpty.value = e.isPreEmpty
  tableLoaded.value = true
}

</script>
