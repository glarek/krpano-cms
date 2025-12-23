<script>
	import { onMount } from 'svelte';
	import { page } from '$app/stores';
	import { toast } from 'svelte-sonner';
	import * as Card from '$lib/components/ui/card';
	import { Button } from '$lib/components/ui/button';
	import * as Table from '$lib/components/ui/table';
	import { Badge } from '$lib/components/ui/badge';
	import * as Dialog from '$lib/components/ui/dialog';
	import { Input } from '$lib/components/ui/input';
	import { Label } from '$lib/components/ui/label';
	import {
		Folder,
		MoreVertical,
		Trash2,
		Edit2,
		ExternalLink,
		LogOut,
		Plus,
		ChevronRight,
		Lock,
		Unlock,
		RefreshCw,
		ArrowLeft,
		Eye,
		UploadCloud
	} from '@lucide/svelte';
	import * as DropdownMenu from '$lib/components/ui/dropdown-menu';

	let authenticated = $state(false);
	let loading = $state(true);
	let data = $state({ groups: {}, rootProjects: [], stats: {}, authData: {} });
	// CHANGED: Use query param 'id'
	let groupName = $derived($page.url.searchParams.get('id') || '');
	let projects = $derived(data.groups[groupName] || []);

	// UI State
	let renameDialogOpen = $state(false);
	let renameTarget = $state({ group: '', old_name: '', type: 'project' });
	let newName = $state('');
	let actionLoading = $state(false);

	// Upload State
	let uploadDialogOpen = $state(false);
	let uploadFile = $state(null);
	let uploadInput;

	function handleFileSelect(e) {
		const files = e.target.files;
		if (files.length > 0) {
			uploadFile = files[0];
		}
	}

	async function handleUpload() {
		if (!uploadFile) return;
		actionLoading = true;

		const formData = new FormData();
		formData.append('group', groupName);
		formData.append('file', uploadFile);

		try {
			const res = await fetch('/api/upload.php', {
				method: 'POST',
				body: formData
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Projekt uppladdat!');
				uploadDialogOpen = false;
				uploadFile = null;
				await fetchDashboard();
			} else {
				toast.error(result.message || 'Uppladdning misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid uppladdning');
		} finally {
			actionLoading = false;
		}
	}

	async function fetchDashboard() {
		try {
			const res = await fetch('/api/dashboard.php');
			const json = await res.json();
			if (json.success) {
				data = json;
			} else {
				window.location.href = '/login';
			}
		} catch (e) {
			console.error('Fetch failed', e);
			toast.error('Kunde inte hämta data');
		}
	}

	onMount(async () => {
		try {
			const res = await fetch('/api/check_auth.php');
			const auth = await res.json();
			if (auth.authenticated) {
				authenticated = true;
				await fetchDashboard();
			} else {
				window.location.href = '/login';
			}
		} catch (e) {
			console.error('Auth check failed', e);
		} finally {
			loading = false;
		}
	});

	async function handleDelete(group, project) {
		if (!confirm(`Är du säker på att du vill ta bort ${project}?`)) return;

		try {
			const res = await fetch('/api/delete.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ group, project })
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Borttagen framgångsrikt');
				await fetchDashboard();
			} else {
				toast.error(result.message || 'Borttagning misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid borttagning');
		}
	}

	function openRenameDialog(group, oldName, type) {
		renameTarget = { group, old_name: oldName, type };
		newName = oldName.replace(/-/g, ' ');
		renameDialogOpen = true;
	}

	async function handleRename() {
		actionLoading = true;
		try {
			const res = await fetch('/api/rename.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					group: renameTarget.group,
					old_name: renameTarget.old_name,
					new_name: newName
				})
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Namn ändrat');
				renameDialogOpen = false;
				await fetchDashboard();
			} else {
				toast.error(result.message || 'Misslyckades att ändra namn');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid namnändring');
		} finally {
			actionLoading = false;
		}
	}

	function getTourUrl(group, project) {
		let url = group ? `/projekt/${group}/${project}/tour.html` : `/projekt/${project}/tour.html`;
		if (group && isSecure(group)) {
			url += `?token=${data.authData[group].token}`;
		}
		return url;
	}

	function isSecure(name) {
		return data.authData && data.authData[name] && data.authData[name].token;
	}

	async function handleTokenAction(group, action) {
		if (
			action === 'delete' &&
			!confirm('Är du säker på att du vill ta bort skyddet? Länken kommer att sluta fungera.')
		)
			return;
		if (
			action === 'generate' &&
			isSecure(group) &&
			!confirm(
				'Detta kommer att generera en ny länk. Den gamla kommer att sluta fungera. Fortsätt?'
			)
		)
			return;

		actionLoading = true;
		try {
			const res = await fetch('/api/generate_token.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ project_name: group, action })
			});
			const result = await res.json();
			if (result.success) {
				toast.success(result.message);
				await fetchDashboard();
			} else {
				toast.error(result.message || 'Åtgärd misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod');
		} finally {
			actionLoading = false;
		}
	}

	async function handleLogout() {
		const res = await fetch('/api/logout.php');
		if (res.ok) window.location.href = '/login';
	}
</script>

<svelte:head>
	<title>{groupName ? groupName.replace(/-/g, ' ') + ' | ' : ''}GRIT 360-viewer</title>
	<meta
		name="description"
		content="Manage projects in {groupName ? groupName.replace(/-/g, ' ') : 'group'}."
	/>
</svelte:head>

{#if loading}
	<div class="fixed inset-0 flex items-center justify-center bg-[#0b0f19]">
		<div class="flex flex-col items-center gap-4">
			<div
				class="border-top-primary h-12 w-12 animate-spin rounded-full border-4 border-white/10"
			></div>
			<p class="animate-pulse text-white/50">Laddar grupp...</p>
		</div>
	</div>
{:else if authenticated}
	<div class="min-h-screen text-white selection:bg-primary/30">
		<!-- Nav -->
		<nav class="sticky top-0 z-50 border-b border-white/5 bg-white/2 backdrop-blur-xl">
			<div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6">
				<div class="flex items-center gap-4">
					<img src="/img/GRIT_LOGO.svg" alt="Logo" class="h-6 brightness-0 invert" />
					<div class="mx-2 h-6 w-px bg-white/10"></div>
					<h1 class="text-lg font-semibold tracking-tight">Krpano CMS</h1>
				</div>

				<div class="flex items-center gap-3">
					<Button
						variant="ghost"
						size="sm"
						class="text-white/60 hover:text-white"
						onclick={handleLogout}
					>
						<LogOut class="mr-2 h-4 w-4" />
						Logga ut
					</Button>
				</div>
			</div>
		</nav>

		<main class="mx-auto max-w-7xl space-y-10 px-6 py-10">
			<!-- Header -->
			<div class="flex items-center gap-4">
				<Button
					variant="outline"
					size="icon"
					href="/"
					class="border-white/10 bg-white/5 text-white hover:bg-white/10 hover:text-white"
				>
					<ArrowLeft class="h-4 w-4" />
				</Button>
				<div>
					<div class="flex items-center gap-3">
						<h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight">
							<Folder class="h-6 w-6 text-primary" />
							{groupName?.replace(/-/g, ' ')}
							{#if isSecure(groupName)}
								<Lock class="h-4 w-4 text-amber-400" />
							{/if}
						</h2>
						{#if isSecure(groupName)}
							<Button
								variant="ghost"
								size="icon"
								class="text-white/40 hover:text-white"
								href="/shared?id={groupName}&token={data.authData[groupName].token}"
								target="_blank"
								title="Öppna delad vy"
							>
								<ExternalLink class="h-5 w-5" />
							</Button>
						{:else}
							<Button
								variant="ghost"
								size="icon"
								class="text-white/40 hover:text-white"
								href="/shared?id={groupName}"
								target="_blank"
								title="Öppna delad vy"
							>
								<ExternalLink class="h-5 w-5" />
							</Button>
						{/if}
					</div>
					<p class="hidden text-white/40"></p>
				</div>
			</div>

			<div class="grid gap-6">
				<Card.Root
					class="group/card overflow-hidden border-white/5 bg-white/3 transition-all hover:bg-white/5"
				>
					<Card.Header
						class="flex flex-row items-center justify-between border-b border-white/5 p-4"
					>
						<div class="flex items-center gap-4">
							<div class="flex items-center gap-4 transition-colors hover:text-primary">
								<Folder class="h-5 w-5 text-primary" />
								<div>
									<Card.Title class="flex items-center gap-2 text-lg font-medium">
										Projektlista
									</Card.Title>
								</div>
							</div>
						</div>
						<div class="flex items-center gap-2">
							<Button
								variant="outline"
								size="sm"
								class="border-white/10 bg-white/5 text-white hover:bg-white/10"
								onclick={() => (createGroupDialogOpen = true)}
							>
								<Plus class="mr-2 h-4 w-4" /> Lägg till projekt (WIP)
							</Button>
							<DropdownMenu.Root>
								<DropdownMenu.Trigger>
									<Button variant="ghost" size="icon" class="text-white/40">
										<MoreVertical class="h-4 w-4" />
									</Button>
								</DropdownMenu.Trigger>
								<DropdownMenu.Content class="border-white/10 bg-[#1a1f2e] text-white">
									<DropdownMenu.Item
										onclick={() => openRenameDialog(groupName, groupName, 'group')}
									>
										<Edit2 class="mr-2 h-4 w-4" /> Ändra gruppnamn
									</DropdownMenu.Item>
									{#if isSecure(groupName)}
										<DropdownMenu.Item onclick={() => handleTokenAction(groupName, 'generate')}>
											<RefreshCw class="mr-2 h-4 w-4" /> Generera ny länk
										</DropdownMenu.Item>
										<DropdownMenu.Item
											class="text-red-400 hover:text-red-300"
											onclick={() => handleTokenAction(groupName, 'delete')}
										>
											<Unlock class="mr-2 h-4 w-4" /> Ta bort skydd
										</DropdownMenu.Item>
									{:else}
										<DropdownMenu.Item onclick={() => handleTokenAction(groupName, 'generate')}>
											<Lock class="mr-2 h-4 w-4" /> Säkra (Generera länk)
										</DropdownMenu.Item>
									{/if}
								</DropdownMenu.Content>
							</DropdownMenu.Root>
						</div>
					</Card.Header>
					<Card.Content class="p-0">
						<Table.Root>
							<Table.Header class="hidden">
								<Table.Row class="border-white/5 hover:bg-transparent">
									<Table.Head class="pl-8 font-medium text-white/40">Projektnamn</Table.Head>
									<Table.Head class="font-medium text-white/40">Status</Table.Head>
									<Table.Head class="pr-8 text-right font-medium text-white/40">Åtgärder</Table.Head
									>
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
												<span class="font-medium">{pj.replace(/-/g, ' ')}</span>
											</div>
										</Table.Cell>
										<Table.Cell>
											<Badge
												variant="outline"
												class="rounded-lg border-emerald-500/20 bg-emerald-500/5 text-emerald-400"
											>
												Aktiv
											</Badge>
										</Table.Cell>
										<Table.Cell class="pr-8 text-right">
											<div class="flex items-center justify-end gap-2">
												<Button
													variant="ghost"
													size="icon"
													class="h-8 w-8 text-white/40 hover:text-white"
													href={getTourUrl(groupName, pj)}
													target="_blank"
												>
													<ExternalLink class="h-4 w-4" />
												</Button>

												<DropdownMenu.Root>
													<DropdownMenu.Trigger>
														<Button
															variant="ghost"
															size="icon"
															class="h-8 w-8 text-white/40 hover:text-white"
														>
															<MoreVertical class="h-4 w-4" />
														</Button>
													</DropdownMenu.Trigger>
													<DropdownMenu.Content class="border-white/10 bg-[#1a1f2e] text-white">
														<DropdownMenu.Item
															onclick={() => openRenameDialog(groupName, pj, 'project')}
														>
															<Edit2 class="mr-2 h-4 w-4" /> Ändra namn
														</DropdownMenu.Item>
														<DropdownMenu.Separator class="bg-white/5" />
														<DropdownMenu.Item
															class="text-red-400 hover:text-red-300"
															onclick={() => handleDelete(groupName, pj)}
														>
															<Trash2 class="mr-2 h-4 w-4" /> Ta bort
														</DropdownMenu.Item>
													</DropdownMenu.Content>
												</DropdownMenu.Root>
											</div>
										</Table.Cell>
									</Table.Row>
								{/each}
								{#if projects.length === 0}
									<Table.Row>
										<Table.Cell colspan={3} class="py-8 text-center text-white/30">
											Inga projekt uppladdade än.
										</Table.Cell>
									</Table.Row>
								{/if}
							</Table.Body>
						</Table.Root>
					</Card.Content>
				</Card.Root>
			</div>
		</main>
	</div>
{/if}

<!-- Rename Dialog -->
<Dialog.Root bind:open={renameDialogOpen}>
	<Dialog.Content class="rounded-2xl border-white/10 bg-[#1a1f2e] text-white">
		<Dialog.Header>
			<Dialog.Title>Ändra namn</Dialog.Title>
			<Dialog.Description class="text-white/50">
				Ange ett nytt namn för projektet.
			</Dialog.Description>
		</Dialog.Header>
		<div class="space-y-4 py-4">
			<div class="space-y-2">
				<Label for="new-name">Nytt namn</Label>
				<Input
					id="new-name"
					bind:value={newName}
					class="border-white/10 bg-white/5 text-white"
					onkeydown={(e) => e.key === 'Enter' && handleRename()}
				/>
			</div>
		</div>
		<Dialog.Footer>
			<Button variant="ghost" onclick={() => (renameDialogOpen = false)}>Avbryt</Button>
			<Button
				class="bg-primary hover:bg-primary/90"
				onclick={handleRename}
				disabled={actionLoading}
			>
				{#if actionLoading}
					<div
						class="border-top-white mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/20"
					></div>
				{/if}
				Spara ändringar
			</Button>
		</Dialog.Footer>
	</Dialog.Content>
</Dialog.Root>

<!-- Upload Dialog -->
<Dialog.Root bind:open={uploadDialogOpen}>
	<Dialog.Content class="rounded-2xl border-white/10 bg-[#1a1f2e] text-white">
		<Dialog.Header>
			<Dialog.Title>Ladda upp projekt</Dialog.Title>
			<Dialog.Description class="text-white/50">
				Välj en ZIP-fil som innehåller ditt projekt. Mappen kommer automatiskt att skapas baserat på
				filnamnet.
			</Dialog.Description>
		</Dialog.Header>
		<div class="space-y-4 py-4">
			<div class="space-y-2">
				<Label for="file-upload">Välj ZIP-fil</Label>
				<Input
					id="file-upload"
					type="file"
					accept=".zip"
					class="cursor-pointer border-white/10 bg-white/5 text-white file:text-white"
					onchange={handleFileSelect}
				/>
			</div>
		</div>
		<Dialog.Footer>
			<Button variant="ghost" onclick={() => (uploadDialogOpen = false)}>Avbryt</Button>
			<Button
				class="bg-primary hover:bg-primary/90"
				onclick={handleUpload}
				disabled={actionLoading || !uploadFile}
			>
				{#if actionLoading}
					<div
						class="border-top-white mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/20"
					></div>
				{/if}
				Ladda upp
			</Button>
		</Dialog.Footer>
	</Dialog.Content>
</Dialog.Root>

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
	:global(.border-top-white) {
		border-top-color: white !important;
	}
</style>
