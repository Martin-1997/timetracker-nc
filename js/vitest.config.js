import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
  },
  resolve: {
    alias: {
      // Mock @nextcloud/* packages that require Nextcloud browser globals
      // (window.OC, window._oc_appswebroots, etc.) which don't exist in jsdom.
      '@nextcloud/axios': resolve(__dirname, 'src/__mocks__/@nextcloud/axios.js'),
      '@nextcloud/router': resolve(__dirname, 'src/__mocks__/@nextcloud/router.js'),
      '@nextcloud/vue': resolve(__dirname, 'src/__mocks__/@nextcloud/vue.js'),
    },
  },
})
