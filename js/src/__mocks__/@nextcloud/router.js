/**
 * Minimal mock for @nextcloud/router.
 * The real package reads window._oc_appswebroots and window.OC.config which
 * are injected by the Nextcloud server and don't exist in a jsdom environment.
 * We simply pass the path through so URL construction in api/index.js works
 * without throwing.
 */
export function generateUrl(path) {
  return path
}

export function generateRemoteUrl(path) {
  return path
}

export function getRootUrl() {
  return ''
}
