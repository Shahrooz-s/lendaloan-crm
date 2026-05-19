<template>
    <BaseIndexField
      :resource-name="resourceName"
      :resource-id="resourceId"
      :row="row"
      :field="field"
    >
      <template v-for="(_, name) in $slots" #[name]="slotData">
        <slot :name="name" v-bind="slotData" />
      </template>

      <slot name="custom-text-field" :formatted-value="formattedValue">
        {{ formattedValue }}
      </slot>
    </BaseIndexField>
  </template>

  <script setup>
  import { computed } from 'vue'
  import isNil from 'lodash/isNil'

  const props = defineProps([
    'column',
    'row',
    'field',
    'resourceName',
    'resourceId',
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
