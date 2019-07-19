import { GetterTree } from 'vuex';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_ID,
	ENTITY_ONLY_MAIN_STRING_VALUE,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';

export const getters: GetterTree<EntityState, any> = {
	[ ENTITY_ID ]( state: EntityState ): string {
		return state.id;
	},

	[ ENTITY_REVISION ]( state: EntityState ): number {
		return state.baseRevision;
	},

	/**
	 * Get the main string value of the only statement for this property ID.
	 * Returns null if the statements have not yet been initialized,
	 * throws an error in all other unsupported cases
	 * (more than one statement, unknown/no value, etc.).
	 */
	[ ENTITY_ONLY_MAIN_STRING_VALUE ]: ( state: EntityState ) => ( propertyId: string ): string|null => {
		const statements = state.statements;
		if ( statements === null ) {
			return null;
		}
		if ( !( propertyId in statements ) ) {
			throw new Error( 'no statement for property' );
		}
		const statementGroup = statements[ propertyId ];
		if ( statementGroup.length !== 1 ) {
			throw new Error( 'ambiguous statement' );
		}
		const statement = statements[ propertyId ][ 0 ],
			snak = statement.mainsnak;
		if ( snak.snaktype !== 'value' ) {
			throw new Error( 'unsupported snak type' );
		}
		const datavalue = snak.datavalue!; // type guard instead of assertion would be neat
		if ( datavalue.type !== 'string' ) {
			throw new Error( 'unsupported data value type' );
		}
		return datavalue.value as string; // type guard instead of cast would be neat
	},
};
