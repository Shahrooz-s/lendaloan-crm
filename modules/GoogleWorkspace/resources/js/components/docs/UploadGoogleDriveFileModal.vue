<template>
  <ISlideover id="uploadDriveFileModal" :visible="visible" :title="title || $t('googleworkspace::google.upload_drive_file')"
    :ok-text="$t('core::app.upload')"
    :ok-disabled="form.busy || !form.file"
    static form
    @hidden="handleModalHiddenEvent"
    @submit="uploadFile"
    @update:visible="$emit('update:visible', $event)">
    <div class="p-4">
      <IFormLabel :label="$t('googleworkspace::google.select_file')" />

      <input
        ref="fileInput"
        type="file"
        class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 shadow-sm transition placeholder-gray-400 dark:placeholder-gray-500 p-2"
        @change="onFileChange"
      />
    </div>
  </ISlideover>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useForm } from '@/Core/composables/useForm'
import IFormLabel from '@/Core/components/UI/Form/IFormLabel.vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  title: String
})
const emit = defineEmits(['created', 'update:visible'])
const { t } = useI18n()
const { form } = useForm()
const fileInput = ref(null)

watch(() => props.visible, (val) => {
  if (!val) return
  form.reset()
  if (fileInput.value) fileInput.value.value = ''
})

function handleModalHiddenEvent() {
  form.set('file', null)
  if (fileInput.value) fileInput.value.value = ''
  
  form.reset()
}

function onFileChange(event) {
  const file = event.target.files[0]
  if (file) {
    form.set('file', file)
    form.file = file
  } else {
    form.set('file', null)
    form.file = null
  }
}

async function uploadFile() {
  if (!form.file) return
  form.busy = true
  const formData = new FormData()
  formData.append('type', 'drive')
  formData.append('file', form.file)

  try {
    await Innoclapps.request()
      .post('/google/create-file', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
      .then(file => {
        emit('created', file)
        // Only emit event, do not show success toast here; parent will handle it
        emit('update:visible', false)
      })
      .catch(e => {
        if (e.isValidationError && e.isValidationError()) {
          Innoclapps.error(t('core::app.form_validation_failed'), 3000)
        }
      })
  } finally {
    form.busy = false
  }
}
</script>
