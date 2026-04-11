# marko/inertia-react

React client scaffolding for Marko Inertia applications.

## Installation

```bash
composer require marko/inertia-react
```

## Initialize Project Files

```bash
marko vite:init --inertia=react
```

The Vite init flow adds the minimum React + Inertia client setup for a Marko project:

- updates `package.json`
- publishes a React-aware `vite.config.ts`
- publishes `resources/js/app.ts`

## Customizing `createInertiaApp`

The scaffold keeps `resources/js/app.ts` small and delegates the default
`createInertiaApp()` setup to `bootstrapMarkoInertiaReact()`.

Use the `setup` option when you only need to wrap the app or register client
behavior:

```tsx
import { createElement, StrictMode } from "react";
import { bootstrapMarkoInertiaReact } from "../../vendor/marko/inertia-react/resources/js/bootstrap";

const pages = import.meta.glob([
  "./pages/**/*.jsx",
  "./pages/**/*.tsx",
  "../../app/**/resources/js/pages/**/*.jsx",
  "../../app/**/resources/js/pages/**/*.tsx",
  "../../modules/**/resources/js/pages/**/*.jsx",
  "../../modules/**/resources/js/pages/**/*.tsx",
  "../../vendor/marko/**/resources/js/pages/**/*.jsx",
  "../../vendor/marko/**/resources/js/pages/**/*.tsx",
]);

bootstrapMarkoInertiaReact({
  pages,
  setup: ({ root, app }) => {
    root.render(createElement(StrictMode, null, app));
  },
});
```

Use the `inertia` option when you want to add or override
`createInertiaApp()` config while keeping Marko's default resolver helpers:

```tsx
bootstrapMarkoInertiaReact({
  pages,
  inertia: (config) => ({
    ...config,
    progress: {
      color: "#2563eb",
    },
  }),
});
```

You can also pass a partial object instead of a callback:

```tsx
bootstrapMarkoInertiaReact({
  pages,
  inertia: {
    progress: {
      color: "#2563eb",
    },
  },
});
```
