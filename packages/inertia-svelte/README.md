# marko/inertia-svelte

Svelte client scaffolding for Marko Inertia applications.

## Installation

```bash
composer require marko/inertia-svelte
```

## Initialize Project Files

```bash
marko vite:init --inertia=svelte
```

The Vite init flow adds the minimum Svelte + Inertia client setup for a Marko project:

- updates `package.json`
- publishes a Svelte-aware `vite.config.ts`
- publishes `resources/js/app.ts`

## Customizing `createInertiaApp`

The scaffold keeps `resources/js/app.ts` small and delegates the default
`createInertiaApp()` setup to `bootstrapMarkoInertiaSvelte()`.

Use the `setup` option when you only need to control how the root app mounts:

```ts
import { mount } from "svelte";
import { bootstrapMarkoInertiaSvelte } from "../../vendor/marko/inertia-svelte/resources/js/bootstrap";

const pages = import.meta.glob([
  "./pages/**/*.svelte",
  "../../app/**/resources/js/pages/**/*.svelte",
  "../../modules/**/resources/js/pages/**/*.svelte",
  "../../vendor/marko/**/resources/js/pages/**/*.svelte",
]);

bootstrapMarkoInertiaSvelte({
  pages,
  setup: ({ el, App, props }) => {
    mount(App as never, {
      target: el,
      props,
    });
  },
});
```

Use the `inertia` option when you want to add or override
`createInertiaApp()` config while keeping Marko's default resolver helpers:

```ts
bootstrapMarkoInertiaSvelte({
  pages,
  inertia: (config) => ({
    ...config,
    progress: {
      color: "#ea580c",
    },
  }),
});
```

You can also pass a partial object instead of a callback:

```ts
bootstrapMarkoInertiaSvelte({
  pages,
  inertia: {
    progress: {
      color: "#ea580c",
    },
  },
});
```
