<template>
    <ICard as="form" :overlay="!componentReady" @submit.prevent="submit">
      <ICardHeader>
        <ICardHeading>{{ $t('googleworkspace::google.settings.title') }}</ICardHeading>
      </ICardHeader>

      <ICardBody>
          <IFormGroup label="Client ID" label-for="client_id" class="mb-4">
            <IFormInput id="client_id" v-model="form.google_client_id" placeholder="Enter Google Client ID" />
          </IFormGroup>

          <IFormGroup label="Client Secret" label-for="client_secret" class="mb-4">
              <IFormInput id="client_secret" v-model="form.google_client_secret" type="password"
                          placeholder="Enter Google Client Secret" />
          </IFormGroup>

          <IFormGroup label="Redirect URI" label-for="redirect_uri" class="mb-6">
              <IFormInput id="redirect_uri" v-model="form.google_redirect_uri" placeholder="Enter Redirect URI" disabled />
          </IFormGroup>

        <IFormGroup :label="$t('googleworkspace::google.settings.project_id')" label-for="project_id" class="mb-4">
          <IFormInput id="project_id" v-model="form.google_project_id" :placeholder="$t('googleworkspace::google.settings.project_id_placeholder')" />
        </IFormGroup>
      </ICardBody>

      <ICardFooter class="flex justify-between">
          <IButton :loading="loading" variant="primary" @click="connectToGoogle">
              Connect with Google
          </IButton>

        <IButton @click="submit" variant="primary">
          {{ $t('googleworkspace::google.settings.save') }}
        </IButton>
      </ICardFooter>
    </ICard>
</template>

<script setup>
import { ref } from 'vue'
import { useSettings } from '@/Core/composables/useSettings.js'

const { form, isReady: componentReady, submit } = useSettings()

const loading = ref(false)

const connectToGoogle = async () => {
    loading.value = true
    try {
        const response = await Innoclapps.request().get('/google/auth');
        window.location.href = response.data.url
    } catch (e) {
        console.error('Failed to get auth URL', e)
    } finally {
        loading.value = false
    }
}
</script>
