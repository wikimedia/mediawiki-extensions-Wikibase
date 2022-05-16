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
			:max-length="maxlength"
			:value="value"
			@input="value = $event"
		/>
	</div>
</template>
<script lang="ts">
import { defineComponent, PropType } from 'vue';
import PropertyLabel from '@/presentation/components/PropertyLabel.vue';
import { DataValue } from '@wmde/wikibase-datamodel-types';
import ResizingTextField from '@/presentation/components/ResizingTextField.vue';
import { v4 as uuid } from 'uuid';
import Term from '@/datamodel/Term';

interface StringDataValue {
	dataValue: DataValue | null;
	setDataValue: ( dataValue: DataValue ) => void;
}

export default defineComponent( {
	name: 'StringDataValue',
	props: {
		dataValue: {
			type: Object as PropType<DataValue | null>,
			required: false,
			default: null,
		},
		label: {
			type: Object as PropType<Term>,
			required: true,
		},
		placeholder: {
			required: false,
			type: String,
			default: undefined,
		},
		maxlength: {
			type: Number,
			required: false,
			default: undefined,
		},
		setDataValue: {
			required: true,
			type: Function as PropType<( dataValue: DataValue ) => void>,
		},
	},
	components: { PropertyLabel, ResizingTextField },
	data() {
		return {
			id: uuid(),
		};
	},
	computed: {
		value: {
			get( this: StringDataValue ): string {
				if ( !this.dataValue ) {
					return '';
				} else {
					return this.dataValue.value;
				}
			},
			set( this: StringDataValue, value: string ): void {
				this.setDataValue(
					{
						type: 'string',
						value,
					},
				);
			},
		},
	},
	compatConfig: { MODE: 3 },
} );
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
