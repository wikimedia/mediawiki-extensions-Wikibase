<template>
	<div class="wb-db-thankyou">
		<h2 class="wb-db-thankyou__head">
			{{ $messages.getText( $messages.KEYS.THANK_YOU_HEAD ) }}
		</h2>

		<p class="wb-db-thankyou__body">
			{{ $messages.getText( $messages.KEYS.THANK_YOU_EDIT_REFERENCE_ON_REPO_BODY ) }}
		</p>

		<div class="wb-db-thankyou__button">
			<EventEmittingButton
				type="primaryProgressive"
				size="M"
				:message="$messages.getText( $messages.KEYS.THANK_YOU_EDIT_REFERENCE_ON_REPO_BUTTON )"
				:href="repoLink"
				:new-tab="true"
				:prevent-default="false"
				@click="click"
			/>
		</div>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

/**
 * A component to thank the user for their edit and present them with
 * the option to continue editing (e.g. references) on the repository.
 */
export default defineComponent( {
	name: 'ThankYou',
	components: { EventEmittingButton },
	emits: [ 'opened-reference-edit-on-repo' ],
	props: {
		/**
		 * The link to continue editing on the repository if desired
		 */
		repoLink: {
			type: String,
			required: true,
		},
	},
	methods: {
		click(): void {
			/**
			 * An event fired when the user clicks the CTA to edit references on the repository
			 * @type {Event}
			 */
			this.$emit( 'opened-reference-edit-on-repo' );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-thankyou {
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	padding: $padding-panel-form;

	&__head {
		@include h3();
		@include marginForCenterColumnHeading();
	}

	&__body {
		@include body-responsive();
		@include marginForCenterColumn( 3 * $base-spacing-unit );
		max-width: calc( 100% - 2 * #{$margin-center-column-side} ); // restrict text content to parent width - margin

		@media ( max-width: $breakpoint ) {
			max-width: 100%; // margin is 0 on mobile (see marginForCenterColum() mixin)
		}
	}

	&__button {
		@include marginForCenterColumn();
	}
}
</style>
