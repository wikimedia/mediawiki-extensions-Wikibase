<template>
	<a
		v-if="userId !== 0"
		:href="router.getPageUrl( `Special:Redirect/user/${userId}` )"
	>
		<bdi>{{ userName }}</bdi>
	</a>
	<bdi v-else>{{ userName }}</bdi>
</template>

<script lang="ts">
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import Component from 'vue-class-component';
import { Prop, Vue } from 'vue-property-decorator';

@Component
export default class UserLink extends Vue {
	@Prop( { required: true } )
	private readonly userId!: number;
	@Prop( { required: true } )
	private readonly userName!: string;
	@Prop( { required: true } )
	private readonly router!: MediaWikiRouter;
}
</script>
