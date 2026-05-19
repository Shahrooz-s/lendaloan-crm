<template>
  <ISlideover
    id="createCompanyModal"
    :visible="visible"
    :title="title || $t('invoice::invoice.create')"
    :ok-text="$t('core::app.create')"
    :ok-disabled="form.busy"
    static
    form
    @hidden="handleModalHiddenEvent"
    @submit="createUsing ? createUsing(create) : create()"
    @update:visible="$emit('update:visible', $event)"
  >
    <FieldsPlaceholder v-if="!hasFields" />

    <slot name="top" :is-ready="hasFields" />

    <div v-show="fieldsVisible">
      <FormFields
        :fields="fields"
        :form="form"
        :resource-name="resourceName"
        focus-first
        is-floating
        @update-field-value="form.fill($event.attribute, $event.value)"
        @set-initial-value="form.set($event.attribute, $event.value)"
      >
        <template #after-deal_id-field>
          <ILink
            class="-mt-1 block text-right"
            @click="dealBeingCreated = true"
          >
            &plus; {{ $t('deals::deal.create') }}
          </ILink>
        </template>

        <template #after-contact_id-field>
          <ILink
            class="-mt-1 block text-right"
            @click="contactBeingCreated = true"
          >
            &plus; {{ $t('invoice::customers.create') }}
          </ILink>
        </template>

        <template #after-products-field>
          <ILink
            class="-mt-1 block text-right"
            @click="productBeingCreated = true"
          >
            &plus; {{ $t('billable::product.create') }}
          </ILink>
        </template>
      </FormFields>
    </div>

    <template v-if="withExtendedSubmitButtons" #modal-ok>
      <IExtendedDropdown
        type="submit"
        placement="top-end"
        :disabled="form.busy"
        :loading="form.busy"
        :text="$t('core::app.create')"
      >
        <IDropdownMenu class="min-w-48">
          <IDropdownItem
            :text="$t('core::app.create_and_add_another')"
            @click="createAndAddAnother"
          />

          <IDropdownItem
            v-show="goToList"
            :text="$t('core::app.create_and_go_to_list')"
            @click="createAndGoToList"
          />
        </IDropdownMenu>
      </IExtendedDropdown>
    </template>

    <CreateDealModal
      v-model:visible="dealBeingCreated"
      :overlay="false"
      @created="
        (handleAssociateableAdded('deal_id', $event.deal),
        (dealBeingCreated = false))
      "
    />

    <CreateContactModal
      v-model:visible="contactBeingCreated"
      :overlay="false"
      @created="
        (handleAssociateableAdded('contact_id', $event.contact),
        (contactBeingCreated = false))
      "
    />

    <ProductsCreateModal
      v-model:visible="productBeingCreated"
      :overlay="false"
      @created="
        (handleAssociateableMultiSelectAdded('products', $event),
        (productBeingCreated = false))
      "
    />
  </ISlideover>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { whenever } from '@vueuse/core'

import { useForm } from '@/Core/composables/useForm'
import { useResourceable } from '@/Core/composables/useResourceable'
import { useResourceFields } from '@/Core/composables/useResourceFields'

const props = defineProps({
  visible: { type: Boolean, default: true },
  goToList: { type: Boolean, default: true },
  redirectToView: Boolean,
  createUsing: Function,
  withExtendedSubmitButtons: Boolean,
  fieldsVisible: { type: Boolean, default: true },
  title: String,
})

const emit = defineEmits(['created', 'restored', 'update:visible', 'ready'])

const { t } = useI18n()
const router = useRouter()

const resourceName = Innoclapps.resourceName('invoices')

const dealBeingCreated = ref(false)
const productBeingCreated = ref(false)
const contactBeingCreated = ref(false)

const { fields, hasFields, findField, getCreateFields } = useResourceFields()

const { form } = useForm()
const { createResource } = useResourceable(resourceName)

whenever(() => props.visible, prepareComponent, { immediate: true })

function onAfterCreate(data) {
  data.indexRoute = { name: 'invoice-index' }

  if (data.action === 'go-to-list') {
    return router.push(data.indexRoute)
  }

  if (data.action === 'create-another') return
}

function handleAssociateableAdded(attribute, record) {
  findField(attribute).options.push(record)
  form[attribute] = record.id
}

function handleAssociateableMultiSelectAdded(attribute, record) {
  findField(attribute).options.push(record)
  form[attribute].push(record.id)
}

function handleModalHiddenEvent() {
  fields.value = []
  form.reset()
}

function create() {
  makeCreateRequest().then(() => router.push('/invoices'))
}

function createAndAddAnother() {
  makeCreateRequest('create-another').then(data => {
    form.reset()
    onAfterCreate(data)
  })
}

function createAndGoToList() {
  makeCreateRequest('go-to-list').then(onAfterCreate)
}

async function makeCreateRequest(actionType = null) {
  if (props.associations) {
    form.fill(props.associations)
  }

  let company = await createResource(form).catch(e => {
    if (e.isValidationError()) {
      Innoclapps.error(t('core::app.form_validation_failed'), 3000)
    }

    return Promise.reject(e)
  })

  let payload = {
    company: company,
    isRegularAction: actionType === null,
    action: actionType,
  }

  emit('created', payload)

  Innoclapps.success(t('core::resource.created'))

  return payload
}

async function prepareComponent() {
  fields.value = await getCreateFields(resourceName)
  emit('ready', { fields, form })
}
</script>
