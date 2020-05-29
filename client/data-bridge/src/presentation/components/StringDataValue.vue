<template>
	<div class="wb-db-string-value">
		<PropertyLabel
			:term="label"
			:html-for="id"
		/>
		<ResizingTextField
			:id="id"
			class="wb-db-string-value__input"
			:placeholder="placeholder"
			:maxlength="maxlength"
			v-model="value"
		/>
	</div>
</template>
<script lang="ts">
import Term from '@/datamodel/Term';
import PropertyLabel from '@/presentation/components/PropertyLabel.vue';
import { DataValue } from '@wmde/wikibase-datamodel-types';
import { ResizingTextField } from '@wmde/wikibase-vuejs-components';
import { v4 as uuid } from 'uuid';
import {
	Component,
	Prop,
	Vue,
} from 'vue-property-decorator';

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

	@Prop( { type: Number, required: false } )
	public maxlength?: number;

	@Prop( { required: true, type: Function } )
	public setDataValue!: ( dataValue: DataValue ) => void;

	public get value(): string {
		if ( !this.dataValue ) {
			return '';
		} else {
			return this.dataValue.value as string;
		}
	}

	public set value( value: string ) {
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
.wb-db-string-value {
	@include marginForCenterColumn();

	&__input {
		@include transitions();
		@include inputFieldBase();
		@include inputFieldStandalone();
	}
}
</style>
