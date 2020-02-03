<template>
	<div class="wb-db-references">
		<h2 class="wb-db-references__heading">
			{{ $messages.get( $messages.KEYS.REFERENCES_HEADING ) }}
		</h2>
		<ul>
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
	margin: 0 $margin-center-column-side;

	&__heading {
		margin: px-to-em( 18px ) 0 px-to-em( 10px ) 0;
		font-weight: 600;
		font-size: 1.1em;
	}

	&__listItem {
		padding: 10px 14px 10px 0;
	}
}
</style>
