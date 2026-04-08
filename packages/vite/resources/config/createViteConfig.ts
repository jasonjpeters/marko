import { mergeConfig } from "vite";
import type { PluginOption, UserConfig } from "vite";

type BaseConfigOptions = {
  entrypoints: Array<string | null | undefined>;
  plugins?: PluginOption[];
  config?: UserConfig;
};

export function createBaseConfig(options: BaseConfigOptions): UserConfig {
  const entrypoints = options.entrypoints.filter(
    (entrypoint): entrypoint is string =>
      typeof entrypoint === "string" && entrypoint !== "",
  );

  const baseConfig: UserConfig = {
    plugins: options.plugins ?? [],
    server: {
      host: "0.0.0.0",
      port: 5173,
      strictPort: true,
    },
    build: {
      manifest: true,
      outDir: "public/build",
      emptyOutDir: true,
      rollupOptions: {
        input: Array.from(new Set(entrypoints)),
      },
    },
  };

  return options.config ? mergeConfig(baseConfig, options.config) : baseConfig;
}
