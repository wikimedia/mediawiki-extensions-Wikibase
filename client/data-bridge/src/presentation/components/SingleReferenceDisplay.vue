<template>
	<span class="wb-db-reference">
		<span v-for="(value, index) in snaks()" :key="index">
			<span v-if="index > 0">{{ separator }}</span><span>{{ value }}</span>
		</span>
	</span>
</template>

<script lang="ts">
import Vue from 'vue';
import Component from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import Reference from '@/datamodel/Reference';
import Snak from '@/datamodel/Snak';
import DataValue from '@/datamodel/DataValue';

@Component
export default class SingleReferenceDisplay extends Vue {
	@Prop( { required: true } )
	public reference!: Reference;

	@Prop( { required: false, type: String, default: '. ' } )
	public separator!: string;

	public snaks(): string[] {
		function flatten( array: ( null|string )[][] ): string[] {
			const flatArray: string[] = [];
			for ( const elements of array ) {
				for ( const element of elements ) {
					if ( element !== null ) {
						flatArray.push( element );
					}
				}
			}
			return flatArray;
		}

		function isValueSnak( snak: Snak ): snak is Snak & { datavalue: DataValue } {
			return snak.snaktype === 'value';
		}

		return flatten( this.reference[ 'snaks-order' ].map(
			( propertyId: string ) => {
				return this.reference.snaks[ propertyId ].map( ( snak: Snak ) => {
					if ( !isValueSnak( snak ) ) {
						// TODO: handle novalue and somevalue
						return null;
					}
					const datavalueValue = snak.datavalue.value;
					if ( typeof datavalueValue === 'object' ) {
						return JSON.stringify( datavalueValue );
					}
					return datavalueValue;
				} );
			},
		) );
	}
}
</script>

<style scoped></style>
