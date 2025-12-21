<script>
	import { onMount } from 'svelte';
	import { page } from '$app/stores';
	import { toast } from 'svelte-sonner';
	import * as Card from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import * as Table from '$lib/components/ui/table';
	import { Badge } from '$lib/components/ui/badge';
	import { Folder, ExternalLink, ChevronRight, Lock, Eye } from '@lucide/svelte';

	let loading = $state(true);
	let error = $state('');
	let groupName = $state('');
	let projects = $state([]);
	// CHANGED: Use query param 'id' and 'token'
	let id = $derived($page.url.searchParams.get('id') || '');
	let token = $derived($page.url.searchParams.get('token') || '');

	async function fetchGroupData() {
		try {
			let url = `/api/shared_group.php?`;
			if (id) url += `id=${id}`;
			if (token) url += `&token=${token}`;
			const res = await fetch(url);
			const data = await res.json();
			if (data.success) {
				groupName = data.group_name;
				projects = data.projects;
			} else {
				error = data.message || 'Kunde inte hämta projekt.';
			}
		} catch (e) {
			console.error('Fetch failed', e);
			error = 'Kunde inte ansluta till servern.';
		} finally {
			loading = false;
		}
	}

	onMount(() => {
		if (id || token) {
			fetchGroupData();
		} else {
			error = 'Ingen giltig länk.';
			loading = false;
		}
	});

	function getTourUrl(group, project) {
		let url = `/projekt/${group}/${project}/tour.html`;
		if (token) url += `?token=${token}`;
		return url;
	}
</script>

{#if loading}
	<div class="fixed inset-0 flex items-center justify-center bg-[#0b0f19]">
		<div class="flex flex-col items-center gap-4">
			<div
				class="border-top-primary h-12 w-12 animate-spin rounded-full border-4 border-white/10"
			></div>
			<p class="animate-pulse text-white/50">Hämtar delad mapp...</p>
		</div>
	</div>
{:else if error}
	<div class="flex h-screen flex-col items-center justify-center bg-[#0b0f19] text-white">
		<Lock class="mb-4 h-16 w-16 text-white/20" />
		<h1 class="text-2xl font-semibold">Åtkomst Nekad</h1>
		<p class="text-white/50">{error}</p>
	</div>
{:else}
	<div class="min-h-screen bg-[#0b0f19] text-white selection:bg-primary/30">
		<!-- Header -->
		<header class="border-b border-white/5 bg-white/2 backdrop-blur-xl">
			<div class="mx-auto flex h-20 max-w-4xl items-center justify-between px-6">
				<div class="flex items-center gap-4">
					<img src="/img/GRIT_LOGO.svg" alt="Logo" class="h-6 brightness-0 invert" />
					<div class="mx-2 h-6 w-px bg-white/10"></div>
					<h1 class="text-lg font-semibold tracking-tight">Krpano CMS</h1>
				</div>
				<div class="flex items-center gap-2">
					<Badge variant="outline" class="border-amber-500/20 bg-amber-500/10 text-amber-500">
						<Lock class="mr-1 h-3 w-3" /> Säker vy
					</Badge>
				</div>
			</div>
		</header>

		<main class="mx-auto max-w-4xl space-y-8 px-6 py-10">
			<div>
				<h2 class="flex items-center gap-3 text-3xl font-bold tracking-tight">
					<Folder class="h-8 w-8 text-primary" />
					{groupName.replace(/-/g, ' ')}
				</h2>
				<p class="mt-2 text-white/40">Visar {projects.length} delade projekt</p>
			</div>

			<Card.Root class="overflow-hidden border-white/10 bg-white/5 backdrop-blur-md">
				<Card.Content class="p-0">
					<Table.Root>
						<Table.Header class="hidden">
							<Table.Row class="border-white/5 hover:bg-transparent">
								<Table.Head class="pl-8 font-medium text-white/40">Projektnamn</Table.Head>
								<Table.Head class="pr-8 text-right font-medium text-white/40">Länk</Table.Head>
							</Table.Row>
						</Table.Header>
						<Table.Body>
							{#each projects as pj}
								<Table.Row class="group/row border-white/5 transition-colors hover:bg-white/2">
									<Table.Cell class="py-2 pl-8">
										<div class="flex items-center gap-3">
											<Eye
												class="h-4 w-4 text-white/20 transition-transform group-hover/row:scale-110 group-hover/row:text-primary"
											/>
											<span class="text-lg font-medium">{pj.replace(/-/g, ' ')}</span>
										</div>
									</Table.Cell>
									<Table.Cell class="pr-8 text-right">
										<Button
											variant="secondary"
											size="sm"
											class="bg-white/10 text-white hover:bg-white/20"
											href={getTourUrl(groupName, pj)}
											target="_blank"
										>
											Öppna
											<ExternalLink class="ml-2 h-3.5 w-3.5" />
										</Button>
									</Table.Cell>
								</Table.Row>
							{/each}
						</Table.Body>
					</Table.Root>
				</Card.Content>
			</Card.Root>
		</main>
	</div>
{/if}

<style>
	:global(.border-top-primary) {
		border-top-color: oklch(0.6 0.2 255.45) !important;
	}
	:global(.bg-primary) {
		background-color: oklch(0.6 0.2 255.45) !important;
	}
	:global(.text-primary) {
		color: oklch(0.6 0.2 255.45) !important;
	}
</style>
