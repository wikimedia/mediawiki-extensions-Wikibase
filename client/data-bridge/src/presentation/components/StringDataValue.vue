<template>
	<div class="wb-db-stringValue">
		<label
			class="wb-db-stringValue__label"
			:for="id"
		>{{ label }}</label>
		<ResizingTextField
			:id="id"
			class="wb-db-stringValue__input"
			v-model="value"
		/>
	</div>
</template>
<script lang="ts">
import {
	Component,
	Vue,
} from 'vue-property-decorator';
import { Prop } from 'vue-property-decorator';
import DataValue from '@/datamodel/DataValue';
import ResizingTextField from '@/presentation/components/ResizingTextField.vue';
import { v4 as uuid } from 'uuid';

@Component( {
	components: { ResizingTextField },
} )
export default class StringDataValue extends Vue {
	public readonly id = uuid();

	@Prop( { required: true } )
	public dataValue!: DataValue|null;

	@Prop( { required: true } )
	public label!: string;

	get value() {
		if ( !this.dataValue ) {
			return '';
		} else {
			return this.dataValue.value;
		}
	}
}
</script>
