<script>
	import { invalidate } from '$app/navigation';
	import { toast } from 'svelte-sonner';
	import * as Card from '$lib/components/ui/card';
	import { Button, buttonVariants } from '$lib/components/ui/button';
	import * as Table from '$lib/components/ui/table';
	import { FileDropZone } from '$lib/components/ui/file-drop-zone';
	import { Progress } from '$lib/components/ui/progress';
	import { Badge } from '$lib/components/ui/badge';
	import axios from 'axios';
	import * as Dialog from '$lib/components/ui/dialog';
	import { Input } from '$lib/components/ui/input';
	import { Label } from '$lib/components/ui/label';
	import {
		Folder,
		FileJson,
		MoreVertical,
		Trash2,
		Edit2,
		ExternalLink,
		LogOut,
		HardDrive,
		Clock,
		Cpu,
		Database,
		Plus,
		ChevronRight,
		Globe,
		Lock,
		Unlock,
		RefreshCw,
		Eye,
		UploadCloud,
		Settings,
		User
	} from '@lucide/svelte';
	import * as DropdownMenu from '$lib/components/ui/dropdown-menu';
	import * as AlertDialog from '$lib/components/ui/alert-dialog';

	let { data } = $props();

	let actionLoading = $state(false);

	// UI State
	let renameDialogOpen = $state(false);
	let createGroupDialogOpen = $state(false);
	let renameTarget = $state({ group: '', old_name: '', type: 'project' }); // type: 'project' | 'group'
	let newName = $state('');
	let newGroupName = $state('');

	// Confirm Dialog State
	let confirmDialogOpen = $state(false);
	let confirmConfig = $state({
		title: '',
		description: '',
		action: async () => {}
	});

	// Upload State
	let uploadDialogOpen = $state(false);
	let uploadFile = $state(null);
	let uploadTargetGroup = $state('');
	let uploadTargetName = $state('');
	let uploadProgress = $state(0);
	let extracting = $state(false);

	// Settings State
	let settingsDialogOpen = $state(false);
	let settingsForm = $state({
		username: 'admin', // default, maybe load from data if available
		password: ''
	});

	function triggerConfirm(title, description, action) {
		confirmConfig = {
			title,
			description,
			action: async () => {
				await action();
				confirmDialogOpen = false;
			}
		};
		confirmDialogOpen = true;
	}

	function openUploadDialog(groupId) {
		uploadTargetGroup = groupId;
		uploadTargetName = data.groups[groupId]?.name || groupId;
		uploadFile = null;
		uploadDialogOpen = true;
	}

	async function handleZoneUpload(files) {
		console.log('handleZoneUpload called with:', files);
		if (files.length > 0) {
			uploadFile = files[0];
			console.log('uploadFile set to:', uploadFile);
		}
	}

	function handleFileRejected(reason) {
		console.log('File rejected:', reason);
		toast.error(`Filen nekades: ${reason.reason}`);
	}

	async function handleUpload() {
		if (!uploadFile || !uploadTargetGroup) return;
		actionLoading = true;
		uploadProgress = 0;
		extracting = false;

		const formData = new FormData();
		// Send raw group name, backend will encode it to find the folder
		formData.append('group', uploadTargetGroup);
		formData.append('file', uploadFile);

		try {
			const res = await axios.post('/api/upload.php', formData, {
				headers: {
					'Content-Type': 'multipart/form-data'
				},
				onUploadProgress: (progressEvent) => {
					const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
					uploadProgress = percentCompleted;
					// When upload reaches 100%, set extracting to true
					if (percentCompleted === 100) {
						extracting = true;
					}
				}
			});

			const result = res.data;
			if (result.success) {
				toast.success('Projekt uppladdat!');
				uploadDialogOpen = false;
				uploadFile = null;
				uploadDialogOpen = false;
				uploadFile = null;
				await invalidate('app:dashboard');
			} else {
				toast.error(result.message || 'Uppladdning misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid uppladdning');
		} finally {
			actionLoading = false;
			uploadProgress = 0;
			extracting = false;
		}
	}

	async function handleUpdateProfile() {
		if (!settingsForm.username.trim()) return;
		actionLoading = true;
		try {
			const res = await fetch('/api/update_profile.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(settingsForm)
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Profil uppdaterad');
				settingsDialogOpen = false;
				settingsForm.password = ''; // Clear password
			} else {
				toast.error(result.message || result.error || 'Uppdatering misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid uppdatering');
		} finally {
			actionLoading = false;
		}
	}

	async function handleLogout() {
		try {
			const res = await fetch('/api/logout.php');
			if (res.ok) window.location.href = '/login';
		} catch (e) {
			toast.error('Kunde inte logga ut (serverfel?)');
		}
	}

	function requestDelete(groupId, projectKey, displayName) {
		const type = projectKey ? 'projektet' : 'gruppen';
		const name = displayName || (projectKey ? projectKey : groupId);
		triggerConfirm(
			`Ta bort ${type}?`,
			`Är du säker på att du vill ta bort ${name}? Detta går inte att ångra.`,
			() => performDelete(groupId, projectKey)
		);
	}

	async function performDelete(groupId, projectKey) {
		actionLoading = true;
		try {
			const res = await fetch('/api/delete.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					group: groupId,
					project: projectKey // Empty for group delete
				})
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Borttagen framgångsrikt');
				await invalidate('app:dashboard');
			} else {
				toast.error(result.message || 'Borttagning misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid borttagning');
		} finally {
			actionLoading = false;
		}
	}

	function openRenameDialog(id, currentName, type) {
		renameTarget = { id, key: type === 'group' ? id : currentName, name: currentName, type };
		newName = currentName;
		renameDialogOpen = true;
	}

	async function handleRename() {
		actionLoading = true;
		try {
			const res = await fetch('/api/rename.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					group: renameTarget.type === 'group' ? '' : renameTarget.id, // Parent Group ID for Project
					target_id: renameTarget.key, // ID for Group, Name for Project
					new_name: newName
				})
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Namn ändrat');
				renameDialogOpen = false;
				await invalidate('app:dashboard');
			} else {
				toast.error(result.message || 'Misslyckades att ändra namn');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid namnändring');
		} finally {
			actionLoading = false;
		}
	}

	async function handleCreateGroup() {
		if (!newGroupName.trim()) return;
		actionLoading = true;
		try {
			const res = await fetch('/api/create_group.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ group_name: newGroupName })
			});
			const result = await res.json();
			if (result.success) {
				toast.success('Grupp skapad');
				createGroupDialogOpen = false;
				newGroupName = '';
				await invalidate('app:dashboard');
			} else {
				toast.error(result.message || 'Misslyckades att skapa grupp');
			}
		} catch (e) {
			toast.error('Ett fel uppstod vid skapande av grupp');
		} finally {
			actionLoading = false;
		}
	}

	function getTourUrl(groupId, folder, token) {
		let url = `/projekt/${groupId}/${folder}/tour.html`;
		if (token) {
			url += `?token=${token}`;
		}
		return url;
	}

	function requestTokenAction(groupId, displayName, action, hasToken) {
		if (action === 'delete') {
			triggerConfirm(
				'Ta bort skydd?',
				`Är du säker på att du vill ta bort skyddet för ${displayName}? Länken kommer att sluta fungera.`,
				() => performTokenAction(groupId, action)
			);
		} else if (action === 'generate' && hasToken) {
			triggerConfirm(
				'Generera ny länk?',
				'Detta kommer att generera en ny länk. Den gamla kommer att sluta fungera. Fortsätt?',
				() => performTokenAction(groupId, action)
			);
		} else {
			performTokenAction(groupId, action);
		}
	}

	async function performTokenAction(id, action) {
		actionLoading = true;
		try {
			const res = await fetch('/api/generate_token.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ project_name: id, action })
			});
			const result = await res.json();
			if (result.success) {
				toast.success(result.message);
				await invalidate('app:dashboard');
			} else {
				toast.error(result.message || 'Misslyckades');
			}
		} catch (e) {
			toast.error('Ett fel uppstod');
		} finally {
			actionLoading = false;
		}
	}
</script>

{#if data.serverOffline}
	<div class="fixed inset-0 flex items-center justify-center bg-[#0b0f19] text-white">
		<div class="flex max-w-md flex-col items-center gap-6 p-6 text-center">
			<div class="rounded-full bg-white/5 p-6">
				<Database class="h-12 w-12 text-white/20" />
			</div>

			<div class="space-y-2">
				<h2 class="text-2xl font-bold">Ingen kontakt med servern</h2>
				<p class="text-white/60">
					Kunde inte ansluta till API:et. Kontrollera din internetanslutning eller försök igen om en
					stund.
				</p>
			</div>

			<Button
				onclick={() => window.location.reload()}
				class="min-w-[140px] bg-primary hover:bg-primary/90"
			>
				<RefreshCw class="mr-2 h-4 w-4" />
				Försök igen
			</Button>
		</div>
	</div>
{:else}
	<div class="min-h-screen text-white selection:bg-primary/30">
		<!-- Nav -->
		<nav class="sticky top-0 z-50 border-b border-white/5 bg-white/2 backdrop-blur-xl">
			<div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6">
				<div class="flex items-center gap-4">
					<img src="/img/GRIT_LOGO.svg" alt="Logo" class="h-6 brightness-0 invert" />
				</div>

				<div class="flex items-center gap-3">
					<Button
						variant="ghost"
						size="sm"
						class="text-white/60 hover:text-white"
						onclick={() => (settingsDialogOpen = true)}
					>
						<Settings class="mr-2 h-4 w-4" />
						<span class="hidden md:inline-flex">Inställningar</span>
					</Button>
					<Button
						variant="ghost"
						size="sm"
						class="text-white/60 hover:text-white"
						onclick={handleLogout}
					>
						<LogOut class="mr-2 h-4 w-4" />
						<span class="hidden md:inline-flex">Logga ut</span>
					</Button>
				</div>
			</div>
		</nav>

		<main class="mx-auto max-w-7xl space-y-10 px-6 py-10">
			<!-- Header / Stats -->
			<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
				<Card.Root class="border-white/10 bg-white/5 backdrop-blur-md">
					<Card.Content class="flex items-center gap-4 p-6">
						<div class="rounded-2xl bg-primary/10 p-3">
							<Database class="h-6 w-6 text-primary" />
						</div>
						<div>
							<p class="text-xs font-semibold tracking-wider text-white/50 uppercase">Max Upload</p>
							<p class="text-2xl font-bold">{data.stats.maxUploadMb} MB</p>
						</div>
					</Card.Content>
				</Card.Root>

				<Card.Root class="border-white/10 bg-white/5 backdrop-blur-md">
					<Card.Content class="flex items-center gap-4 p-6">
						<div class="rounded-2xl bg-blue-500/10 p-3">
							<Cpu class="h-6 w-6 text-blue-400" />
						</div>
						<div>
							<p class="text-xs font-semibold tracking-wider text-white/50 uppercase">
								Minnesgräns
							</p>
							<p class="text-2xl font-bold">{data.stats.memoryLimit}</p>
						</div>
					</Card.Content>
				</Card.Root>

				<Card.Root class="border-white/10 bg-white/5 backdrop-blur-md">
					<Card.Content class="flex items-center gap-4 p-6">
						<div class="rounded-2xl bg-amber-500/10 p-3">
							<Clock class="h-6 w-6 text-amber-400" />
						</div>
						<div>
							<p class="text-xs font-semibold tracking-wider text-white/50 uppercase">Max Tid</p>
							<p class="text-2xl font-bold">{data.stats.maxExecutionTime}s</p>
						</div>
					</Card.Content>
				</Card.Root>

				<Card.Root class="border-white/10 bg-white/5 backdrop-blur-md">
					<Card.Content class="flex items-center gap-4 p-6">
						<div class="rounded-2xl bg-purple-500/10 p-3">
							<Globe class="h-6 w-6 text-purple-400" />
						</div>
						<div>
							<p class="text-xs font-semibold tracking-wider text-white/50 uppercase">
								PHP Version
							</p>
							<p class="text-2xl font-bold">{data.stats.phpVersion}</p>
						</div>
					</Card.Content>
				</Card.Root>
			</div>

			<!-- Project Explorer -->
			<div class="space-y-8">
				<div class="flex items-center justify-between">
					<h2 class="flex items-center gap-3 text-2xl font-semibold tracking-tight">
						<Folder class="h-6 w-6 text-primary" />
						Projektarkiv
					</h2>
					<Button
						class="rounded-xl bg-primary hover:bg-primary/90"
						onclick={() => (createGroupDialogOpen = true)}
					>
						<Plus class="mr-2 h-4 w-4" />
						Skapa Grupp
					</Button>
				</div>

				<div class="grid grid-cols-1 gap-8">
					{#each Object.entries(data.groups) as [groupId, groupData]}
						<Card.Root class="border-white/5 bg-white/5">
							<Card.Header class="pb-0">
								<div class="flex flex-col justify-between gap-2 md:flex-row md:items-center">
									<div class="space-y-1">
										<div class="flex items-center gap-3">
											<Card.Title class="text-xl font-medium">
												{groupData.name}
											</Card.Title>
											{#if groupData.token}
												<div
													class="flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-500"
												>
													<Lock class="h-3 w-3" />
													<span class="hidden md:inline-flex">Skyddad</span>
												</div>
											{/if}
										</div>
										<Card.Description class="text-white/40">
											{groupData.projects.length} projekt
										</Card.Description>
									</div>

									<div class="flex items-center gap-2">
										<Button
											variant="secondary"
											size="sm"
											class="h-9 gap-2 bg-white/5 hover:bg-white/10"
											href={groupData.token
												? `/shared?id=${groupId}&token=${groupData.token}`
												: `/shared?id=${groupId}`}
											target="_blank"
										>
											<ExternalLink class="h-4 w-4 text-primary" />
											<span class="hidden md:inline-flex">Öppna delad vy</span>
										</Button>
										<Button
											variant="secondary"
											size="sm"
											class="h-9 gap-2 bg-white/5 hover:bg-white/10"
											onclick={() => openUploadDialog(groupId)}
										>
											<UploadCloud class="h-4 w-4" />
											<span class="hidden md:inline-flex">Ladda upp</span>
										</Button>

										<DropdownMenu.Root>
											<DropdownMenu.Trigger
												class={buttonVariants({ variant: 'ghost', size: 'icon' }) +
													' h-9 w-9 text-white/40 hover:text-white'}
											>
												<MoreVertical class="h-4 w-4" />
											</DropdownMenu.Trigger>
											<DropdownMenu.Content
												class="w-56 border-white/10 bg-[#1a1f2e] text-white"
												align="end"
											>
												<DropdownMenu.Label>Säkerhet</DropdownMenu.Label>
												{#if groupData.token}
													<DropdownMenu.Item
														onclick={() =>
															requestTokenAction(groupId, groupData.name, 'generate', true)}
													>
														<RefreshCw class="mr-2 h-4 w-4" /> Generera ny token
													</DropdownMenu.Item>
													<DropdownMenu.Item
														class="text-red-400 hover:text-red-300"
														onclick={() =>
															requestTokenAction(groupId, groupData.name, 'delete', true)}
													>
														<Unlock class="mr-2 h-4 w-4" /> Ta bort skydd
													</DropdownMenu.Item>
												{:else}
													<DropdownMenu.Item
														onclick={() =>
															requestTokenAction(groupId, groupData.name, 'generate', false)}
													>
														<Lock class="mr-2 h-4 w-4" /> Generera ny token
													</DropdownMenu.Item>
												{/if}
												<DropdownMenu.Separator />
												<DropdownMenu.Label>Grupp</DropdownMenu.Label>
												<DropdownMenu.Item
													onclick={() => openRenameDialog(groupId, groupData.name, 'group')}
												>
													<Edit2 class="mr-2 h-4 w-4" /> Byt namn
												</DropdownMenu.Item>
												<DropdownMenu.Item
													class="text-red-400 hover:text-red-300"
													onclick={() => requestDelete(groupId, '', groupData.name)}
												>
													<Trash2 class="mr-2 h-4 w-4" /> Ta bort grupp
												</DropdownMenu.Item>
											</DropdownMenu.Content>
										</DropdownMenu.Root>
									</div>
								</div>
							</Card.Header>

							<Card.Content class="p-0">
								<Table.Root>
									<Table.Body>
										{#each groupData.projects as pj}
											<Table.Row
												class="group/row border-white/5 transition-colors hover:bg-white/2"
											>
												<Table.Cell class="py-3 pl-6 font-medium">
													<div class="flex items-center gap-3">
														<div
															class="rounded bg-blue-500/10 p-1.5 text-blue-400 transition-colors group-hover/row:bg-blue-500/20"
														>
															<FileJson class="h-4 w-4" />
														</div>
														<div class="flex items-center gap-2">
															<span class="text-base">{pj.name}</span>
															{#if groupData.token}
																<Lock class="h-3 w-3 text-amber-500" />
															{/if}
														</div>
													</div>
												</Table.Cell>
												<Table.Cell class="py-3 pr-6 text-right">
													<div
														class="flex items-center justify-end gap-2 opacity-60 transition-opacity group-hover/row:opacity-100"
													>
														<Button
															size="sm"
															variant="ghost"
															class="h-8 gap-2 hover:bg-primary/10 hover:text-primary"
															href={getTourUrl(groupId, pj.folder, groupData.token)}
															target="_blank"
														>
															<ExternalLink class="h-3.5 w-3.5" />
															Öppna
														</Button>

														<div class="mx-1 h-4 w-px bg-white/10"></div>

														<Button
															variant="ghost"
															size="icon"
															class="h-8 w-8 text-white/40 hover:text-white"
															onclick={() => openRenameDialog(groupId, pj.name, 'project')}
															title="Byt namn"
														>
															<Edit2 class="h-3.5 w-3.5" />
														</Button>
														<Button
															variant="ghost"
															size="icon"
															class="h-8 w-8 text-white/40 hover:text-red-400"
															onclick={() => requestDelete(groupId, pj.name, pj.name)}
															title="Ta bort"
														>
															<Trash2 class="h-3.5 w-3.5" />
														</Button>
													</div>
												</Table.Cell>
											</Table.Row>
										{/each}
										{#if groupData.projects.length === 0}
											<Table.Row>
												<Table.Cell colspan={2} class="h-24 text-center text-white/30 italic">
													Inga projekt i denna grupp.
												</Table.Cell>
											</Table.Row>
										{/if}
									</Table.Body>
								</Table.Root>
							</Card.Content>
						</Card.Root>
					{/each}

					<!-- Root Projects -->
					{#if data.rootProjects.length > 0}
						<Card.Root class="border-white/5 bg-white/5">
							<Card.Header class="pb-4">
								<div class="flex items-center justify-between">
									<div class="space-y-1">
										<Card.Title class="text-xl font-medium">Fristående Projekt</Card.Title>
										<Card.Description class="text-white/40">
											{data.rootProjects.length} projekt utanför grupper
										</Card.Description>
									</div>
								</div>
							</Card.Header>
							<Card.Content class="p-0">
								<Table.Root>
									<Table.Body>
										{#each data.rootProjects as pj}
											<Table.Row
												class="group/row border-white/5 transition-colors hover:bg-white/2"
											>
												<Table.Cell class="py-3 pl-6 font-medium">
													<div class="flex items-center gap-3">
														<div
															class="rounded bg-blue-500/10 p-1.5 text-blue-400 transition-colors group-hover/row:bg-blue-500/20"
														>
															<FileJson class="h-4 w-4" />
														</div>
														<span class="text-base">{decodeURIComponent(pj)}</span>
													</div>
												</Table.Cell>
												<Table.Cell class="py-3 pr-6 text-right">
													<div
														class="flex items-center justify-end gap-2 opacity-60 transition-opacity group-hover/row:opacity-100"
													>
														<Button
															size="sm"
															variant="ghost"
															class="h-8 gap-2 hover:bg-primary/10 hover:text-primary"
															href={getTourUrl('', pj)}
															target="_blank"
														>
															<ExternalLink class="h-3.5 w-3.5" />
															Öppna
														</Button>

														<div class="mx-1 h-4 w-px bg-white/10"></div>

														<Button
															variant="ghost"
															size="icon"
															class="h-8 w-8 text-white/40 hover:text-white"
															onclick={() => openRenameDialog('', pj, 'project')}
															title="Byt namn"
														>
															<Edit2 class="h-3.5 w-3.5" />
														</Button>
														<Button
															variant="ghost"
															size="icon"
															class="h-8 w-8 text-white/40 hover:text-red-400"
															onclick={() => requestDelete('', pj)}
															title="Ta bort"
														>
															<Trash2 class="h-3.5 w-3.5" />
														</Button>
													</div>
												</Table.Cell>
											</Table.Row>
										{/each}
									</Table.Body>
								</Table.Root>
							</Card.Content>
						</Card.Root>
					{/if}
				</div>
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
				Ange ett nytt namn för {renameTarget.type === 'group' ? 'gruppen' : 'projektet'}.
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

<!-- Create Group Dialog -->
<Dialog.Root bind:open={createGroupDialogOpen}>
	<Dialog.Content class="rounded-2xl border-white/10 bg-[#1a1f2e] text-white">
		<Dialog.Header>
			<Dialog.Title>Skapa ny grupp</Dialog.Title>
			<Dialog.Description class="text-white/50">
				Ange ett namn för den nya projektgruppen.
			</Dialog.Description>
		</Dialog.Header>
		<div class="space-y-4 py-4">
			<div class="space-y-2">
				<Label for="group-name">Gruppnamn</Label>
				<Input
					id="group-name"
					bind:value={newGroupName}
					placeholder="t.ex. Bostäder"
					class="border-white/10 bg-white/5 text-white"
					onkeydown={(e) => e.key === 'Enter' && handleCreateGroup()}
				/>
			</div>
		</div>
		<Dialog.Footer>
			<Button variant="ghost" onclick={() => (createGroupDialogOpen = false)}>Avbryt</Button>
			<Button
				class="bg-primary hover:bg-primary/90"
				onclick={handleCreateGroup}
				disabled={actionLoading}
			>
				{#if actionLoading}
					<div
						class="border-top-white mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/20"
					></div>
				{/if}
				Skapa grupp
			</Button>
		</Dialog.Footer>
	</Dialog.Content>
</Dialog.Root>

<!-- Upload Dialog -->
<Dialog.Root bind:open={uploadDialogOpen}>
	<Dialog.Content class="rounded-2xl border-white/10 bg-[#1a1f2e] text-white">
		<Dialog.Header>
			<Dialog.Title>Ladda upp projekt till {uploadTargetName}</Dialog.Title>
			<Dialog.Description class="text-white/50">
				Välj en ZIP-fil. Projektmappen skapas automatiskt baserat på filnamnet.
			</Dialog.Description>
		</Dialog.Header>
		<div class="space-y-4 py-4">
			<div class="space-y-2">
				<Label for="file-upload">Välj ZIP-fil</Label>
				<FileDropZone
					id="file-upload"
					accept=".zip"
					maxFiles={1}
					fileCount={uploadFile ? 1 : 0}
					onUpload={handleZoneUpload}
					onFileRejected={handleFileRejected}
					class="h-32 border-white/10 bg-white/5 transition-colors hover:bg-white/10"
				>
					<div class="flex flex-col items-center justify-center gap-2 text-center">
						<UploadCloud class="h-8 w-8 text-white/40" />
						{#if uploadFile}
							<div class="flex items-center gap-2 rounded bg-primary/20 px-3 py-1 text-primary">
								<span class="font-medium">{uploadFile.name}</span>
							</div>
							<p class="text-xs text-white/40">Klicka eller dra för att byta fil</p>
						{:else}
							<p class="text-sm font-medium text-white/60">Dra och släpp en ZIP-fil här</p>
							<p class="text-xs text-white/40">eller klicka för att välja</p>
						{/if}
					</div>
				</FileDropZone>
			</div>
		</div>
		<Dialog.Footer class="flex flex-col gap-4 sm:flex-col">
			{#if actionLoading}
				<div class="w-full space-y-3">
					<div class="space-y-1">
						<div class="flex justify-between text-xs text-white/60">
							<span>Laddar upp...</span>
							<span>{uploadProgress}%</span>
						</div>
						<Progress value={uploadProgress} class="h-2" />
					</div>
					{#if extracting}
						<div class="space-y-1">
							<div class="flex justify-between text-xs text-white/60">
								<span>Extraherar filer...</span>
							</div>
							<Progress value={100} class="extract-progress h-2" />
						</div>
					{/if}
				</div>
			{/if}
			<div class="flex justify-end gap-2">
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
			</div>
		</Dialog.Footer>
	</Dialog.Content>
</Dialog.Root>

<!-- Alert Dialog (Replaces native confirm) -->
<AlertDialog.Root bind:open={confirmDialogOpen}>
	<AlertDialog.Content class="rounded-2xl border-white/10 bg-[#1a1f2e] text-white">
		<AlertDialog.Header>
			<AlertDialog.Title>{confirmConfig.title}</AlertDialog.Title>
			<AlertDialog.Description class="text-white/60">
				{confirmConfig.description}
			</AlertDialog.Description>
		</AlertDialog.Header>
		<AlertDialog.Footer>
			<AlertDialog.Cancel class="bg-transparent text-white/60 hover:bg-white/5 hover:text-white"
				>Avbryt</AlertDialog.Cancel
			>
			<AlertDialog.Action
				class="border-none bg-red-500 text-white hover:bg-red-600"
				onclick={confirmConfig.action}
				disabled={actionLoading}
			>
				{#if actionLoading}
					<div
						class="border-top-white mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/20"
					></div>
				{/if}
				Fortsätt
			</AlertDialog.Action>
		</AlertDialog.Footer>
	</AlertDialog.Content>
</AlertDialog.Root>

<!-- Settings Dialog -->
<Dialog.Root bind:open={settingsDialogOpen}>
	<Dialog.Content class="rounded-2xl border-white/10 bg-[#1a1f2e] text-white">
		<Dialog.Header>
			<Dialog.Title>Inställningar</Dialog.Title>
			<Dialog.Description class="text-white/50">
				Uppdatera administratörsuppgifter.
			</Dialog.Description>
		</Dialog.Header>
		<div class="space-y-4 py-4">
			<div class="space-y-2">
				<Label for="settings-username">Användarnamn</Label>
				<Input
					id="settings-username"
					bind:value={settingsForm.username}
					class="border-white/10 bg-white/5 text-white"
				/>
			</div>
			<div class="space-y-2">
				<Label for="settings-password">Nytt Lösenord</Label>
				<Input
					id="settings-password"
					type="password"
					bind:value={settingsForm.password}
					placeholder="Lämna tomt för att behålla nuvarande"
					class="border-white/10 bg-white/5 text-white"
				/>
			</div>
		</div>
		<Dialog.Footer>
			<Button variant="ghost" onclick={() => (settingsDialogOpen = false)}>Avbryt</Button>
			<Button
				class="bg-primary hover:bg-primary/90"
				onclick={handleUpdateProfile}
				disabled={actionLoading}
			>
				{#if actionLoading}
					<div
						class="border-top-white mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/20"
					></div>
				{/if}
				Spara
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

	/* Extraction progress bar styles */
	:global(.extract-progress [data-slot='progress-indicator']) {
		background-image: linear-gradient(
			45deg,
			#22c55e 25%,
			#16a34a 25%,
			#16a34a 50%,
			#22c55e 50%,
			#22c55e 75%,
			#16a34a 75%,
			#16a34a
		) !important;
		background-size: 40px 40px !important;
		background-position: 0 0;
		animation: progress-bar-stripes 1s linear infinite !important;
	}

	@keyframes progress-bar-stripes {
		0% {
			background-position: 0 0;
		}
		100% {
			background-position: 40px 0;
		}
	}
</style>
