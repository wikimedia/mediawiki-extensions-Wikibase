import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import Status from '@/definitions/ApplicationStatus';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_ID,
	ENTITY_ONLY_MAIN_STRING_VALUE,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';
import {
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

export const getters: GetterTree<EntityState, Application> = {
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
	[ ENTITY_ONLY_MAIN_STRING_VALUE ]: (
		state: EntityState,
		getters: { [ key: string ]: any }, // eslint-disable-line @typescript-eslint/no-explicit-any
		applicationState: Application,
	) => ( propertyId: string ): string|null => {
		const path = {
			entityId: state.id,
			propertyId,
			index: 0,
		};

		if ( applicationState.applicationStatus !== Status.READY ) {
			return null;
		}

		if ( getters[
			namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS )
		]( state.id, propertyId ) === false
		) {
			throw new Error( 'no statement for property' );
		}

		if ( getters[
			namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS )
		]( state.id, propertyId ) === true
		) {
			throw new Error( 'ambiguous statement' );
		}

		if ( getters[
			namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.snakType )
		]( path ) !== 'value'
		) {
			throw new Error( 'unsupported snak type' );
		}

		if ( getters[
			namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
		]( path ) !== 'string'
		) {
			throw new Error( 'unsupported data value type' );
		}

		return getters[
			namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.dataValue )
		]( path ).value;
	},
};
