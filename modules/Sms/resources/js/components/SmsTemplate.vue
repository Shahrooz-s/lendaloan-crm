<template>
  <SmsTemplateResourcemanager
    :resource-name="resourceName"
    :with-cancel="false"
  >
    <template #header>
      <ICardHeading :text="$t('sms::sms.sms_template_create')" />
    </template>
  </SmsTemplateResourcemanager>
</template>

<script setup>
import SmsTemplateResourcemanager from './SmsTemplateResourcemanager.vue'

const resourceName = Innoclapps.resourceName('sms_templates')
</script>
