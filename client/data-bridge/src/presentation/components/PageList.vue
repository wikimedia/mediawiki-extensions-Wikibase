<template>
	<ul>
		<li v-for="page in pages" :key="page">
			<a :href="router.getPageUrl( page )">{{ page }}</a>
		</li>
	</ul>
</template>

<script lang="ts">
import { Prop, Vue } from 'vue-property-decorator';
import Component from 'vue-class-component';
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
@Component
export default class PageList extends Vue {
	@Prop( { required: true } )
	private readonly pages!: string[];
	@Prop( { required: true } )
	private readonly router!: MediaWikiRouter;
}

</script>
