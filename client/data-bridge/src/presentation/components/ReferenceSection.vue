<template>
	<div class="wb-db-references">
		<h2 class="wb-db-references__heading">
			{{ $messages.getText( $messages.KEYS.REFERENCES_HEADING ) }}
		</h2>
		<span class="wb-db-references__note">{{ $messages.getText( $messages.KEYS.REFERENCE_NOTE ) }}</span>
		<ul class="wb-db-references__list">
			<li
				class="wb-db-references__listItem"
				v-for="(referenceHTML, index) in renderedTargetReferences"
				:key="index"
				v-html="referenceHTML"
			/>
		</ul>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import StateMixin from '@/presentation/StateMixin';

/**
 * List the references of a statement.
 * Individual references are rendered in the backend via the API and the
 * resulting mark-up is presented in a list here.
 */
export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'ReferenceSection',
	computed: {
		renderedTargetReferences(): readonly string[] {
			return this.rootModule.state.renderedTargetReferences;
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-references {
	@include marginForCenterColumn();

	&__heading {
		margin-bottom: $heading-margin-bottom;

		@include h5();
	}

	&__list {
		padding: 0;
		list-style: none;
	}

	&__listItem {
		padding: 8px 0;
		display: block;

		@include body-responsive();

		// undo some styles of parser-formatted reference
		.mw-parser-output p {
			margin: 0;
		}
	}

	&__note {
		color: $wmui-color-base30;
		font-style: italic;

		@include body-responsive();
	}
}
</style>
