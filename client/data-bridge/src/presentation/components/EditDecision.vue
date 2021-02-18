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
import EditDecisionOption from '@/definitions/EditDecision';
import StateMixin from '@/presentation/StateMixin';
import RadioGroup from '@/presentation/components/RadioGroup.vue';
import { RadioInput } from '@wmde/wikibase-vuejs-components';
import Component, { mixins } from 'vue-class-component';

@Component( {
	components: {
		RadioGroup,
		RadioInput,
	},
} )
export default class EditDecision extends mixins( StateMixin ) {

	public get editDecision(): EditDecisionOption|null {
		return this.rootModule.state.editDecision;
	}

	public set editDecision( value: EditDecisionOption|null ) {
		if ( value === null ) {
			throw new Error( 'Cannot set editDecision back to null!' );
		}
		this.rootModule.dispatch( 'setEditDecision', value );
	}

}
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
