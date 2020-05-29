<template>
	<section class="wb-db-bridge">
		<StringDataValue
			:label="targetLabel"
			:data-value="targetValue"
			:set-data-value="setDataValue"
			:maxlength="this.$bridgeConfig.stringMaxLength"
			class="wb-db-bridge__target-value"
		/>
		<ReferenceSection
			class="wb-db-bridge__reference-section"
		/>
		<EditDecision />
	</section>
</template>

<script lang="ts">
import { DataValue } from '@wmde/wikibase-datamodel-types';
import StateMixin from '@/presentation/StateMixin';
import EditDecision from '@/presentation/components/EditDecision.vue';
import Component, { mixins } from 'vue-class-component';
import Term from '@/datamodel/Term';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';

@Component( {
	components: {
		EditDecision,
		StringDataValue,
		ReferenceSection,
	},
} )
export default class DataBridge extends mixins( StateMixin ) {
	public get targetValue(): DataValue {
		const targetValue = this.rootModule.state.targetValue;
		if ( targetValue === null ) {
			throw new Error( 'not yet ready!' );
		}
		return targetValue;
	}

	public get targetLabel(): Term {
		return this.rootModule.getters.targetLabel;
	}

	public setDataValue( dataValue: DataValue ): void {
		this.rootModule.dispatch( 'setTargetValue', dataValue );
	}

}
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
