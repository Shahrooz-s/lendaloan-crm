<template>
  <div class="flex h-[calc(100vh-8rem)] min-h-[760px] flex-col overflow-hidden border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3 dark:border-neutral-700">
      <div>
        <h2 class="text-base font-semibold text-neutral-900 dark:text-neutral-100">
          {{ activePage.title }}
        </h2>
        <p class="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
          {{ activePage.description }}
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <IButton
          v-for="page in pages"
          :key="page.route"
          :to="{ name: page.route }"
          :variant="page.route === $route.name ? 'primary' : 'secondary'"
          size="sm"
          :text="page.title" />
      </div>
    </div>

    <iframe
      :key="iframeSrc"
      title="Lend A Loan Forms"
      :src="iframeSrc"
      class="h-full w-full flex-1 border-0"
      allow="clipboard-read; clipboard-write; fullscreen" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const opnFormBaseUrl = 'https://form.lendaloan.com.au'

const pages = [
  {
    route: 'web-forms-index',
    title: 'Manage Forms',
    description: 'Manage Lend A Loan forms from the Concord workspace.',
    path: '/home',
  },
  {
    route: 'web-form-create',
    title: 'Create Form',
    description: 'Build a new form inside the Concord forms workspace.',
    path: '/forms/create',
  },
  {
    route: 'web-form-submissions',
    title: 'Submissions',
    description: 'Review captured form responses before syncing records.',
    path: '/home',
  },
  {
    route: 'web-form-automations',
    title: 'Automations',
    description: 'Connect form delivery, webhooks, and workflow actions.',
    path: '/integrations',
  },
]

const route = useRoute()

const activePage = computed(() => {
  return pages.find(page => page.route === route.name) || pages[0]
})

const iframeSrc = computed(() => {
  const url = new URL(activePage.value.path, opnFormBaseUrl)

  url.searchParams.set('embed', 'concord')
  url.searchParams.set('workspace', 'lendaloan')

  return url.toString()
})
</script>
