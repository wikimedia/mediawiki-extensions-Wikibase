<template>
	<div class="wb-db-edit-decision">
		<h2 class="wb-db-edit-decision__heading">
			{{ $messages.get( $messages.KEYS.EDIT_DECISION_HEADING ) }}
		</h2>
		<RadioGroup>
			<RadioInput name="editDecision" html-value="replace" v-model="editDecision">
				<template slot="label">
					<span v-html="$messages.get( $messages.KEYS.EDIT_DECISION_REPLACE_LABEL )" />
				</template>
				<template slot="description">
					{{ $messages.get( $messages.KEYS.EDIT_DECISION_REPLACE_DESCRIPTION ) }}
				</template>
			</RadioInput>
			<RadioInput name="editDecision" html-value="update" v-model="editDecision">
				<template slot="label">
					<span v-html="$messages.get( $messages.KEYS.EDIT_DECISION_UPDATE_LABEL )" />
				</template>
				<template slot="description">
					{{ $messages.get( $messages.KEYS.EDIT_DECISION_UPDATE_DESCRIPTION ) }}
				</template>
			</RadioInput>
		</RadioGroup>
	</div>
</template>

<script lang="ts">
import EditDecisionOption from '@/definitions/EditDecision';
import StateMixin from '@/presentation/StateMixin';
import RadioGroup from '@/presentation/components/RadioGroup.vue';
import { BRIDGE_SET_EDIT_DECISION } from '@/store/actionTypes';
import { RadioInput } from '@wmde/wikibase-vuejs-components';
import Component, { mixins } from 'vue-class-component';

@Component( {
	components: {
		RadioGroup,
		RadioInput,
	},
} )
export default class EditDecision extends mixins( StateMixin ) {

	public get editDecision(): EditDecisionOption {
		return this.$store.state.editDecision;
	}

	public set editDecision( value: EditDecisionOption ) {
		this.rootModule.dispatch( BRIDGE_SET_EDIT_DECISION, value );
	}

}
</script>

<style lang="scss">
.wb-db-edit-decision {
	margin: 0 $margin-center-column-side;

	&__heading {
		margin: px-to-em( 18px ) 0 px-to-em( 10px ) 0;
		font-weight: 600;
		font-size: 1.1em;
	}

	strong {
		font-weight: bold;
	}
}
</style>
