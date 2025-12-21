import adapter from '@sveltejs/adapter-static';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			fallback: 'index.html' // Dependent on your host, usually index.html or 404.html
		}),
		alias: {
			'@/*': './path/to/lib/*',
			$src: 'src',
			$lib: 'src/lib'
		}
	}
};

export default config;
