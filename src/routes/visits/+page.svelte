<svelte:options runes={true} />

<script lang="ts">
	import { onMount } from 'svelte';
	import { getWeek, getYear } from 'date-fns';
	/*
		{"timestamp":"2025-01-31 09:37:50","userAgent":"Mozilla\/5.0 (Linux; Android 10; K) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/123.0.0.0 Mobile Safari\/537.36 (Ecosia android@123.0.0.0)","referrer":""}
{"timestamp":"2025-01-31 09:52:48","userAgent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko\/20100101 Firefox\/134.0","referrer":"https:\/\/deref-gmx.net\/"}
{"timestamp":"2025-01-31 09:54:40","userAgent":"Mozilla\/5.0 (Windows NT 10.0; Win64; x64; rv:134.0) Gecko\/20100101 Firefox\/134.0","referrer":""}
		 */
	type Visit = {
		timestamp: string;
		userAgent: string;
		referrer: string;
	};

	let visitsByWeek: Record<string, number> = $state({});
	let visitsTotal = $derived(Object.values(visitsByWeek).reduce((acc, count) => acc + count, 0));

	onMount(async () => {
		const response = await fetch('/visits.php');
		const visits: Visit[] = (await response.text())
			.trim()
			.split('\n')
			.map((line) => JSON.parse(line));

		visitsByWeek = visits
			.filter((visit) => getYear(visit.timestamp) === 2025)
			.reduce<Record<string, number>>((acc, visit) => {
				const week = getWeek(visit.timestamp);
				const year = getYear(visit.timestamp);
				const key = year.toString() + '_' + week.toString();
				acc[key] = (acc[key] || 0) + 1;
				return acc;
			}, {});
	});
</script>

<h1>Visits {visitsTotal}</h1>
<ul>
	{#each Object.entries(visitsByWeek) as [week, count]}
		<li>Week {week}: {count} visits</li>
	{/each}
</ul>
