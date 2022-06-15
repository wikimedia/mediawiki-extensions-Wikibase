<template>
	<div class="wb-db-bailout-actions">
		<h2 class="wb-db-bailout-actions__heading">
			{{ $messages.getText( $messages.KEYS.BAILOUT_HEADING ) }}
		</h2>
		<ul>
			<li class="wb-db-bailout-actions__suggestion">
				{{ $messages.getText( $messages.KEYS.BAILOUT_SUGGESTION_GO_TO_REPO ) }}<br>
				<EventEmittingButton
					class="wb-db-bailout-actions__button"
					type="primaryProgressive"
					size="M"
					:message="$messages.getText( $messages.KEYS.BAILOUT_SUGGESTION_GO_TO_REPO_BUTTON )"
					:href="originalHref"
					:new-tab="true"
					:prevent-default="false"
				/>
			</li>
			<li
				class="wb-db-bailout-actions__suggestion"
				v-html="$messages.get( $messages.KEYS.BAILOUT_SUGGESTION_EDIT_ARTICLE, editArticleUrl )"
			/>
		</ul>
	</div>
</template>

<script lang="ts">
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import { defineComponent } from 'vue';

/**
 * A component to present the user with alternative suggestions
 * if they cannot use the Data Bridge to edit a value for some reason.
 * (That reason is typically displayed above this component in an IconMessageBox.)
 */
export default defineComponent( {
	name: 'BailoutActions',
	components: { EventEmittingButton },
	props: {
		/**
		 * The original URL of the bridge edit link,
		 * used for the “edit on the repo” button.
		 */
		originalHref: {
			type: String,
			required: true,
		},
		/**
		 * The title of the client page with the bridge edit link,
		 * used for the “article editor” link.
		 */
		pageTitle: {
			required: true,
			type: String,
		},
	},
	computed: {
		/**
		 * The full URL of the “article editor” link.
		 */
		editArticleUrl(): string {
			return this.$clientRouter.getPageUrl( this.pageTitle, { action: 'edit' } );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-bailout-actions {
	$margin-to-error-message: 2 * $base-spacing-unit-fixed;
	$margin-between-suggestions: 2 * $base-spacing-unit-fixed;

	@include marginForCenterColumn( $margin-to-error-message, 0 );

	&__heading {
		margin-bottom: $heading-margin-bottom;

		@include body-responsive-bold();
	}

	&__suggestion {
		margin-bottom: $margin-between-suggestions;

		@include body-responsive();

		&:last-child {
			margin-bottom: 0;
		}
	}

	&__button {
		margin-top: $base-spacing-unit;
	}
}
</style>
