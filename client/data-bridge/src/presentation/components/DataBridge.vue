<template>
	<section class="wb-db-bridge">
		<StringDataValue
			:label="targetLabel"
			:data-value="targetValue"
			:set-data-value="setDataValue"
			:maxlength="valueMaxLength"
			class="wb-db-bridge__target-value"
		/>
		<ReferenceSection
			class="wb-db-bridge__reference-section"
		/>
		<EditDecision />
	</section>
</template>

<script lang="ts">
import { defineComponent } from 'vue';
import { DataValue } from '@wmde/wikibase-datamodel-types';
import StateMixin from '@/presentation/StateMixin';
import EditDecision from '@/presentation/components/EditDecision.vue';
import Term from '@/datamodel/Term';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';

export default defineComponent( {
	mixins: [ StateMixin ],
	name: 'DataBridge',
	components: {
		EditDecision,
		StringDataValue,
		ReferenceSection,
	},
	computed: {
		targetValue(): DataValue {
			const targetValue = this.rootModule.state.targetValue;
			if ( targetValue === null ) {
				throw new Error( 'not yet ready!' );
			}
			return targetValue;
		},
		targetLabel(): Term {
			return this.rootModule.getters.targetLabel;
		},
		valueMaxLength(): number | null {
			return this.rootModule.getters.config.stringMaxLength;
		},
	},
	methods: {
		setDataValue( dataValue: DataValue ): void {
			this.rootModule.dispatch( 'setTargetValue', dataValue );
		},
	},
	compatConfig: { MODE: 3 },
} );
</script>

<style lang="scss">
.wb-db-bridge {
	padding: $padding-panel-form;

	.wb-db-bridge__target-value {
		margin-bottom: 2 * $base-spacing-unit-fixed;
	}

	.wb-db-bridge__reference-section {
		margin-bottom: 3 * $base-spacing-unit-fixed;
	}
}
</style>
