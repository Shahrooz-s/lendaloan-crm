<template>
  <CreateSmsTemplate
    :title="modalTitle"
    :[viaResource]="viaResource ? [parentResource] : undefined"
  >
    <template #top="{ isReady }">
      <div
        v-if="viaResource"
        v-show="isReady"
        class="mb-4 rounded-lg border border-neutral-300 bg-neutral-50/80 px-4 py-3 dark:border-neutral-500/30 dark:bg-neutral-500/10"
      >
        <FormFields
          :fields="associateField"
          :form="associateForm"
          :resource-name="resourceName"
          is-floating
          @update-field-value="
            associateForm.fill($event.attribute, $event.value)
          "
          @set-initial-value="associateForm.set($event.attribute, $event.value)"
        />
      </div>
    </template>
  </CreateSmsTemplate>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { useForm } from '@/Core/composables/useForm'
import { usePageTitle } from '@/Core/composables/usePageTitle'

import CreateSmsTemplate from '../../components/CreateSmsTemplate.vue'

const props = defineProps({
  viaResource: String,
  parentResource: Object,
})

const resourceName = Innoclapps.resourceName('sms_templates')

const { t } = useI18n()

const { form: associateForm } = useForm()

const modalTitle = computed(() => {
  return t('sms::sms.sms_template_create')
})

if (!props.viaResource) {
  usePageTitle(t('sms::sms.sms_template_create'))
}
</script>
