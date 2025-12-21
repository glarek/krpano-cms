import { redirect } from '@sveltejs/kit';

export const load = async ({ fetch, depends }) => {
	depends('app:dashboard');

	try {
		// Authenticate first
		const authRes = await fetch('/api/check_auth.php');
		const authData = await authRes.json();

		if (!authData.authenticated) {
			throw redirect(302, '/login');
		}

		const res = await fetch('/api/dashboard.php');
		if (!res.ok) throw new Error('Server error');

		const data = await res.json();

		if (!data.success) {
			// If the API explicitly says unsuccessful (maybe session expired during fetch), redirect
			throw redirect(302, '/login');
		}

		return {
			groups: data.groups,
			rootProjects: data.rootProjects,
			stats: data.stats,
			authData: data.authData,
			serverOffline: false
		};
	} catch (e) {
		// If it's a redirect, let it pass through
		if (e.status === 302) {
			throw e;
		}

		console.error('Load failed', e);
		return {
			groups: {},
			rootProjects: [],
			stats: {},
			authData: {},
			serverOffline: true
		};
	}
};
