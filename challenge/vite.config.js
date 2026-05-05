import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// BACKEND_URL is injected at container start via docker-compose environment.
// Falls back to http://localhost:8080 for local development outside Docker.
const backendUrl = process.env.BACKEND_URL ?? 'http://localhost:8080'

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 3000,
    proxy: {
      '/api': {
        target: backendUrl,
        changeOrigin: true,
      },
    },
  },
})

