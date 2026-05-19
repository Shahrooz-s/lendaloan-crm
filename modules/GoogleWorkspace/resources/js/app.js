import GoogleWorkspaceSettings from './components/GoogleWorkspaceSettings.vue'
import ConnectGoogle from './views/ConnectGoogle.vue'
import GoogleDocsIndex from './views/GoogleDocsIndex.vue'
import GoogleDriveIndex from './views/GoogleDriveIndex.vue'
import GoogleFilePreview from './views/GoogleFilePreview.vue'
import GoogleFormsIndex from './views/GoogleFormsIndex.vue'
import GoogleSettings from './views/GoogleSettings.vue'
import GoogleSheetsIndex from './views/GoogleSheetsIndex.vue'
import GoogleSlidesIndex from './views/GoogleSlidesIndex.vue'
import GoogleSuccess from './views/GoogleSuccess.vue'
import { SidebarManager } from './sidebarInitializer.js'

if (window.Innoclapps) {
  Innoclapps.booting(function (app, router) {
    router.addRoute('settings', {
      path: 'google-workspace',
      component: GoogleSettings,
      name: 'google-workspace-settings',
      meta: {
        title: 'Google Settings',
      },
    })

    router.addRoute('settings', {
      path: 'google-workspace/activation',
      component: GoogleWorkspaceSettings,
      name: 'google-workspace-settings-activation',
      meta: {
        title: 'Google Settings',
      },
    })

    router.addRoute({
      path: '/google-docs',
      component: GoogleDocsIndex,
      meta: {
        title: 'Google Docs',
      },
    })

    router.addRoute({
      path: '/google-sheets',
      component: GoogleSheetsIndex,
      meta: {
        title: 'Google Sheets',
      },
    })

    router.addRoute({
      path: '/google/success',
      component: GoogleSuccess,
      meta: {
        title: 'Google Success',
      },
    })

    router.addRoute({
      path: '/google/connect',
      component: ConnectGoogle,
      meta: {
        title: 'Google Success',
      },
    })

    router.addRoute({
      path: '/google/preview/:type/:driveId',
      name: 'google.preview',
      component: GoogleFilePreview,
      meta: { title: 'Google File Preview' },
    })

    router.addRoute({
      path: '/google-drives',
      name: 'google.drive',
      component: GoogleDriveIndex,
      meta: {
        title: 'Google Drive Files',
      },
    })

    router.addRoute({
      path: '/google-slides',
      name: 'google.slide',
      component: GoogleSlidesIndex,
      meta: { title: 'Google slide Files' },
    })

    router.addRoute({
      path: '/google-forms',
      name: 'google.form',
      component: GoogleFormsIndex,
      meta: { title: 'Google form Files' },
    })

    router.addRoute({
      path: '/google-settings',
      name: 'google.setting',
      component: GoogleSettings,
      meta: { title: 'Google Settings' },
    })

    const sidebarManager = new SidebarManager(router)

    setTimeout(() => {
      sidebarManager.initializeDropdowns()
    }, 100)
  })
}
