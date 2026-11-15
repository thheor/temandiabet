import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig, loadEnv } from 'vite'

// base comes from VITE_BASE_PATH: '/temandiabet/' in development, '/' in production.
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'VITE_')

  return {
    base: env.VITE_BASE_PATH || '/temandiabet/',
    plugins: [react(), tailwindcss()],
    server: { port: 5173 },
  }
})
