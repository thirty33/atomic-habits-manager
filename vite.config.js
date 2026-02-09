import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
            vue(),
        ],
        resolve: {
            alias: {
                'vue': 'vue/dist/vue.esm-bundler.js',
                '@': path.resolve(__dirname, 'resources/js'),
            },
        },
        server: {
            cors: true,
            allowedHosts: env.VITE_TUNNEL_URL ? [env.VITE_TUNNEL_URL] : [],
            hmr: env.VITE_TUNNEL_URL
                ? { protocol: 'wss', host: env.VITE_TUNNEL_URL, clientPort: 443 }
                : { host: 'localhost' },
        },
    };
});
