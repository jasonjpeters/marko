import { createInertiaApp } from "@inertiajs/vue3";
import { createApp, h, type App as VueApp } from "vue";
import {
  createMarkoPageResolver,
  createMarkoTitleResolver,
  type MarkoInertiaPages,
} from "../../../inertia/resources/js/client";

type SetupContext = {
  app: VueApp;
  el: Element;
  App: unknown;
  props: Record<string, unknown>;
  plugin: unknown;
};

type MarkoInertiaVueConfig = {
  id: string;
  resolve: ReturnType<typeof createMarkoPageResolver>;
  title: ReturnType<typeof createMarkoTitleResolver>;
  setup: (context: {
    el: Element;
    App: unknown;
    props: Record<string, unknown>;
    plugin: unknown;
  }) => void;
  [key: string]: unknown;
};

export type MarkoInertiaVueOptions = {
  pages: MarkoInertiaPages;
  id?: string;
  title?: (title: string, appName: string) => string;
  setup?: (context: SetupContext) => void;
  inertia?:
    | Partial<MarkoInertiaVueConfig>
    | ((config: MarkoInertiaVueConfig) => MarkoInertiaVueConfig);
};

export function bootstrapMarkoInertiaVue(
  options: MarkoInertiaVueOptions,
): Promise<unknown> {
  const config: MarkoInertiaVueConfig = {
    id: options.id ?? "app",
    resolve: createMarkoPageResolver(options.pages),
    title: createMarkoTitleResolver(options.title),
    setup: ({ el, App, props, plugin }) => {
      const app = createApp({
        render: () => h(App as never, props),
      });

      app.use(plugin as never);

      if (options.setup) {
        options.setup({
          app,
          el,
          App,
          props: props as Record<string, unknown>,
          plugin,
        });

        return;
      }

      app.mount(el);
    },
  };

  const inertiaConfig =
    typeof options.inertia === "function"
      ? options.inertia(config)
      : {
          ...config,
          ...options.inertia,
        };

  return createInertiaApp(inertiaConfig as never);
}
