<script>
	import { ShieldAlert, ArrowLeft } from '@lucide/svelte';
	import { Button } from '$lib/components/ui/button';
</script>

<div class="flex min-h-screen flex-col items-center justify-center bg-[#0b0f19] p-4 text-white">
	<div class="flex w-full max-w-md flex-col items-center text-center">
		<div class="mb-6 rounded-full bg-red-500/10 p-6 ring-1 ring-red-500/20">
			<ShieldAlert class="h-12 w-12 text-red-500" />
		</div>

		<h1 class="mb-2 text-3xl font-bold tracking-tight">Åtkomst Nekad</h1>

		<p class="mb-8 text-white/50">
			Du saknar behörighet att visa detta projekt. Det kan vara skyddat med lösenord eller kräva en
			unik länk.
		</p>

		<div class="flex gap-4">
			<Button
				variant="outline"
				class="border-white/10 bg-white/5 text-white hover:bg-white/10 hover:text-white"
				href="/"
			>
				<ArrowLeft class="mr-2 h-4 w-4" />
				Gå till Dashboard
			</Button>
		</div>
	</div>
</div>
