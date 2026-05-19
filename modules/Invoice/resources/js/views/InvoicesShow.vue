<!-- InvoicesShow.vue -->
<template>
  <MainLayout> {{ resource.title }} </MainLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'core/router'

import { usePageTitle } from '@/Core/composables/usePageTitle'
import { useResource } from '@/Core/composables/useResource'

const resourceName = 'invoices'

const route = useRoute()

const invoiceId = computed(() => route.params.id)

const {
  resourceInformation,
  resource,
  synchronizeResource,
  detachResourceAssociations,
  incrementResourceCount,
  decrementResourceCount,
  fetchResource,
  updateResource,
  resourceReady: componentReady,
} = useResource(resourceName, invoiceId)

usePageTitle(computed(() => resource.value.title))

fetchResource()
</script>
