<template>
  <ISlideover
    id="createGoogleDriveFileModal"
    :visible="visible"
    :title="title || $t('googleworkspace::google.create_google_drive_file')"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    static
    form
    @hidden="handleModalHiddenEvent"
    @submit="createGoogleDriveFile"
    @update:visible="$emit('update:visible', $event)"
  >
    <FieldsPlaceholder v-if="!hasFields" />
    <div v-show="fieldsVisible">
      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        is-floating
        focus-first
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      />
    </div>
  </ISlideover>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: String,
  fieldsVisible: { type: Boolean, default: true },
})
const emit = defineEmits(['created', 'update:visible', 'ready'])
const { t } = useI18n()
const resourceName = Innoclapps.resourceName('google-drives')
const { fields, hasFields, getCreateFields } = useResourceFields()
const { form } = useForm()
const { createResource } = useResourceable(resourceName)

watch(() => props.visible, async (val) => {
  if (val) {
    const createFields = await getCreateFields(resourceName)
    fields.value = createFields
    emit('ready', { fields, form })
  }
})

function handleModalHiddenEvent() {
  fields.value = []
  form.reset()
}

async function createGoogleDriveFile() {
  form.busy = true
  await Innoclapps.request()
    .post(`/google/create-file`, {
      type: 'drive',
      name: form.name,
      description: form.description
    })
    .then(file => {
      emit('created', file)
      emit('update:visible', false)
    })
    .catch(e => {
      if (e.isValidationError && e.isValidationError()) {
        Innoclapps.error(t('core::app.form_validation_failed'), 3000)
      }
    })
    .finally(() => {
      form.busy = false
    })
}
</script>
