<template>
	<div class="wb-db-stringValue">
		<PropertyLabel
			:term="label"
			:html-for="id"
		/>
		<ResizingTextField
			:id="id"
			class="wb-db-stringValue__input"
			:placeholder="placeholder"
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
import Term from '@/datamodel/Term';
import PropertyLabel from '@/presentation/components/PropertyLabel.vue';
import ResizingTextField from '@/presentation/components/ResizingTextField.vue';
import { v4 as uuid } from 'uuid';

@Component( {
	components: { PropertyLabel, ResizingTextField },
} )
export default class StringDataValue extends Vue {
	public readonly id = uuid();

	@Prop( { required: true } )
	public dataValue!: DataValue|null;

	@Prop( { required: true } )
	public label!: Term;

	@Prop( { required: false } )
	public placeholder?: string;

	@Prop( { required: true, type: Function } )
	public setDataValue!: ( dataValue: DataValue ) => void;

	get value() {
		if ( !this.dataValue ) {
			return '';
		} else {
			return this.dataValue.value as string;
		}
	}

	set value( value: string ) {
		this.setDataValue(
			{
				type: 'string',
				value,
			},
		);
	}
}
</script>
<style lang="scss">
.wb-db-stringValue {
	@include marginInputComponent();

	&__input {
		@include transitions();
		@include inputFieldBase();
		@include inputFieldStandalone();
	}
}
</style>
