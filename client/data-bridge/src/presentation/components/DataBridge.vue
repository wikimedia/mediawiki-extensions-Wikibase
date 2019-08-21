<template>
	<section class="wb-db-bridge">
		<DataPlaceholder :target-value="targetValue.value" />
	</section>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Getter } from 'vuex-class';
import DataValue from '@/datamodel/DataValue';
import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';

@Component( {
	components: {
		DataPlaceholder,
	},
} )
export default class DataBridge extends Vue {
	@Getter( 'targetValue' )
	public targetValue!: DataValue;
}
</script>
