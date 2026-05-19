<template>
    <BaseFormField
      v-slot="{ readonly, fieldId }"
      :resource-name="resourceName"
      :field="field"
      :value="model"
      :is-floating="isFloating"
    >
      <FormFieldGroup
        :field="field"
        :label="field.label"
        :field-id="fieldId"
        :validation-errors="validationErrors"
      >
        <div class="relative">
          <div
            v-if="
              field.prependText
            "
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
          >
            <span
              class="text-base text-neutral-500 dark:text-neutral-300 sm:text-sm"
              v-text="
                field.prependText
              "
            />
          </div>

          <IFormInput
            :id="fieldId"
            v-model="model"
            type="text"
            v-bind="field.attributes"
            :class="{
              'pl-14 sm:pl-14':
                field.prependText ||
                (field.currency && field.currency.symbol_first),
              'pr-14 sm:pr-14':
                field.appendText ||
                (field.currency && !field.currency.symbol_first),
            }"
            :disabled="readonly"
            :name="field.attribute"
            />

          <div
            v-if="
              field.appendText
            "
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
          >
            <span
              class="text-base text-neutral-500 dark:text-neutral-300 sm:text-sm"
              v-text="
                field.appendText
              "
            />
          </div>
        </div>
      </FormFieldGroup>
    </BaseFormField>
  </template>

  <script setup>
  import isNil from 'lodash/isNil'

  import FormFieldGroup from '../FormFieldGroup.vue'

  const props = defineProps({
    field: { type: Object, required: true },
    resourceName: String,
    resourceId: [String, Number],
    validationErrors: Object,
    isFloating: Boolean,
  })

  const emit = defineEmits(['setInitialValue'])

  const model = defineModel()

  function setInitialValue() {
    emit('setInitialValue', !isNil(props.field.value) ? props.field.value : '')
  }

  setInitialValue()
  </script>
