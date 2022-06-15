<!-- span added to ensure there is a single root and eslint is happy -->
<template>
	<span>
		<a
			v-if="userId !== 0"
			:href="router.getPageUrl( `Special:Redirect/user/${userId}` )"
		>
			<bdi>{{ userName }}</bdi>
		</a>
		<bdi v-else>{{ userName }}</bdi>
	</span>
</template>

<script lang="ts">
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { defineComponent, PropType } from 'vue';

/**
 * A component which renders a link to a user page on a certain wiki.
 *
 * This is an internal component used when communicating permission
 * violations to the user. This happens for client and repo errors
 * alike; consequently the router is injectable instead of directly
 * accessing features from ClientRouterPlugin or RepoRouterPlugin
 * from here.
 */
export default defineComponent( {
	name: 'UserLink',
	props: {
		/**
		 * The user ID, or 0 if the user has no account on this wiki
		 * (can happen for certain cross-wiki administrative actions);
		 * in that case, the link is omitted, and only the name is shown.
		 */
		userId: {
			type: Number,
			required: true,
		},
		/**
		 * The user name (without User: prefix).
		 */
		userName: {
			type: String,
			required: true,
		},
		/**
		 * A router for the wiki to which the link should point.
		 */
		router: {
			type: Object as PropType<MediaWikiRouter>,
			required: true,
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>
