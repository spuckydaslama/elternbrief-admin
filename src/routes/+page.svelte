<svelte:options runes={true} />

<script lang="ts">
	import { onMount } from 'svelte';
	import { SvelteMap } from 'svelte/reactivity';
	import { Button } from '$lib/components/ui/button';
	import { EyeOff, Loader, Send } from 'lucide-svelte';
	import { Label } from '$lib/components/ui/label';
	import { Switch } from '$lib/components/ui/switch';
	import ProcessedInfo from './_components/ProcessedInfo.svelte';
	import type { Brief, Env, Processed } from '$lib/components/types';

	const createHash = (input: Brief): string => {
		const hashCode: number = JSON.stringify(input)
			.split('')
			.reduce((hash, char) => {
				return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
			}, 0);
		return hashCode.toString(16);
	};

	let env = $state<Env>();
	let briefe = $state<Brief[]>([]);
	let alreadyProcessedBriefe = $state<Map<string, Processed>>(new SvelteMap());
	let showAll = $state(false);

	let filteredBriefe = $derived.by(() => {
		return showAll
			? briefe
			: briefe.filter((brief) => !brief.hash || !alreadyProcessedBriefe.has(brief.hash));
	});

	onMount(async () => {
		const envResponse = await fetch('/loadEnv.php');
		// const envResponse = await fetch('/loadEnv.json');
		env = (await envResponse.json()) as unknown as {
			api_key: string;
			identifier: string;
			url: string;
		};

		try {
			const alreadyProcessedResponse = await fetch('/processed.php');
			const alreadyProcessed = await alreadyProcessedResponse.text();
			const alreadyProcessedParsed = alreadyProcessed
				.trim()
				.split('\n')
				.map((line) => JSON.parse(line) as Processed);
			console.log(alreadyProcessedParsed);
			alreadyProcessedBriefe = new SvelteMap(
				alreadyProcessedParsed.map((entry) => [entry.hash, entry])
			);
		} catch (error: unknown) {
			console.error(error);
		}

		const dataResponse = await fetch('/loadData.php');
		// const dataResponse = await fetch('/loadData.txt');
		const text = await dataResponse.text();

		const dataParsed = text
			.trim()
			.split('\n')
			.map((line) => JSON.parse(line)) as Brief[];
		const dataWithHashed = dataParsed.map((brief) => {
			return {
				...brief,
				hash: createHash(brief)
			};
		});

		briefe = dataWithHashed.sort((a, b) => (a.created < b.created ? 1 : -1));
	});

	const storeProcessed = async (hash: string, status: Processed['status']) => {
		const processed: Processed = {
			processedAt: new Date().toISOString(),
			hash,
			status
		};
		await fetch('/store.php', {
			method: 'POST',
			body: JSON.stringify(processed)
		});
		alreadyProcessedBriefe.set(hash, processed);
	};

	const bestellen = async (brief: Brief) => {
		if (!env) {
			brief.error = 'No env loaded';
			return;
		}
		brief.isSending = true;
		try {
			const image = await fetch('/motiv.jpg');
			const imageBlob = await image.blob();

			const formData = new FormData();
			formData.append('photo', imageBlob, 'motiv.jpg');
			formData.append('api_key', env.api_key);
			formData.append('identifier', env.identifier);
			formData.append('addressline1', brief.addressline1);
			if (brief.addressline2) {
				formData.append('addressline2', brief.addressline2);
			}
			formData.append('addressline3', brief.addressline3);
			formData.append('addressline4', brief.addressline4);
			formData.append('addressline5', brief.addressline5);
			formData.append('text', brief.text);

			const orderResponse = await fetch(env.url, {
				method: 'POST',
				body: formData
			});
			const orderResponseData = (await orderResponse.json()) as unknown as {
				code: number;
				error?: string;
			};
			if (orderResponseData.code !== 200) {
				brief.error = orderResponseData.error || `Error: ${orderResponseData.code}`;
				return;
			}
			const hash = brief.hash;
			if (hash) {
				await storeProcessed(hash, 'success');
			}
		} catch (error: unknown) {
			if (error instanceof Error) {
				brief.error = error.message;
			}
			console.error(error);
		} finally {
			brief.isSending = false;
		}
	};

	const ignorieren = async (brief: Brief) => {
		const hash = brief.hash;
		if (hash) {
			try {
				await storeProcessed(hash, 'ignored');
			} catch (error: unknown) {
				console.error(error);
			}
		}
	};
</script>

<svelte:head>
	<title>Admininterface - Stimme für Kinder</title>
</svelte:head>

<div class="space-y-2 p-1 md:p-4">
	<div class="flex items-center space-x-2">
		<Switch id="show-all" bind:checked={showAll} />
		<Label for="show-all" class="cursor-pointer">Zeige alle</Label>
	</div>
	<ul class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
		{#each filteredBriefe as brief}
			<li class="flex flex-col space-y-2 border p-4 shadow">
				<div class="grow">
					<div>{brief.created}</div>
					<div>{brief.addressline1}</div>
					{#if brief.addressline2}
						<div>{brief.addressline2}</div>
					{/if}
					<div>{brief.addressline3} {brief.addressline4}</div>
					<div>{brief.text}</div>
				</div>
				<div class="flex items-center gap-2">
					<div class="grow">
						{#if brief.hash && alreadyProcessedBriefe.has(brief.hash)}
							<ProcessedInfo value={alreadyProcessedBriefe.get(brief.hash)} />
						{/if}
					</div>
					<Button disabled={brief.isSending} variant="secondary" onclick={() => ignorieren(brief)}>
						Ignorieren <EyeOff />
					</Button>
					<Button
						disabled={brief.isSending}
						variant={brief.hash && alreadyProcessedBriefe.has(brief.hash)
							? 'secondary'
							: 'postkarteCta'}
						onclick={() => bestellen(brief)}
					>
						Bestellen {#if brief.isSending}
							<Loader class="animate-spin" />
						{:else}
							<Send />
						{/if}
					</Button>
				</div>
				{#if brief.error}
					<div class="text-destructive">{brief.error}</div>
				{/if}
				{brief.hash}
			</li>
		{/each}
	</ul>
</div>
