import { defineConfig } from "astro/config";
import react from "@astrojs/react";
import sitemap from "@astrojs/sitemap";

export default defineConfig({
  site: "https://promedico.co.uk",
  output: "static",
  integrations: [react(), sitemap()],
  trailingSlash: "never",
  vite: {
    css: {
      devSourcemap: true
    }
  }
});
