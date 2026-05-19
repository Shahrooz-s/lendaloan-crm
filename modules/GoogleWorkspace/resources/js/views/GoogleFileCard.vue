<template>
    <ICard class="mb-4">
      <ICardHeader class="flex justify-between items-center">
        <div>
          <h4 class="font-semibold text-lg">{{ file.name }}</h4>
          <div class="text-xs text-gray-500">{{ $t('googleworkspace::google.last_modified') }}: {{ formattedDate }}</div>
        </div>
  
        <div class="flex items-center space-x-2">
          <RouterLink
            :to="previewUrl"
            class="text-blue-600 hover:underline text-sm"
          >
            {{ $t('googleworkspace::google.view') }}
          </RouterLink>
  
          <a
            v-if="file.webViewLink"
            :href="file.webViewLink"
            target="_blank"
            class="text-sm text-blue-500 hover:underline"
          >
            {{ $t('googleworkspace::google.open_in_google') }}
          </a>
        </div>
      </ICardHeader>
    </ICard>
  </template>
  
  <script setup>
  import { computed } from 'vue'
  import { format } from 'date-fns'
  
  const props = defineProps({
    file: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  })
  
  const formattedDate = computed(() =>
    file.modifiedTime
      ? format(new Date(file.modifiedTime), 'PPP p')
      : 'N/A'
  )
  
  const previewUrl = computed(() => `/google/preview/${props.type}/${props.file.id}`)
  </script>
