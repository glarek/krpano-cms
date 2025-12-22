<script>
	import { onMount } from 'svelte';
	import * as Card from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import { Input } from '$lib/components/ui/input';
	import { Label } from '$lib/components/ui/label';
	import * as Alert from '$lib/components/ui/alert';
	import { Loader2, AlertCircle, LogIn } from '@lucide/svelte';
	import { cn } from '$lib/utils';

	let username = '';
	let password = '';
	let error = '';
	let loading = false;

	async function handleLogin() {
		loading = true;
		error = '';

		try {
			const res = await fetch('/api/login.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ username, password })
			});

			const data = await res.json();

			if (data.success) {
				// Redirect to dashboard
				window.location.href = '/';
			} else {
				error = data.error || 'Inloggning misslyckades';
			}
		} catch (e) {
			error = 'Kunde inte ansluta till servern';
		} finally {
			loading = false;
		}
	}
</script>

<svelte:head>
	<title>Login | GRIT 360-viewer</title>
	<meta name="description" content="Secure admin login for GRIT 360-viewer." />
</svelte:head>

<div
	class="font-outfit fixed inset-0 flex items-center justify-center overflow-hidden bg-[#0b0f19] p-4"
>
	<!-- Background Glow -->
	<div
		class="absolute top-1/2 left-1/2 -z-10 h-[600px] w-[600px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[oklch(0.6_0.2_255.45)] opacity-10 blur-[100px]"
	></div>

	<div class="relative w-full max-w-[420px]">
		<Card.Root
			class="overflow-hidden rounded-[24px] border-white/10 bg-white/3 shadow-2xl backdrop-blur-2xl"
		>
			<Card.Header class="space-y-4 pt-10 pb-6 text-center">
				<div class="mb-4 flex justify-center">
					<img src="/img/GRIT_LOGO.svg" alt="Logo" class="h-8 brightness-0 invert" />
				</div>
				<Card.Title class="text-2xl font-semibold tracking-tight text-white">Admin Login</Card.Title
				>
				<Card.Description class="text-white/50">
					Vänligen logga in för att fortsätta till kontrollpanelen.
				</Card.Description>
			</Card.Header>
			<Card.Content class="px-8 pb-8">
				{#if error}
					<Alert.Root
						variant="destructive"
						class="mb-6 animate-in rounded-xl border-destructive/20 bg-destructive/10 text-destructive fade-in slide-in-from-top-2"
					>
						<AlertCircle class="h-4 w-4" />
						<Alert.Title>Fel</Alert.Title>
						<Alert.Description>{error}</Alert.Description>
					</Alert.Root>
				{/if}

				<form on:submit|preventDefault={handleLogin} class="space-y-5">
					<div class="space-y-2">
						<Label for="username" class="ml-1 text-sm font-medium text-white/50">Användarnamn</Label
						>
						<Input
							type="text"
							id="username"
							bind:value={username}
							placeholder="admin@example.com"
							required
							class="h-12 rounded-xl border-white/10 bg-white/5 text-white placeholder:text-white/20 focus-visible:border-[oklch(0.6_0.2_255.45)] focus-visible:ring-[oklch(0.6_0.2_255.45)] focus-visible:ring-offset-0"
						/>
					</div>

					<div class="space-y-2">
						<Label for="password" class="ml-1 text-sm font-medium text-white/50">Lösenord</Label>
						<Input
							type="password"
							id="password"
							bind:value={password}
							placeholder="••••••••"
							required
							class="h-12 rounded-xl border-white/10 bg-white/5 text-white placeholder:text-white/20 focus-visible:border-[oklch(0.6_0.2_255.45)] focus-visible:ring-[oklch(0.6_0.2_255.45)] focus-visible:ring-offset-0"
						/>
					</div>

					<Button
						type="submit"
						disabled={loading}
						class="mt-4 h-12 w-full rounded-xl bg-[oklch(0.6_0.2_255.45)] font-semibold text-white transition-all hover:-translate-y-0.5 hover:bg-[oklch(0.55_0.2_255.45)]"
					>
						{#if loading}
							<Loader2 class="mr-2 h-5 w-5 animate-spin" />
							Loggar in...
						{:else}
							<LogIn class="mr-2 h-5 w-5" />
							Logga in
						{/if}
					</Button>
				</form>
			</Card.Content>
		</Card.Root>
	</div>
</div>

<style>
	:global(body) {
		margin: 0;
		padding: 0;
	}
</style>
