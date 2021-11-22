<template>
	<div class="wb-db-edit-decision">
		<h2 class="wb-db-edit-decision__heading">
			{{ $messages.getText( $messages.KEYS.EDIT_DECISION_HEADING ) }}
		</h2>
		<RadioGroup>
			<RadioInput name="editDecision" html-value="replace" v-model="editDecision">
				<template slot="label">
					<span v-html="$messages.get( $messages.KEYS.EDIT_DECISION_REPLACE_LABEL )" />
				</template>
				<template slot="description">
					{{ $messages.getText( $messages.KEYS.EDIT_DECISION_REPLACE_DESCRIPTION ) }}
				</template>
			</RadioInput>
			<RadioInput name="editDecision" html-value="update" v-model="editDecision">
				<template slot="label">
					<span v-html="$messages.get( $messages.KEYS.EDIT_DECISION_UPDATE_LABEL )" />
				</template>
				<template slot="description">
					{{ $messages.getText( $messages.KEYS.EDIT_DECISION_UPDATE_DESCRIPTION ) }}
				</template>
			</RadioInput>
		</RadioGroup>
	</div>
</template>

<script lang="ts">
import Vue, { VueConstructor } from 'vue';
import StateMixin from '@/presentation/StateMixin';
import EditDecisionOption from '@/definitions/EditDecision';
import RadioGroup from '@/presentation/components/RadioGroup.vue';
import { RadioInput } from '@wmde/wikibase-vuejs-components';

export default ( Vue as VueConstructor<Vue & InstanceType<typeof StateMixin>> ).extend( {
	mixins: [ StateMixin ],
	name: 'EditDecision',
	components: {
		RadioGroup,
		RadioInput,
	},
	computed: {
		editDecision: {
			get(): EditDecisionOption | null {
				return this.rootModule.state.editDecision;
			},
			set( value: EditDecisionOption | null ): void {
				if ( value === null ) {
					throw new Error( 'Cannot set editDecision back to null!' );
				}
				this.rootModule.dispatch( 'setEditDecision', value );
			},
		},
	},
} );
</script>

<style lang="scss">
.wb-db-edit-decision {
	@include marginForCenterColumn();

	&__heading {
		margin-bottom: $heading-margin-bottom;

		@include h5();
	}
}
</style>
