<template>
    <BaseDetailField
      :field="field"
      :is-floating="isFloating"
      :resource="resource"
      :resource-name="resourceName"
      :resource-id="resourceId"
    >
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>

      <slot name="custom-text-field" :formatted-value="formattedValue">
        {{ formattedValue }}
      </slot>
    </BaseDetailField>
  </template>

  <script setup>
  import { computed } from 'vue'
  import isNil from 'lodash/isNil'

  const props = defineProps([
    'resource',
    'resourceName',
    'resourceId',
    'field',
    'isFloating',
  ])

  const formattedValue = computed(() => {
    let value = props.field.value

    if (isNil(value)) {
      value = ''
    }

    return (
      (props.field.prependText || '') + formatted + (props.field.appendText || '')
    )
  })
  </script>
