# Timetracker Frontend Tests

This directory documents the frontend test suite for the **timetracker** Nextcloud app.
Tests are written with [Vitest](https://vitest.dev/) and [@vue/test-utils](https://test-utils.vuejs.org/).

---

## Running Tests

Because the project targets Node ≥ 20 but the dev machine may have an older version, all test runs go through Docker.

### One-shot run (CI / checking everything passes)

```bash
sudo docker run --rm \
  -v /home/martin.guest/workspace/update_apps_34/timetracker:/work \
  -w /work/js \
  node:18-alpine \
  sh -c "./node_modules/.bin/vitest run 2>&1"
```

### Watch mode (interactive development)

```bash
sudo docker run --rm -it \
  -v /home/martin.guest/workspace/update_apps_34/timetracker:/work \
  -w /work/js \
  node:18-alpine \
  sh -c "./node_modules/.bin/vitest 2>&1"
```

Or, if you have Node ≥ 18 available locally:

```bash
cd js
npm test           # vitest run (single pass)
npm run test:watch # vitest (watch mode)
```

---

## What Each Test File Covers

### `src/utils/__tests__/date.test.js`

Tests the two pure utility functions exported from `src/utils/date.js`:

| Function | What is tested |
|---|---|
| `startOfDay(date)` | Returns Unix seconds at 00:00:00 of the local date; result is an integer; always less than `endOfDay`; preserves day/month/year |
| `endOfDay(date)` | Returns Unix seconds at 23:59:59 of the local date; is exactly 86399 s after `startOfDay` |

These functions are critical for the date-range query that loads work intervals from the API.

### `src/__tests__/helpers.test.js`

Tests two pure helper functions that are defined inline inside `TimerView.vue`.
The function bodies are copied into the test file verbatim so the tests remain
independent of the Vue component's imports.

| Function | What is tested |
|---|---|
| `formatDuration(seconds)` | Zero, sub-minute, minute, hour, multi-hour, and multi-day durations; zero-padding; boundary at exactly 86400 s |
| `truncate(str, n)` | Falsy input returns `''`; strings shorter than `n` pass through; truncation inserts ` ...` suffix; result length equals `n` |

If the implementations in `TimerView.vue` ever change, the copies in `helpers.test.js` must be updated to match.

### `src/__tests__/ClientsView.test.js`

Smoke tests for `ClientsView.vue` — the Clients management page.

- Component mounts without throwing
- Shows `NcLoadingIcon` while the `getClients` API call is in flight
- Hides the table while loading
- Shows the table and hides the loader after the API resolves
- Renders one `<tbody>` row per client
- Numbers rows starting at 1

### `src/__tests__/GoalsView.test.js`

Smoke tests for `GoalsView.vue` — the Goals management page.

- Component mounts without throwing
- Shows `NcLoadingIcon` while both `getGoals` and `getProjects` are in flight
- Hides the table while loading
- Shows the table and hides the loader after both API calls resolve
- Renders one `<tbody>` row per goal with correct project name and interval
- Shows the expected column headers (Project, Target Hours, Interval)
- Populates the project `<select>` dropdown from the projects API

---

## Adding New Tests

1. **Pure functions** — create a file anywhere matching `src/**/__tests__/*.test.js`.
   Import the function directly from its source module and write `describe`/`it` blocks.
   No mocking needed for pure functions.

2. **Vue components** — follow the pattern in `ClientsView.test.js`:
   - Import the component.
   - `vi.mock('../api/index.js', ...)` at the top of the file to prevent real HTTP calls.
   - Mount with `{ global: { stubs: ncStubs } }` (copy the `ncStubs` object) so no
     real `@nextcloud/vue` components load.
   - Use `await flushPromises()` after mounting to let async `onMounted` hooks settle.

3. **New `@nextcloud/vue` components** — if a view you're testing uses a component not
   yet listed in `src/__mocks__/@nextcloud/vue.js`, add a stub export there.

---

## How the `@nextcloud` Mocks Work

Nextcloud's companion packages (`@nextcloud/axios`, `@nextcloud/router`, `@nextcloud/vue`)
read browser globals that the Nextcloud PHP server injects at page load:

| Global | Set by |
|---|---|
| `window.OC` | Nextcloud core JS |
| `window._oc_appswebroots` | Nextcloud core JS |
| `window.OC.config.session_lifetime` | Nextcloud core JS |

None of these exist in a `jsdom` environment, so importing those packages in a test
throws at module load time.

### Solution: `resolve.alias` in `vitest.config.js`

`vitest.config.js` re-maps the three problem packages to hand-written stubs under
`src/__mocks__/@nextcloud/`:

```js
resolve: {
  alias: {
    '@nextcloud/axios':  'src/__mocks__/@nextcloud/axios.js',
    '@nextcloud/router': 'src/__mocks__/@nextcloud/router.js',
    '@nextcloud/vue':    'src/__mocks__/@nextcloud/vue.js',
  },
}
```

Every file in the repo that imports from `@nextcloud/*` will transparently receive the
mock instead, without any per-test `vi.mock()` call.

### `@nextcloud/axios` mock

Returns `{ get, post, put, delete, patch }` where each method is a `vi.fn()`.
Tests can override individual methods with `.mockResolvedValue(...)`.

### `@nextcloud/router` mock

Exports `generateUrl(path)` as an identity function: `path => path`.
This means API URLs in the real code like `/apps/timetracker/ajax/clients` are used
as-is in tests — axios calls still happen against those strings, but since axios itself
is mocked, no network traffic occurs.

### `@nextcloud/vue` mock

The real package ships minified ES modules with inline CSS imports that Node / Vitest
cannot parse. The mock exports lightweight Vue stub components (each renders a single
`<div>` or `<input>`) for every `@nextcloud/vue` component used in the tested views.
This is simpler and faster than a CSS transform plugin and keeps the test environment
self-contained.
