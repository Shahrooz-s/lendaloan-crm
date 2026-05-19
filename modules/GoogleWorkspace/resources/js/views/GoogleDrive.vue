<template>
    <MainLayout :title="$t('googleworkspace::google.my_google_files')">
      <ICard class="mb-6">
        <ICardHeader>
          <ICardHeading>{{ $t('googleworkspace::google.connected_google_files', { label: displayLabel }) }}</ICardHeading>
        </ICardHeader>
  
        <ICardBody>
          <div v-if="loading" class="text-center py-6">{{ $t('googleworkspace::google.loading_files') }}</div>
          <div v-else-if="files.length === 0" class="text-center py-6 text-gray-500">
            {{ $t('googleworkspace::google.no_files_found', { label: displayLabel }) }}
          </div>
          <div v-else>
            <GoogleFileCard
              v-for="file in files"
              :key="file.id"
              :file="file"
              :type="fileType"
            />
          </div>
        </ICardBody>
      </ICard>
    </MainLayout>
  </template>
  
  <script setup>
  import { onMounted, ref, computed } from 'vue'
  import { useRoute } from 'vue-router'
  import GoogleFileCard from './GoogleFileCard.vue'
  
  const route = useRoute()
  const fileType = ref(route.params.type || 'doc') // default to 'doc'
  const files = ref([])
  const loading = ref(true)
  
  const displayLabel = computed(() => {
    switch (fileType.value) {
      case 'doc':
        return 'Docs'
      case 'sheet':
        return 'Sheets'
      case 'slide':
        return 'Slides'
      case 'form':
        return 'Forms'
      default:
        return 'Drive'
    }
  })
  
  onMounted(async () => {
    loading.value = true
    try {
      const { data } = await Innoclapps.request().get(`/google/files/${fileType.value}`)
      files.value = data
    } catch (err) {
      Innoclapps.error('Failed to load Google files.')
    } finally {
      loading.value = false
    }
  })
  </script>
  