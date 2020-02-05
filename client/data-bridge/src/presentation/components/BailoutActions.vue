<template>
	<div class="wb-db-bailout-actions">
		<h2 class="wb-db-bailout-actions__heading">
			{{ $messages.get( $messages.KEYS.BAILOUT_HEADING ) }}
		</h2>
		<ul>
			<li class="wb-db-bailout-actions__suggestion">
				{{ $messages.get( $messages.KEYS.BAILOUT_SUGGESTION_GO_TO_REPO ) }}<br>
				<EventEmittingButton
					class="wb-db-bailout-actions__button"
					type="primaryProgressive"
					:message="$messages.get( $messages.KEYS.BAILOUT_SUGGESTION_GO_TO_REPO_BUTTON )"
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
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

/**
 * A component to present the user with alternative suggestions
 * if they cannot use the Data Bridge to edit a value for some reason.
 * (That reason is typically displayed above this component in an IconMessageBox.)
 */
@Component( {
	components: { EventEmittingButton },
} )
export default class BailoutActions extends Vue {
	/**
	 * The original URL of the bridge edit link,
	 * used for the “edit on the repo” button.
	 */
	@Prop( { required: true, type: String } )
	public originalHref!: string;

	/**
	 * The title of the client page with the bridge edit link,
	 * used for the “article editor” link.
	 */
	@Prop( { required: true, type: String } )
	public pageTitle!: string;

	/**
	 * The full URL of the “article editor” link.
	 */
	public get editArticleUrl(): string {
		return this.$clientRouter.getPageUrl( this.pageTitle, { action: 'edit' } );
	}
}
</script>

<style lang="scss">
.wb-db-bailout-actions {
	margin: 0 $margin-center-column-side;

	&__heading {
		margin: $heading-margin-top 0 $heading-margin-bottom 0;
		font-weight: bold;
	}

	&__suggestion {
		list-style: disc outside;
		margin-bottom: 3 * $base-spacing-unit;
		margin-left: 2 * $base-spacing-unit-fixed;
		line-height: $line-height-text;
		font-size: $font-size-body;

		&:last-child {
			margin-bottom: 0;
		}
	}

	&__button {
		margin-top: $base-spacing-unit;
		font-size: $font-size-normal;
	}
}
</style>
