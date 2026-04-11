# marko/inertia-vue

Vue client scaffolding for Marko Inertia applications.

## Installation

```bash
composer require marko/inertia-vue
```

## Initialize Project Files

```bash
marko vite:init --inertia=vue
```

The Vite init flow adds the minimum Vue + Inertia client setup for a Marko project:

- updates `package.json`
- publishes a Vue-aware `vite.config.ts`
- publishes `resources/js/app.ts`

## Customizing `createInertiaApp`

The scaffold keeps `resources/js/app.ts` small and delegates the default
`createInertiaApp()` setup to `bootstrapMarkoInertiaVue()`.

Use the `setup` option when you only need to register Vue plugins, globals, or
components:

```ts
import { bootstrapMarkoInertiaVue } from "../../vendor/marko/inertia-vue/resources/js/bootstrap";

const pages = import.meta.glob([
  "./pages/**/*.vue",
  "../../app/**/resources/js/pages/**/*.vue",
  "../../modules/**/resources/js/pages/**/*.vue",
  "../../vendor/marko/**/resources/js/pages/**/*.vue",
]);

bootstrapMarkoInertiaVue({
  pages,
  title: (title, appName) => (title ? `${title} | ${appName}` : appName),
  setup: ({ app, el }) => {
    app.config.globalProperties.$appName = "Marko";
    app.mount(el);
  },
});
```

Use the `inertia` option when you want to add or override
`createInertiaApp()` config while keeping Marko's default resolver helpers:

```ts
bootstrapMarkoInertiaVue({
  pages,
  inertia: (config) => ({
    ...config,
    progress: {
      color: "#0f766e",
    },
  }),
});
```

You can also pass a partial object instead of a callback:

```ts
bootstrapMarkoInertiaVue({
  pages,
  inertia: {
    progress: {
      color: "#0f766e",
    },
  },
});
```
