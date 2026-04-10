import { createInertiaApp } from "@inertiajs/svelte";
import { mount } from "svelte";
import {
  createMarkoPageResolver,
  createMarkoTitleResolver,
  type MarkoInertiaPages,
} from "../../../inertia/resources/js/client";

type SetupContext = {
  el: HTMLElement;
  App: unknown;
  props: Record<string, unknown>;
};

export type MarkoInertiaSvelteOptions = {
  pages: MarkoInertiaPages;
  id?: string;
  title?: (title: string, appName: string) => string;
  setup?: (context: SetupContext) => void;
};

export function bootstrapMarkoInertiaSvelte(
  options: MarkoInertiaSvelteOptions,
): Promise<unknown> {
  return createInertiaApp({
    id: options.id ?? "app",
    resolve: createMarkoPageResolver(options.pages),
    title: createMarkoTitleResolver(options.title),
    setup: ({ el, App, props }) => {
      if (options.setup) {
        options.setup({
          el: el as HTMLElement,
          App,
          props: props as Record<string, unknown>,
        });

        return;
      }

      mount(App as never, {
        target: el as HTMLElement,
        props,
      });
    },
  });
}
