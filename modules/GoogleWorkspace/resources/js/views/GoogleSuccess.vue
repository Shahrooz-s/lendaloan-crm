<template>
    <MainLayout>
      <div class="text-center py-10">
        <div v-if="loading">{{ $t('googleworkspace::google.connecting_account') }}</div>
        <div v-if="error" class="text-red-500">{{ error }}</div>
        <div v-if="success" class="text-green-500">
          {{ $t('googleworkspace::google.account_connected_success') }}
        </div>
      </div>
    </MainLayout>
  </template>
  
  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  
  const router = useRouter()
  
  const loading = ref(true)
  const success = ref(false)
  const error = ref(null)
  
  const checkConnection = async () => {
    try {
      const res = await Innoclapps.request().get('/google/token-status')
  
      if (res.data.connected) {
        success.value = true
        setTimeout(() => {
          router.push('/google-docs') // or another view
        }, 2000)
      } else {
        error.value = 'No valid Google connection found.'
      }
    } catch (err) {
      error.value = 'Failed to verify Google connection.'
      console.error(err)
    } finally {
      loading.value = false
    }
  }
  
  onMounted(checkConnection)
  </script>
