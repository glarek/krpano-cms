<script>
	import { page } from '$app/stores';
	import { Button } from '$lib/components/ui/button';
	import { Home, AlertTriangle } from '@lucide/svelte';
	import favicon from '$lib/assets/favicon.svg';
	// Explicitly opt out of layout if desired, but keeping the layout is usually good for nav consistency.
	// If you want a completely standalone 404 page without the navbar, you would need a +layout.svelte reset or group.
	// Assuming we want to keep the main layout for now as it provides the background.
</script>

<svelte:head>
	<title>404 - Sidan hittades inte | GRIT</title>
</svelte:head>

<div class="flex h-[80vh] flex-col items-center justify-center text-center text-white">
	<div class="relative mb-8">
		<div
			class="absolute inset-0 animate-pulse rounded-full bg-red-500/20 blur-[50px] duration-3000"
		></div>
		<div
			class="relative flex h-32 w-32 items-center justify-center rounded-3xl border border-white/10 bg-white/5 shadow-2xl backdrop-blur-xl"
		>
			<AlertTriangle class="h-16 w-16 text-red-400" />
		</div>
	</div>

	<h1 class="font-display mb-2 text-8xl font-bold tracking-tighter text-white">404</h1>

	<h2 class="mb-6 text-2xl font-light tracking-wide text-white/80">Sidan hittades inte</h2>

	<p class="max-w-md text-lg leading-relaxed text-white/50">
		Hoppsan! Sidan du letar efter verkar ha flyttats, tagits bort eller så har du skrivit in fel
		adress.
	</p>

	<div class="mt-10 flex gap-4">
		<Button
			href="/"
			class="h-12 min-w-[160px] gap-2 rounded-xl bg-primary text-base font-medium hover:bg-primary/90"
		>
			<Home class="h-5 w-5" />
			Tillbaka hem
		</Button>
	</div>
</div>
