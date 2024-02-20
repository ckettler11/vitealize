import { defineConfig } from "vite"

export default defineConfig({
  server: {
    port: 5173,
    strictPort: true,
  },
  build: {
    manifest: true,
    rollupOptions: {
      input: {
        // theme scripts
        theme:'./theme/index.ts',
      }
    },
    outDir: '../build',
    emptyOutDir: true,
    //causing conflicts when including multiple files.
    minify: false
  },
})