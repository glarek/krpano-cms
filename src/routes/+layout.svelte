<script>
	import './layout.css';
	import favicon from '$lib/assets/favicon.svg';
	import { Toaster } from '$lib/components/ui/sonner';

	let { children } = $props();
</script>

<svelte:head><link rel="icon" href={favicon} /></svelte:head>

<!-- Fixed Global Background -->
<div
	class="fixed inset-0 -z-50 bg-background bg-linear-to-br from-background via-muted/20 to-background"
>
	<div
		class="absolute top-[-10%] left-[-10%] h-[50vh] w-[50vh] animate-pulse rounded-full bg-primary/20 blur-[100px]"
	></div>
	<div
		class="absolute right-[-10%] bottom-[-10%] h-[60vh] w-[60vh] rounded-full bg-blue-400/10 blur-[120px]"
		style="animation-delay: 1.5s"
	></div>
</div>

<div class="relative z-10">{@render children()}</div>

<Toaster position="top-center" richColors />
