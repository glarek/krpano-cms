<script>
	import { onMount } from 'svelte';
	import { page } from '$app/stores';
	import { fly, fade } from 'svelte/transition';
	import * as Card from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import { Badge } from '$lib/components/ui/badge';
	import { Folder, ExternalLink, Lock, Eye, Share2, Smartphone } from '@lucide/svelte';
	import { qr } from '@svelte-put/qr/svg';
	import { createQrSvgString, createQrSvgDataUrl } from '@svelte-put/qr';

	let loading = $state(true);
	let error = $state('');
	let groupName = $state('');
	let groupId = $state(''); // The FS encoded name
	let projects = $state([]);
	let isProtected = $state(false);

	// CHANGED: Use query param 'id' and 'token'
	let id = $derived($page.url.searchParams.get('id') || '');
	let token = $derived($page.url.searchParams.get('token') || '');
	let currentUrl = $derived($page.url.href);

	async function fetchGroupData() {
		try {
			let url = `/api/shared_group.php?`;
			if (id) url += `id=${id}`;
			if (token) url += `&token=${token}`;
			const res = await fetch(url);
			const data = await res.json();
			if (data.success) {
				groupName = data.group_name;
				groupId = data.group_id;
				projects = data.projects || [];
				isProtected = data.is_protected;
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

	let logoSource = $state('/img/GRIT_LOGO_ICON.svg');

	let QRconfig = $derived({
		data: currentUrl,
		logo: logoSource,
		shape: 'square',
		moduleFill: 'black',
		anchorInnerFill: 'black',
		anchorOuterFill: 'black',

		backgroundfill: 'white',

		logoOptions: {
			ratio: 0.25
		}
	});

	let QRdataURL = $derived(createQrSvgDataUrl(QRconfig));

	onMount(async () => {
		if (id || token) {
			fetchGroupData();
		} else {
			error = 'Ingen giltig länk.';
			loading = false;
		}

		// Fetch logo and convert to base64
		try {
			const response = await fetch('/img/GRIT_LOGO_ICON.svg');
			const blob = await response.blob();
			const reader = new FileReader();
			reader.onloadend = () => {
				logoSource = reader.result;
			};
			reader.readAsDataURL(blob);
		} catch (e) {
			console.error('Failed to load logo for QR code', e);
		}
	});

	function getTourUrl(project) {
		const encodedGroup = encodeURIComponent(groupId);
		// project is an object { name, folder }
		const encodedProject = encodeURIComponent(project.folder);
		let url = `/projekt/${encodedGroup}/${encodedProject}/tour.html`;
		if (token) url += `?token=${token}`;
		return url;
	}
</script>

<svelte:head>
	{#if groupName}
		<title>{decodeURIComponent(groupName)} | GRIT 360-viewer</title>
		<meta
			name="description"
			content="Utforska virtuella visningar för {decodeURIComponent(groupName)}."
		/>
	{:else}
		<title>GRIT 360-viewer</title>
		<meta name="description" content="Säker och delbar 360-visningsplattform för dina projekt." />
	{/if}
</svelte:head>

{#if loading}
	<div class="fixed inset-0 flex items-center justify-center bg-[#0b0f19]">
		<div class="flex flex-col items-center gap-6" in:fade>
			<div class="relative">
				<div
					class="h-16 w-16 animate-spin rounded-full border-4 border-white/10 border-t-primary"
				></div>
				<div class="absolute inset-0 flex items-center justify-center">
					<div class="h-2 w-2 rounded-full bg-primary"></div>
				</div>
			</div>
			<p class="animate-pulse text-sm font-medium tracking-wide text-white/50 uppercase">
				Hämtar innehåll...
			</p>
		</div>
	</div>
{:else if error}
	<div class="flex h-screen flex-col items-center justify-center bg-[#0b0f19] text-white" in:fade>
		<div class="mb-6 rounded-full bg-red-500/10 p-6">
			<Lock class="h-12 w-12 text-red-500" />
		</div>
		<h1 class="mb-2 text-3xl font-bold tracking-tight">Åtkomst Nekad</h1>
		<p class="max-w-md text-center text-white/50">{error}</p>
	</div>
{:else}
	<div
		class="min-h-screen bg-[radial-gradient(ellipse_at_top,var(--tw-gradient-stops))] from-[#1a1f2e] via-[#0b0f19] to-black text-white selection:bg-primary/30"
	>
		<!-- Header -->
		<header class="sticky top-0 z-50 border-b border-white/5 bg-[#0b0f19]/80 backdrop-blur-xl">
			<div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">
				<div class="flex items-center gap-6">
					<img
						src="/img/GRIT_LOGO.svg"
						alt="Grit Logo"
						class="h-8 opacity-90 brightness-0 invert transition-opacity hover:opacity-100"
					/>
					<div class="hidden h-6 w-px bg-white/10 sm:block"></div>
					<h2 class="hidden text-sm font-semibold tracking-wide text-white/50 sm:block">
						360-viewer
					</h2>
				</div>
				<div class="flex items-center gap-3">
					{#if isProtected}
						<Badge
							variant="outline"
							class="border-amber-500/20 bg-amber-500/10 px-3 py-1 text-amber-500"
						>
							<Lock class="mr-1.5 h-3 w-3" /> Säker vy
						</Badge>
					{/if}
				</div>
			</div>
		</header>

		<main class="mx-auto max-w-7xl px-6 py-12 lg:px-8">
			<div class="grid gap-12 lg:grid-cols-[1fr_350px]">
				<!-- Left Column: Content -->
				<div class="space-y-8" in:fly={{ y: 20, duration: 600, delay: 100 }}>
					<!-- Title Section -->
					<div>
						<div class="mb-2 flex items-center gap-3">
							<div class="rounded-lg bg-primary/10 p-2">
								<Folder class="h-6 w-6 text-primary" />
							</div>
							<span class="text-sm font-medium tracking-wider text-primary uppercase"
								>Projektgrupp</span
							>
						</div>
						<h2 class="text-4xl font-bold tracking-tight text-white sm:text-5xl">
							{decodeURIComponent(groupName)}
						</h2>
						<p class="mt-4 max-w-2xl text-lg leading-relaxed text-white/40">
							Här hittar du alla publicerade projekt för denna grupp. Klicka på 'Öppna' för att
							starta en virtuell visning i en ny flik.
						</p>
					</div>

					<!-- Projects List -->
					<div class="grid gap-4">
						{#each projects as pj, i}
							<div
								in:fly={{ y: 20, duration: 500, delay: 200 + i * 50 }}
								class="group relative overflow-hidden rounded-xl border border-white/5 bg-white/5 p-4 transition-all hover:border-white/10 hover:bg-white/10 hover:shadow-2xl hover:shadow-primary/5"
							>
								<div class="flex items-center justify-between gap-4">
									<div class="flex items-center gap-4">
										<div
											class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-white/5 bg-gradient-to-br from-white/10 to-transparent transition-colors group-hover:border-primary/20 group-hover:from-primary/10"
										>
											<Eye
												class="h-5 w-5 text-white/40 transition-colors group-hover:text-primary"
											/>
										</div>
										<div>
											<h3
												class="text-lg font-medium text-white transition-colors group-hover:text-primary"
											>
												{decodeURIComponent(pj.name)}
											</h3>
											<p class="flex items-center gap-2 text-sm text-white/30">
												Virtuell Visning
												<span class="inline-block h-1 w-1 rounded-full bg-white/20"></span>
												Interaktiv
											</p>
										</div>
									</div>
									<Button
										href={getTourUrl(pj)}
										target="_blank"
										variant="ghost"
										class="hidden items-center gap-2 border border-transparent text-white/70 hover:border-white/10 hover:bg-white/10 hover:text-white sm:flex"
									>
										Öppna visning
										<ExternalLink class="h-4 w-4" />
									</Button>

									<!-- Mobile Button (Icon only) -->
									<Button
										href={getTourUrl(pj)}
										target="_blank"
										size="icon"
										variant="ghost"
										class="text-white/70 hover:bg-white/10 hover:text-white sm:hidden"
									>
										<ExternalLink class="h-5 w-5" />
									</Button>
								</div>
							</div>
						{/each}

						{#if projects.length === 0}
							<div
								class="rounded-xl border border-dashed border-white/10 bg-white/2 p-12 text-center"
							>
								<Folder class="mx-auto h-12 w-12 text-white/10" />
								<h3 class="mt-4 text-lg font-medium text-white/40">Inga projekt</h3>
								<p class="text-white/20">Denna mapp är tom för tillfället.</p>
							</div>
						{/if}
					</div>
				</div>

				<!-- Right Column: Sidebar (QR) -->
				<div class="space-y-6" in:fly={{ y: 20, duration: 600, delay: 300 }}>
					<Card.Root class="overflow-hidden border-white/10 bg-white/5 backdrop-blur-md">
						<Card.Header class="border-b border-white/5 pb-4">
							<Card.Title class="flex items-center gap-2 text-base font-medium">
								<Share2 class="h-4 w-4 text-primary" />
								Dela denna vy
							</Card.Title>
						</Card.Header>
						<Card.Content class="flex flex-col items-center gap-6 px-10">
							<div class="relative rounded-md bg-white p-2 shadow-xl">
								<svg width="100%" use:qr={QRconfig} />
							</div>
							<div class="text-center">
								<p class="text-sm font-medium text-white">Skanna för att öppna i mobilen</p>
								<p class="mt-1 text-xs text-white/40">Använd kameran eller en QR-läsare</p>

								<a
									href={QRdataURL}
									download="qr_code.svg"
									class="mt-4 inline-block rounded-md bg-white/10 p-1 text-[10px] tracking-wider text-white/50 transition-colors hover:text-white/40"
								>
									Ladda ner QR
								</a>
							</div>

							<div class="w-full rounded-lg bg-white/5 p-3 text-center">
								<div class="flex items-center justify-center gap-2 text-xs text-white/40">
									<Smartphone class="h-3.5 w-3.5" />
									Optimerad för mobil & surfplatta
								</div>
							</div>
						</Card.Content>
					</Card.Root>

					<div class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-4">
						<h4 class="mb-2 text-xs font-semibold tracking-wider text-blue-400 uppercase">Info</h4>
						<p class="text-xs leading-relaxed text-blue-200/60">
							{#if isProtected}
								Denna länk är säker och token-skyddad. Endast personer med denna specifika länk kan
								se innehållet.
							{:else}
								Denna länk är publik och kan delas fritt. Ingen inloggning krävs för att visa
								innehållet.
							{/if}
						</p>
					</div>
				</div>
			</div>
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
