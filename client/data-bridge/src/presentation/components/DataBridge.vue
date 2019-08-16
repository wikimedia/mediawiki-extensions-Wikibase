<template>
	<section class="wb-db-bridge">
		<StringDataValue
			:label="targetProperty"
			:data-value="targetValue"
			:set-data-value="setDataValue"
		/>
	</section>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Action, Getter } from 'vuex-class';
import DataValue from '@/datamodel/DataValue';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import { BRIDGE_SET_TARGET_VALUE } from '@/store/actionTypes';

@Component( {
	components: {
		StringDataValue,
	},
} )
export default class DataBridge extends Vue {
	@Getter( 'targetValue' )
	public targetValue!: DataValue;

	@Getter( 'targetProperty' )
	public targetProperty!: string;

	@Action( BRIDGE_SET_TARGET_VALUE )
	public setDataValue!: ( dataValue: DataValue ) => void;
}
</script>
