<template>
  <div class="w-full h-screen overflow-hidden">
    <div v-if="loading" class="flex items-center justify-center h-full">
      <div class="loader"></div> 
    </div>

    <iframe
      v-else-if="iframeSrc"
      :src="iframeSrc"
      class="w-full h-full"
      frameborder="0"
      allowfullscreen
    ></iframe>

    <div v-else class="text-center py-10 text-red-500">
      {{ $t('googleworkspace::google.invalid_or_missing_file_id') }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const iframeSrc = ref(null)
const loading = ref(true)
const route = useRoute()

const driveId = route.params.driveId
const type = route.params.type

onMounted(async () => {
  try {
    let url = null

    switch (type) {
      case 'doc':
      url = `/googleworkspace/docs/${driveId}/iframe`
        url = `/google/docs/preview/${driveId}`
        break
      case 'sheet':
        url = `/google/sheets/preview/${driveId}`
        break
      case 'slide':
        url = `/google/slides/preview/${driveId}`
        break
      case 'form':
        url = `/google/forms/preview/${driveId}`
        break
        case 'drive':
          iframeSrc.value = `https://drive.google.com/file/d/${driveId}/preview`
          // url = `/google/drives/preview/${driveId}`
        return
    }

    if (url) {
      const res = await Innoclapps.request().get(url)
      iframeSrc.value = res.data.iframe_url
    }
  } catch (e) {
    console.error('Preview error', e)
  } finally {
    loading.value = false
  }
})
</script>

<style>
/* Example loader styling */
.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>