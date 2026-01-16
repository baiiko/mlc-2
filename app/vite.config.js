import { defineConfig, loadEnv } from 'vite';
import symfonyPlugin from 'vite-plugin-symfony';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    return {
        plugins: [
            symfonyPlugin({
                originOverride: ' ',
            }),
        ],
        base: '/build/',
        build: {
            outDir: 'public/build',
            rollupOptions: {
                input: {
                    app: './assets/app.js',
                },
            },
        },
        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            cors: true,
            allowedHosts: true,
            hmr: {
                host: 'localhost',
                clientPort: 8080,
            },
        },
    };
});
