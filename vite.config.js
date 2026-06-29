import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig(({ mode }) => {
      const env = loadEnv(mode, process.cwd(), "");
      const assetUrl = env.ASSET_URL || env.APP_URL || "";
      let base = "/build/";

      if (assetUrl) {
            const assetPath = new URL(assetUrl).pathname.replace(/\/$/, "");
            base = `${assetPath}/build/`;
      }

      return {
            base,
            build: {
                  chunkSizeWarningLimit: 700,
            },
            plugins: [
                  laravel({
                        input: [
                              'resources/sass/app.scss',
                              'resources/js/app.js',
                        ],
                        refresh: true,
                  }),
            ],
      };
});
