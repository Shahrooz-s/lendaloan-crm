import SmsSettings from './components/SmsSettings.vue'
import SmsTemplate from './components/SmsTemplate.vue'
import SmsCreate from './views/SmsCreate.vue'
import SmsIndex from './views/SmsIndex.vue'
import SmsShow from './views/SmsShow.vue'
import Create from './views/SmsTemplates/Create.vue'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    router.addRoute({
      path: '/sms',
      component: SmsIndex,
      meta: {
        title: 'SMS Logs',
      },
    })

    router.addRoute({
      path: '/sms/:id',
      component: SmsShow,
      meta: {
        title: 'View SMS',
      },
    })

    router.addRoute({
      path: '/sms/create',
      component: SmsCreate,
      meta: {
        title: 'Create SMS',
      },
    })

    router.addRoute('settings', {
      path: 'sms',
      component: SmsSettings,
      name: 'sms-settings',
      meta: {
        title: 'Sms settings',
      },
    })

    router.addRoute('settings', {
      path: 'sms-templates',
      component: SmsTemplate,
      name: 'sms-templates',
      meta: {
        title: 'Sms Templates',
        subRoutes: ['create-sms-template'],
      },
      children: [
        {
          path: 'create',
          name: 'create-sms-template',
          components: {
            createEdit: Create, // Named view component
          },
          meta: { title: 'Create Sms Template' },
        },
      ],
    })
  })
}
