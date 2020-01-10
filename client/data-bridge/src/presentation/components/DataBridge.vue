<template>
	<section class="wb-db-bridge">
		<StringDataValue
			:label="targetLabel"
			:data-value="targetValue"
			:set-data-value="setDataValue"
			:maxlength="this.$bridgeConfig.stringMaxLength"
		/>
		<ReferenceSection />
		<EditDecision />
	</section>
</template>

<script lang="ts">
import EditDecision from '@/presentation/components/EditDecision.vue';
import Vue from 'vue';
import Component from 'vue-class-component';
import { Action, Getter, State } from 'vuex-class';
import DataValue from '@/datamodel/DataValue';
import Term from '@/datamodel/Term';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import { BRIDGE_SET_TARGET_VALUE } from '@/store/actionTypes';

@Component( {
	components: {
		EditDecision,
		StringDataValue,
		ReferenceSection,
	},
} )
export default class DataBridge extends Vue {
	@Getter( 'targetValue' )
	public targetValue!: DataValue;

	@State( 'targetProperty' )
	public targetProperty!: string;

	@Getter( 'targetLabel' )
	public targetLabel!: Term;

	@Action( BRIDGE_SET_TARGET_VALUE )
	public setDataValue!: ( dataValue: DataValue ) => void;
}
</script>

<style lang="scss">
.wb-db-bridge {
	padding: $padding-panel-form;
}
</style>
