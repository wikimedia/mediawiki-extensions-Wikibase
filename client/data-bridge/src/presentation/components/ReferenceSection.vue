<template>
	<div class="wb-db-references">
		<h2 class="wb-db-references__heading">
			{{ $messages.get( $messages.KEYS.REFERENCES_HEADING ) }}
		</h2>
		<ul class="wb-db-references__list">
			<li
				class="wb-db-references__listItem"
				v-for="(reference, index) in targetReferences"
				:key="index"
			>
				<div>
					<SingleReferenceDisplay
						:reference="reference"
						:separator="$messages.get( $messages.KEYS.REFERENCE_SNAK_SEPARATOR )"
					/>
				</div>
			</li>
		</ul>
	</div>
</template>

<script lang="ts">
import Component, { mixins } from 'vue-class-component';
import StateMixin from '@/presentation/StateMixin';
import SingleReferenceDisplay from '@/presentation/components/SingleReferenceDisplay.vue';
import Reference from '@/datamodel/Reference';

@Component( {
	components: { SingleReferenceDisplay },
} )
export default class ReferenceSection extends mixins( StateMixin ) {
	public get targetReferences(): Reference[] {
		return this.rootModule.getters.targetReferences;
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
