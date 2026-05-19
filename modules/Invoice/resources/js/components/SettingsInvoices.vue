<template>
  <div class="space-y-8">
      <div v-if="!originalSettings.invoice_module_active">
      <ICard
        as="form"
        :overlay="!componentReady"
        @submit.prevent="submitActivationForm"
      >
        <ICardHeader>
          <ICardHeading :text="$t('invoice::invoice.invoice')" />
        </ICardHeader>

        <ICardBody>
          <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
            <div class="sm:col-span-12">
              <IFormGroup
                label-for="activation_code"
                :label="$t('invoice::invoice.settings.activation_code')"
                required
              >
                <IFormInput
                  id="activation_code"
                  v-model="form.invoice_activation_code"
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
            :text="$t('invoice::invoice.settings.activate')"
          />
        </ICardFooter>
      </ICard>
    </div>

    <div
      v-if="
        originalSettings.invoice_module_active &&
        originalSettings.invoice_module_active == true
      "
    >
      <ICard as="form" :overlay="!componentReady" @submit.prevent="submit">
        <ICardHeader>
          <ICardHeading :text="$t('invoice::settings.payments.paypal.title')" />
        </ICardHeader>

        <ICardBody>
          <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-4">
            <div class="sm:col-span-2">
              <IFormGroup
                label-for="paypal_api_key"
                :label="$t('invoice::settings.payments.paypal.api_key')"
              >
                <IFormInput
                  id="paypal_api_key"
                  v-model="form.paypal_api_key"
                  :placeholder="$t('invoice::settings.payments.paypal.api_key')"
                />
              </IFormGroup>
            </div>

            <div class="sm:col-span-2">
              <IFormGroup
                label-for="paypal_secret"
                :label="$t('invoice::settings.payments.paypal.secret')"
              >
                <IFormInput
                  id="paypal_secret"
                  v-model="form.paypal_secret"
                  :placeholder="$t('invoice::settings.payments.paypal.secret')"
                >
                </IFormInput>
              </IFormGroup>
            </div>

            <div class="sm:col-span-2">
              <IFormGroup
                label-for="paypal_app_id"
                :label="$t('invoice::settings.payments.paypal.app_id')"
              >
                <IFormInput
                  id="paypal_app_id"
                  v-model="form.paypal_app_id"
                  :placeholder="$t('invoice::settings.payments.paypal.app_id')"
                >
                </IFormInput>
              </IFormGroup>
            </div>

            <div class="sm:col-span-2">
              <IFormGroup
                label-for="paypal_mode"
                :label="$t('invoice::settings.payments.paypal.mode')"
                required
              >
                <IFormSelect
                  id="paypal_mode"
                  v-model="form.paypal_mode"
                  :placeholder="$t('invoice::settings.payments.paypal.mode')"
                  :option="['sandbox', 'live']"
                >
                  <option :key="'sandbox'" :value="'sandbox'">sandbox</option>

                  <option :key="'live'" :value="'live'">live</option>
                </IFormSelect>
              </IFormGroup>
            </div>
          </div>
        </ICardBody>

        <ICardFooter class="text-right">
          <IButton
            type="submit"
            variant="primary"
            :disabled="form.busy"
            :text="$t('invoice::settings.save')"
          />
        </ICardFooter>
      </ICard>
    </div>

    <div
      v-if="
        originalSettings.invoice_module_active &&
        originalSettings.invoice_module_active == true
      "
    >
      <ICard as="form" :overlay="!componentReady" @submit.prevent="submit">
        <ICardHeader>
          <ICardHeading :text="$t('invoice::settings.payments.stripe.title')" />
        </ICardHeader>

        <ICardBody>
          <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-4">
            <div class="sm:col-span-2">
              <IFormGroup
                label-for="stripe_api_key"
                :label="$t('invoice::settings.payments.stripe.api_key')"
              >
                <IFormInput
                  id="stripe_api_key"
                  v-model="form.stripe_api_key"
                  :placeholder="$t('invoice::settings.payments.stripe.api_key')"
                />
              </IFormGroup>
            </div>

            <div class="sm:col-span-2">
              <IFormGroup
                label-for="stripe_secret"
                :label="$t('invoice::settings.payments.stripe.secret')"
              >
                <IFormInput
                  id="stripe_secret"
                  v-model="form.stripe_secret"
                  :placeholder="$t('invoice::settings.payments.stripe.secret')"
                >
                </IFormInput>
              </IFormGroup>
            </div>
          </div>
        </ICardBody>

        <ICardFooter class="text-right">
          <IButton
            type="submit"
            variant="primary"
            :disabled="form.busy"
            :text="$t('invoice::settings.save')"
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

if (!te('invoice::invoice.settings.activation_code')) {
  Innoclapps.request()
    .post('/generate-translations')
    .then(() => window.location.reload())
    .catch(error => console.error(error))
}

async function submitActivationForm() {
  await nextTick()
  submittingActivationForm.value = true

  form
    .post('modules/invoice/activation')
    .then(() => {
      Innoclapps.success(t('invoice::invoice.settings.activation_success'))

      return router.push('/invoices')
    })
    .catch(error => {
      Innoclapps.error(error.response.data.message)
    })
    .finally(() => {
      submittingActivationForm.value = false
    })
}
</script>
