<template>
  <div class="space-y-8">
    <div v-if="!originalSettings.sms_module_active">
      <ICard
        as="form"
        :overlay="!componentReady"
        @submit.prevent="submitActivationForm"
      >
        <ICardHeader>
          <ICardHeading :text="$t('sms::sms.settings.title')" />
        </ICardHeader>

        <ICardBody>
          <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
            <div class="sm:col-span-12">
              <IFormGroup
                label-for="activation_code"
                :label="$t('sms::sms.settings.activation_code')"
                required
              >
                <IFormInput
                  id="activation_code"
                  v-model="form.sms_activation_code"
                />
              </IFormGroup>
            </div>
          </div>
        </ICardBody>

        <ICardFooter class="text-right">
          <IButton
            type="submit"
            variant="primary"
            :disabled="submittingActivationForm"
            :text="$t('sms::sms.settings.activate')"
          />
        </ICardFooter>
      </ICard>
    </div>
  </div>
</template>

<script setup>
import { nextTick, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

import { useSettings } from '@/Core/composables/useSettings'

const {
  form,
  submit,
  originalSettings,
  isReady: componentReady,
} = useSettings()

const { te, t } = useI18n()
const router = useRouter()

const submittingActivationForm = ref(false)

if (!te('sms::sms.settings.activation_code')) {
  Innoclapps.request()
    .post('/generate-translations')
    .then(() => window.location.reload())
    .catch(error => console.error(error))
}

async function submitActivationForm() {
  await nextTick()
  submittingActivationForm.value = true

  form
    .post('modules/sms/activation')
    .then(() => {
      Innoclapps.success(t('sms::sms.settings.activation_success'))

      return router.push('/sms')
    })
    .catch(error => {
      Innoclapps.error(error.response.data.message)
    })
    .finally(() => {
      submittingActivationForm.value = false
    })
}
</script>
