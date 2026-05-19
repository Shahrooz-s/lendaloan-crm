<template>
  <div class="sms-container">
    <ICard>
      <ICardHeader>
        <ICardHeading>{{ $t('sms::sms.send_custom_sms') }}</ICardHeading>
      </ICardHeader>

      <ICardBody>
        <IFormGroup
          label-for="activity_types"
          :label="$t('sms::sms.select_activity_type')"
        >
          <ICustomSelect
            v-model="selectedFunction"
            label="label"
            input-id="activity_types"
            :options="crmFunctions"
            @cleared="selectedFunction = null"
          />
        </IFormGroup>

        <IFormGroup
          v-if="selectedFunction"
          label-for="activities"
          :label="
            $t('sms::sms.select_activities', {
              activityType: selectedFunction.label,
            })
          "
        >
          <ICustomSelect
            v-model="selectedActivities"
            label="title"
            input-id="activities"
            :options="activities"
            multiple
            @cleared="selectedActivities = []"
          />
        </IFormGroup>

        <IFormGroup label-for="template" :label="$t('sms::sms.sms_template')">
          <ICustomSelect
            v-model="selectedTemplate"
            input-id="template"
            :options="smTemplates"
            :loading="templatesLoading"
            :placeholder="t('sms::sms.type_to_search')"
            debounce
            @cleared="smTemplates = []"
            @input="DebouncedFetchTemplates"
          />
        </IFormGroup>

        <IFormGroup
          label-for="sms_message"
          :label="$t('sms::sms.compose_sms_message')"
        >
          <IFormTextarea
            id="sms_message"
            v-model="smsMessage"
            :placeholder="$t('sms::sms.compose_sms_message')"
          />
        </IFormGroup>

        <!-- Schedule Date and Time Picker -->
        <IFormGroup
          v-if="enableSchedule"
          :label="$t('sms::sms.schedule_date_time')"
        >
          <div class="flex h-[42px] items-center space-x-3">
            <DatePicker
              v-model="scheduledDate"
              class="flex-1"
              :name="'scheduled_date'"
              :with-icon="false"
              :min-date="minSelectableDate"
            />

            <IFormInputDropdown
              v-model="scheduledTime"
              max-height="300px"
              input-id="scheduled-time"
              class="flex-1 sm:max-w-[150px]"
              :items="timeSlots"
              :placeholder="timeFormat"
              condensed
            />
          </div>
        </IFormGroup>

        <IFormCheckboxField>
          <IFormCheckbox v-model:checked="enableSchedule" />

          <IFormCheckboxLabel :text="$t('sms::sms.schedule_sms')" />
        </IFormCheckboxField>

        <IFormGroup class="mt-2">
          <ITextDark
            class="mb-1 font-medium"
            :text="$t('core::mail_template.placeholders.placeholders')"
          />

          <TextPlaceholders :placeholders="placeholders" />
        </IFormGroup>
      </ICardBody>

      <ICardFooter class="text-right">
        <IButton
          type="button"
          variant="primary"
          :disabled="isSending"
          @click="sendSms"
        >
          {{ isSending ? $t('sms::sms.sending') : $t('sms::sms.send_sms') }}
        </IButton>
      </ICardFooter>
    </ICard>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import TextPlaceholders from '@/Core/components/TextPlaceholders.vue'
import { useApp } from '@/Core/composables/useApp'
import { useDates } from '@/Core/composables/useDates'
import { generateTimeSlots } from '@/Core/utils'

const { t } = useI18n()
const { DateTime, timeFormatForLuxon, localizedTime } = useDates()
const { scriptConfig } = useApp()

// State
const crmFunctions = ref([]) // List of Activity Types
const selectedFunction = ref(null) // Selected Activity Type
const activities = ref([]) // List of entries for the selected Activity Type
const selectedActivities = ref([]) // Selected entries (IDs)
const smsMessage = ref('') // SMS message
const isSending = ref(false) // Loading state for sending SMS
const scheduledDate = ref('')
const scheduledTime = ref('')
const enableSchedule = ref(false)
const templatesLoading = ref(false)
const smTemplates = ref([])
const selectedTemplate = ref([])

// Computed properties
const timeSlots = computed(() => {
  const slots = generateTimeSlots('00:00', 15, 'minutes').map(t =>
    localizedTime(DateTime.fromFormat(t, 'HH:mm').toUTC().toISO())
  )

  // If date is today, filter out past times
  if (scheduledDate.value === DateTime.now().toISODate()) {
    const now = DateTime.now()

    return slots.filter(time => {
      const timeStr = DateTime.fromFormat(
        time,
        timeFormatForLuxon.value
      ).toFormat('HH:mm')

      const slotDateTime = DateTime.fromFormat(
        `${scheduledDate.value} ${timeStr}`,
        'yyyy-MM-dd HH:mm'
      )

      return slotDateTime > now
    })
  }

  return slots
})

const timeFormat = computed(() => timeFormatForLuxon.value)
const minSelectableDate = computed(() => DateTime.now().toISODate())

const placeholders = [
  {
    tag: 'firstName',
    description: 'The first name of the contact',
    interpolation_start: '{{',
    interpolation_end: '}}',
    newlineable: false,
  },
  {
    tag: 'lastName',
    description: 'The last name of the contact',
    interpolation_start: '{{',
    interpolation_end: '}}',
    newlineable: false,
  },
  {
    tag: 'fullName',
    description: 'The full name of the contact',
    interpolation_start: '{{',
    interpolation_end: '}}',
    newlineable: false,
  },
  {
    tag: 'email',
    description: 'The email of the contact',
    interpolation_start: '{{',
    interpolation_end: '}}',
    newlineable: false,
  },
  {
    tag: 'phoneNumber',
    description: 'The phone number of the contact',
    interpolation_start: '{{',
    interpolation_end: '}}',
    newlineable: false,
  },
]

// Initialize with current date and next available time slot
const initializeDateTime = () => {
  const now = DateTime.now()
  scheduledDate.value = now.toISODate()

  // Find next available time slot (round up to nearest 15 minutes)
  const nextSlot = now
    .plus({ minutes: 15 - (now.minute % 15) })
    .startOf('minute')

  scheduledTime.value = localizedTime(nextSlot.toUTC().toISO())
}

// Check if the selected date/time is in the future
const isFutureDateTime = computed(() => {
  if (!scheduledDate.value || !scheduledTime.value) return false

  const selectedDateTime = DateTime.fromFormat(
    `${scheduledDate.value} ${DateTime.fromFormat(
      scheduledTime.value,
      timeFormatForLuxon.value
    ).toFormat('HH:mm')}`,
    'yyyy-MM-dd HH:mm'
  )

  return selectedDateTime > DateTime.now()
})

onMounted(async () => {
  try {
    const response = await Innoclapps.request().get('/activity-types')

    crmFunctions.value = response.data.map(func => ({
      value: func.name,
      id: func.id,
      label: func.name.charAt(0).toUpperCase() + func.name.slice(1),
    }))
    initializeDateTime()
  } catch (error) {
    console.error('Error fetching Activity Types:', error)
  }
})

watch(
  selectedFunction,
  async selectedActivity => {
    selectedActivities.value = []

    if (selectedActivity) {
      try {
        const activityResponse = await Innoclapps.request().get(
          `/activity-by-type/${selectedActivity.id}`
        )

        activities.value = activityResponse.data
      } catch (error) {
        console.error('Error fetching activities:', error)
      }
    } else {
      activities.value = []
    }
  },
  { immediate: true }
)

watch(
  selectedTemplate,
  async selectedTemplate => {
    smsMessage.value = selectedTemplate.body
  },
  { immediate: true }
)

// Watch for date changes to update available time slots
watch(scheduledDate, newDate => {
  if (newDate === DateTime.now().toISODate()) {
    // If selected date is today, ensure time is in the future
    const now = DateTime.now()

    const currentTime = DateTime.fromFormat(
      scheduledTime.value,
      timeFormatForLuxon.value
    )

    if (
      DateTime.fromFormat(
        `${newDate} ${currentTime.toFormat('HH:mm')}`,
        'yyyy-MM-dd HH:mm'
      ) <= now
    ) {
      // Reset to next available time slot if current selection is in the past
      initializeDateTime()
    }
  }
})

// Send SMS
const sendSms = async () => {
  if (
    !selectedFunction.value ||
    !selectedActivities.value.length ||
    !smsMessage.value
  ) {
    Innoclapps.error(t('sms::sms.error_select_function'))

    return
  }

  isSending.value = true

  try {
    // Collect all unique contact numbers from selected activities
    const contactNumbers = selectedActivities.value
      .flatMap(activity =>
        activity.contacts.flatMap(contact =>
          contact.phones.map(phone => phone.number)
        )
      )
      .filter((number, index, self) => self.indexOf(number) === index) // Remove duplicates

    // Prepare payload
    const payload = {
      activity_type_id: selectedFunction.value.id,
      activities: selectedActivities.value.map(activity => activity.id),
      contacts: contactNumbers,
      message: smsMessage.value,
    }

    // Add scheduled date if a valid future date/time is selected
    if (
      enableSchedule.value &&
      scheduledDate.value &&
      scheduledTime.value &&
      isFutureDateTime.value
    ) {
      const scheduledDateTime = DateTime.fromFormat(
        `${scheduledDate.value} ${DateTime.fromFormat(
          scheduledTime.value,
          timeFormatForLuxon.value
        ).toFormat('HH:mm')}`,
        'yyyy-MM-dd HH:mm'
      ).setZone(scriptConfig('timezone'))

      // Format as Y-m-d H:i
      payload.scheduled_at = scheduledDateTime.toFormat('yyyy-MM-dd HH:mm')
    }

    // Send SMS payload
    await Innoclapps.request().post('/send-sms', payload)

    Innoclapps.success(
      payload.scheduled_at
        ? t('sms::sms.success_sms_scheduled')
        : t('sms::sms.success_sms_processed')
    )
    resetForm()
  } catch (error) {
    Innoclapps.error(
      error.response?.data?.message || t('sms::sms.error_sending_sms')
    )
  } finally {
    isSending.value = false
  }
}

// Reset form
const resetForm = () => {
  selectedFunction.value = null
  selectedActivities.value = []
  smsMessage.value = ''
  enableSchedule.value = false
  initializeDateTime()
}

const DebouncedFetchTemplates = async event => {
  const searchString = event.target.value

  if (searchString) {
    try {
      templatesLoading.value = true

      const templates = await Innoclapps.request().get(
        `/sms-templates?search=${searchString}`
      )

      smTemplates.value = templates.data.data.map(template => {
        return {
          ...template,
          label: template.name,
          key: template.id,
        }
      })
    } catch (error) {
      console.error('Error fetching activities:', error)
    } finally {
      templatesLoading.value = false
    }
  }
}
</script>

<style scoped>
.sms-container {
  max-width: 100%;
  margin: 30px;
  padding: 20px;
}

/* Adjust the date picker input width */
:deep(.date-picker input) {
  width: 100% !important;
}
</style>
