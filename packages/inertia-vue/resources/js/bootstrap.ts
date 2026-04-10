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

export type MarkoInertiaVueOptions = {
  pages: MarkoInertiaPages;
  id?: string;
  title?: (title: string, appName: string) => string;
  setup?: (context: SetupContext) => void;
};

export function bootstrapMarkoInertiaVue(
  options: MarkoInertiaVueOptions,
): Promise<unknown> {
  return createInertiaApp({
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
  });
}
