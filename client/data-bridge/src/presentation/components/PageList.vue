<template>
	<ul>
		<li v-for="page in pages" :key="page">
			<a :href="router.getPageUrl( page )">{{ page }}</a>
		</li>
	</ul>
</template>

<script lang="ts">
import { defineComponent, PropType } from 'vue';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

/**
 * A component which renders page names into a list, incl. links to them.
 *
 * This is an internal component used when communicating permission
 * violations to the user. This happens for client and repo errors
 * alike; consequently the router is injectable instead of directly
 * accessing features from ClientRouterPlugin or RepoRouterPlugin
 * from here.
 */
export default defineComponent( {
	name: 'PageList',
	props: {
		pages: {
			type: Array as PropType<string[]>,
			required: true,
		},
		router: {
			type: Object as PropType<MediaWikiRouter>,
			required: true,
		},
	},
	compatConfig: { MODE: 3 },
} );

</script>
