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

@Component
export default class PageList extends Vue {
	@Prop( { required: true } )
	private readonly pages!: string[];
	@Prop( { required: true } )
	private readonly router!: MediaWikiRouter;
}

</script>
