<template>
	<div class="wb-db-references">
		<h2 class="wb-db-references__heading">
			{{ $messages.get( $messages.KEYS.REFERENCES_HEADING ) }}
		</h2>
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
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';

/**
 * List the references of a statement.
 * Individual references are rendered in the backend via the API and the
 * resulting mark-up is presented in a list here.
 */
@Component( {
} )
export default class ReferenceSection extends mixins( StateMixin ) {
	public get renderedTargetReferences(): string[] {
		return this.rootModule.state.renderedTargetReferences;
	}
}
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
		padding: 10px 14px 10px 0;

		@include body-responsive();
	}
}
</style>
