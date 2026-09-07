import { defineConfig } from "vite"
import react from "@vitejs/plugin-react"
import tailwindcss from "@tailwindcss/vite"
import { fileURLToPath } from "node:url"

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: { alias: { "@": fileURLToPath(new URL("./src", import.meta.url)) } },
  build: {
    outDir: "assets/ui",
    rollupOptions: {
      input: "src/main.tsx",
      output: {
        entryFileNames: "sujian-ui.js",
        assetFileNames: "sujian-ui.[ext]",
      },
    },
  },
})