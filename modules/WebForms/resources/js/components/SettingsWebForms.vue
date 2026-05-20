<template>
  <div
    class="flex h-[calc(100vh-8rem)] min-h-[760px] flex-col overflow-hidden border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-neutral-900"
  >
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 px-4 py-3 dark:border-neutral-700"
    >
      <div>
        <h2
          class="text-base font-semibold text-neutral-900 dark:text-neutral-100"
        >
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
          size="sm"
          :to="{ name: page.route }"
          :variant="page.route === $route.name ? 'primary' : 'secondary'"
          :text="page.title"
        />
      </div>
    </div>

    <div
      v-if="activePage.kind === 'native'"
      class="dark:bg-neutral-950 grid flex-1 content-start gap-4 overflow-auto bg-neutral-50 p-4 md:grid-cols-3"
    >
      <div
        class="border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900"
      >
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
          Concord sync
        </p>

        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
          New form submissions create or update contacts, companies, deals,
          notes, files, and appointment activities.
        </p>
      </div>

      <div
        class="border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900"
      >
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
          Workflow ready
        </p>

        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
          Synced deals use Concord pipelines, stages, activities, ownership,
          notes, media, and custom fields.
        </p>
      </div>

      <div
        class="border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900"
      >
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
          Product tools
        </p>

        <p class="mt-2 text-sm text-neutral-600 dark:text-neutral-300">
          Product search is prepared for deal-linked launch from the Concord
          workspace.
        </p>
      </div>

      <div
        class="border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-neutral-900 md:col-span-3"
      >
        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">
          Active automation endpoints
        </p>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
          <div>
            <p
              class="text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400"
            >
              Form submissions
            </p>

            <p
              class="mt-1 break-all text-sm text-neutral-700 dark:text-neutral-200"
            >
              https://app.lendaloan.com.au/webhook/opnform
            </p>
          </div>

          <div>
            <p
              class="text-xs font-medium uppercase text-neutral-500 dark:text-neutral-400"
            >
              Mapping status
            </p>

            <p class="mt-1 text-sm text-neutral-700 dark:text-neutral-200">
              LIXI-aligned fact-find fields are available on Concord records.
            </p>
          </div>
        </div>
      </div>
    </div>

    <iframe
      v-else
      :key="iframeSrc"
      title="Lend A Loan Forms"
      class="h-full w-full flex-1 border-0"
      allow="clipboard-read; clipboard-write; fullscreen"
      :src="iframeSrc"
    />
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
    route: 'web-form-templates',
    title: 'Templates',
    description: 'Browse and launch reusable form templates.',
    path: '/templates',
  },
  {
    route: 'web-form-automations',
    title: 'Automations',
    description: 'Review Concord sync, workflow, and deal automation status.',
    kind: 'native',
  },
]

const route = useRoute()

const activePage = computed(() => {
  return pages.find(page => page.route === route.name) || pages[0]
})

const iframeSrc = computed(() => {
  const url = new URL(activePage.value.path || '/home', opnFormBaseUrl)

  url.searchParams.set('embed', 'concord')
  url.searchParams.set('workspace', 'lendaloan')

  return url.toString()
})
</script>
