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

type MarkoInertiaReactConfig = {
  id: string;
  resolve: ReturnType<typeof createMarkoPageResolver>;
  title: ReturnType<typeof createMarkoTitleResolver>;
  setup: (context: {
    el: HTMLElement;
    App: unknown;
    props: Record<string, unknown>;
  }) => void;
  [key: string]: unknown;
};

export type MarkoInertiaReactOptions = {
  pages: MarkoInertiaPages;
  id?: string;
  title?: (title: string, appName: string) => string;
  strictMode?: boolean;
  setup?: (context: SetupContext) => void;
  inertia?:
    | Partial<MarkoInertiaReactConfig>
    | ((config: MarkoInertiaReactConfig) => MarkoInertiaReactConfig);
};

export function bootstrapMarkoInertiaReact(
  options: MarkoInertiaReactOptions,
): Promise<unknown> {
  const config: MarkoInertiaReactConfig = {
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
