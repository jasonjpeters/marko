import { resolve } from 'node:path';
import type { PluginOption, UserConfig } from 'vite';

type BaseConfigOptions = {
  entrypoints: Array<string | null | undefined>;
  plugins?: PluginOption[];
};

export function createBaseConfig(options: BaseConfigOptions): UserConfig {
  const entrypoints = options.entrypoints.filter(
    (entrypoint): entrypoint is string => typeof entrypoint === 'string' && entrypoint !== '',
  );

  return {
    plugins: options.plugins ?? [],
    server: {
      host: '0.0.0.0',
      port: 5173,
      strictPort: true,
    },
    build: {
      manifest: true,
      outDir: 'public/build',
      emptyOutDir: true,
      rollupOptions: {
        input: Array.from(new Set(entrypoints)),
      },
    },
  };
}
