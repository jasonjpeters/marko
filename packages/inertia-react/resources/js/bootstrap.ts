import { createInertiaApp } from "@inertiajs/react";
import { createElement, StrictMode, type ReactElement } from "react";
import { createRoot, type Root } from "react-dom/client";
import {
  createMarkoPageResolver,
  createMarkoTitleResolver,
  type MarkoInertiaPages,
} from "../../../inertia/resources/js/client";

type SetupContext = {
  root: Root;
  el: HTMLElement;
  App: unknown;
  props: Record<string, unknown>;
  app: ReactElement;
};

export type MarkoInertiaReactOptions = {
  pages: MarkoInertiaPages;
  id?: string;
  title?: (title: string, appName: string) => string;
  strictMode?: boolean;
  setup?: (context: SetupContext) => void;
};

export function bootstrapMarkoInertiaReact(
  options: MarkoInertiaReactOptions,
): Promise<unknown> {
  return createInertiaApp({
    id: options.id ?? "app",
    resolve: createMarkoPageResolver(options.pages),
    title: createMarkoTitleResolver(options.title),
    setup: ({ el, App, props }) => {
      const root = createRoot(el as HTMLElement);
      const app = createElement(App as never, props);

      if (options.setup) {
        options.setup({
          root,
          el: el as HTMLElement,
          App,
          props: props as Record<string, unknown>,
          app,
        });

        return;
      }

      root.render(
        options.strictMode === false
          ? app
          : createElement(StrictMode, null, app),
      );
    },
  });
}
