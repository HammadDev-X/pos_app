import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
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
});
