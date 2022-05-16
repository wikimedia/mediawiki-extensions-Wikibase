<template>
	<div class="wb-db-edit-decision">
		<h2 class="wb-db-edit-decision__heading">
			{{ $messages.getText( $messages.KEYS.EDIT_DECISION_HEADING ) }}
		</h2>
		<RadioGroup>
			<RadioInput
				name="editDecision"
				html-value="replace"
				value="editDecision"
				@input="editDecision = $event"
			>
				<template #label>
					<span v-html="$messages.get( $messages.KEYS.EDIT_DECISION_REPLACE_LABEL )" />
				</template>
				<template #description>
					{{ $messages.getText( $messages.KEYS.EDIT_DECISION_REPLACE_DESCRIPTION ) }}
				</template>
			</RadioInput>
			<RadioInput
				name="editDecision"
				html-value="update"
				value="editDecision"
				@input="editDecision = $event"
			>
				<template #label>
					<span v-html="$messages.get( $messages.KEYS.EDIT_DECISION_UPDATE_LABEL )" />
				</template>
				<template #description>
					{{ $messages.getText( $messages.KEYS.EDIT_DECISION_UPDATE_DESCRIPTION ) }}
				</template>
			</RadioInput>
		</RadioGroup>
	</div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { Context } from 'vuex-smart-module';
import StateMixin from '@/presentation/StateMixin';
import EditDecisionOption from '@/definitions/EditDecision';
import RadioGroup from '@/presentation/components/RadioGroup.vue';
import RadioInput from '@/presentation/components/RadioInput.vue';
import { rootModule } from '@/store';

interface EditDecision {
	rootModule: Context<typeof rootModule>;
}

export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'EditDecision',
	components: {
		RadioGroup,
		RadioInput,
	},
	computed: {
		editDecision: {
			get( this: EditDecision ): EditDecisionOption | null {
				return this.rootModule.state.editDecision;
			},
			set( this: EditDecision, value: EditDecisionOption | null ): void {
				if ( value === null ) {
					throw new Error( 'Cannot set editDecision back to null!' );
				}
				this.rootModule.dispatch( 'setEditDecision', value );
			},
		},
	},
	compatConfig: { MODE: 3 },
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
