<svelte:options runes={true} />

<script lang="ts">
	import { onMount } from 'svelte';
	import { SvelteMap } from 'svelte/reactivity';
	import { Button } from '$lib/components/ui/button';
	import { Loader, Send } from 'lucide-svelte';

	const createHash = (input: Brief): string => {
		const hashCode: number = JSON.stringify(input)
			.split('')
			.reduce((hash, char) => {
				return char.charCodeAt(0) + (hash << 6) + (hash << 16) - hash;
			}, 0);
		return hashCode.toString(16);
	};
	type Env = {
		api_key: string;
		identifier: string;
		url: string;
	};
	type Brief = {
		text: string;
		addressline1: string;
		addressline2: string;
		addressline3: string;
		addressline4: string;
		addressline5: string;
		created: string;
		hash?: string;
		error?: string;
		isSending?: boolean;
	};
	type Processed = {
		processedAt: string;
		hash: string;
	};

	let env = $state<Env>();
	let briefe = $state<Brief[]>([]);
	let alreadyProcessedBriefe = $state<Map<string, string>>(new SvelteMap());

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
			alreadyProcessedBriefe = new SvelteMap(
				alreadyProcessedParsed.map((entry) => [entry.hash, entry.processedAt])
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
				code: string;
				error?: string;
			};
			if (orderResponseData.code !== '200') {
				brief.error = orderResponseData.error || `Error: ${orderResponseData.code}`;
				return;
			}
			const hash = brief.hash;
			if (hash) {
				const now = new Date().toISOString();
				await fetch('/store.php', {
					method: 'POST',
					body: JSON.stringify({ hash, processedAt: now })
				});
				alreadyProcessedBriefe.set(hash, new Date().toISOString());
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
</script>

<svelte:head>
	<title>Admininterface - Stimme für Kinder</title>
</svelte:head>

<div class="p-1 md:p-4">
	<ul class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
		{#each briefe as brief}
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
							<div class="text-sm font-bold text-postkarteCta">
								Processed at: {alreadyProcessedBriefe.get(brief.hash)}
							</div>
						{/if}
					</div>
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
			</li>
		{/each}
	</ul>
</div>
