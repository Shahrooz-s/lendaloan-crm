import CreateInvoiceModal from './components/CreateInvoiceModal.vue'
import SettingsInvoices from './components/SettingsInvoices.vue'
import InvoicesCreate from './views/InvoicesCreate.vue'
import InvoicesIndex from './views/InvoicesIndex.vue'
import registerFields from './fields'
import CustomActionModal from './components/Actions/CustomActionModal.vue'
import CustomModal from './components/CustomModal.vue'
import ProductsCreateModal from './components/ProductsCreateModal.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    app.component('CreateInvoiceModal', CreateInvoiceModal)
    app.component('CustomModal', CustomModal)
    app.component('CustomActionModal', CustomActionModal)
    app.component('ProductsCreateModal', ProductsCreateModal)

    registerFields(app)

    router.addRoute({
      path: '/invoices',
      name: 'invoices-index',
      component: InvoicesIndex,
      meta: {
        title: 'invoice',
        subRoutes: ['create-invoice'],
      },
      children: [
        {
          path: 'create',
          name: 'create-invoice',
          component: InvoicesCreate,
          meta: { title: 'Create Invoice' },
        },
      ],
    })

    router.addRoute('settings', {
      path: 'invoices', // "/settings/invoices"
      component: SettingsInvoices,
      meta: {
        title: 'invoice',
      },
    })
  })
}
