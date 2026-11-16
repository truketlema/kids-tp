# .

This template should help get you started developing with Vue 3 in Vite.

## Recommended IDE Setup

[VS Code](https://code.visualstudio.com/) + [Vue (Official)](https://marketplace.visualstudio.com/items?itemName=Vue.volar) (and disable Vetur).

## Recommended Browser Setup

- Chromium-based browsers (Chrome, Edge, Brave, etc.):
  - [Vue.js devtools](https://chromewebstore.google.com/detail/vuejs-devtools/nhdogjmejiglipccpnnnanhbledajbpd)
  - [Turn on Custom Object Formatter in Chrome DevTools](http://bit.ly/object-formatters)
- Firefox:
  - [Vue.js devtools](https://addons.mozilla.org/en-US/firefox/addon/vue-js-devtools/)
  - [Turn on Custom Object Formatter in Firefox DevTools](https://fxdx.dev/firefox-devtools-custom-object-formatters/)

## Customize configuration

See [Vite Configuration Reference](https://vite.dev/config/).

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Connect to the Laravel API

Start the backend in a separate terminal. Make sure its database is configured and migrated:

```sh
cd ../backend
php artisan migrate
php artisan serve
```

The Vite development server proxies `/api` requests to `http://127.0.0.1:8000`. Start the frontend from `frontend/` with `npm run dev`; registration and login then use the Laravel `/api/register` and `/api/login` endpoints. If the backend runs at a different address, set `VITE_API_PROXY_TARGET` in `frontend/.env.local`.

For production, set `VITE_API_BASE_URL` to the API base URL (including `/api`) when the API is hosted on a different origin. Configure the backend to allow the frontend origin.

After login or registration, the returned Sanctum token opens the protected `/dashboard` page. The dashboard loads the signed-in family and its children from `/api/user` and `/api/children`; the Add a child form creates a family-scoped child record. Login checks the parent name along with email and password. The Remember me checkbox controls whether the token lasts for the browser session or persists in local storage.

### Compile and Minify for Production

```sh
npm run build
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```
