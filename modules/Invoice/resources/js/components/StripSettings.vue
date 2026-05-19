<template>
  <ICardHeader>
    <ICardHeading :text="$t('Invoice::settings.payments.stripe.title')" />
  </ICardHeader>

  <ICard as="form" :overlay="!componentReady" @submit.prevent="submit">
    <ICardBody>
      <div class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-6">
        <div class="sm:col-span-2">
          <IFormGroup
              label-for="stripe_api_key"
              :label="$t('Invoice::settings.payments.stripe.api_key')"
              required
          >
            <IFormInput id="stripe_api_key" v-model="form.stripe_api_key" />
          </IFormGroup>
        </div>

        <div class="sm:col-span-2">
          <IFormGroup
              label-for="stripe_secret"
              :label="$t('Invoice::settings.payments.stripe.secret')"
          >
            <IFormNumericInput
                id="stripe_secret"
                v-model="form.stripe_secret"
            >
            </IFormNumericInput>
          </IFormGroup>
        </div>
      </div>
    </ICardBody>

    <ICardFooter class="text-right">
      <IButton
          type="submit"
          variant="primary"
          :disabled="form.busy"
          :text="$t('core::app.save')"
      />
    </ICardFooter>
  </ICard>
</template>

<script setup>
import { useSettings } from '@/Core/composables/useSettings'

const { form, isReady: componentReady, submit } = useSettings()
</script>
