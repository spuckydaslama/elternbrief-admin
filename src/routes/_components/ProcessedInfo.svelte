<svelte:options runes={true} />

<script lang="ts">
	import type { Processed } from '$lib/components/types';
	import { intlFormat } from 'date-fns';
	import { cn } from '$lib/utils';

	interface Props {
		value?: Processed;
	}
	const { value }: Props = $props();
</script>

{#if value}
	<div class={cn('text-sm', value.status === 'success' && 'text-postkarteCta')}>
		{value.status || '?'} at: {intlFormat(
			value.processedAt,
			{ dateStyle: 'short', timeStyle: 'short' },
			{ locale: 'de' }
		)}
	</div>
{/if}
