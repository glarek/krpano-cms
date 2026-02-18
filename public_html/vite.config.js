import tailwindcss from '@tailwindcss/vite';
import { sveltekit } from '@sveltejs/kit/vite';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [tailwindcss(), sveltekit()],
	server: {
		allowedHosts: ['krpano-cms.test'],
		proxy: {
			'/api': 'http://krpano-cms.test',
			'/projekt': 'http://krpano-cms.test'
		}
	}
});
