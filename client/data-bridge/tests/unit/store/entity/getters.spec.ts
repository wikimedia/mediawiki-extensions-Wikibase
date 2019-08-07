import { getters } from '@/store/entity/getters';
import newApplicationState from '../newApplicationState';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import newEntityState from './newEntityState';
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

describe( 'entity/Getters', () => {
	it( 'has an id', () => {
		expect( getters[ ENTITY_ID ](
			newEntityState( { id: 'Q123' } ), null, newApplicationState(), null,
		) ).toBe( 'Q123' );
	} );
	it( 'has a baseRevision id', () => {
		expect( getters[ ENTITY_REVISION ](
			newEntityState( { baseRevision: 23 } ), null, newApplicationState(), null,
		) ).toBe( 23 );
	} );

	describe( ENTITY_ONLY_MAIN_STRING_VALUE, () => {
		it( 'returns null if the application is not ready', () => {
			const scopedGetter = jest.fn();
			expect( getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
				newEntityState( {} ),
				scopedGetter,
				newApplicationState( { applicationStatus: ApplicationStatus.INITIALIZING } ),
				jest.fn(),
			)( 'P23' ) ).toBeNull();
			expect( scopedGetter ).toBeCalledTimes( 0 );

			expect( getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
				newEntityState( {} ),
				scopedGetter,
				newApplicationState( { applicationStatus: ApplicationStatus.ERROR } ),
				jest.fn(),
			)( 'P23' ) ).toBeNull();
			expect( scopedGetter ).toBeCalledTimes( 0 );

		} );

		it( 'throws error for missing statements', () => {
			const entityId = 'Q42';
			const namespacedEvent = namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS );
			const scopedGetter = jest.fn( () => {
				return false;
			} );
			const id = 'P23';
			const moduleGetters = { [ namespacedEvent ]: scopedGetter };

			function apply(): void {
				getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
					newEntityState( {
						id: entityId,
					} ),
					moduleGetters,
					newApplicationState( { applicationStatus: ApplicationStatus.READY } ),
					jest.fn(),
				)( 'P23' );
			}

			expect( apply ).toThrow();
			expect( scopedGetter ).toBeCalledTimes( 1 );
			expect( scopedGetter ).toBeCalledWith( entityId, id );
		} );

		it( 'throws error for ambiguous statements', () => {
			const entityId = 'Q42';
			const namespacedEvents = [
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS ),
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS ),
			];
			const scopedGetter = jest.fn( () => {
				return true;
			} );

			const id = 'P23';
			const moduleGetters = {
				[ namespacedEvents[ 0 ] ]: scopedGetter,
				[ namespacedEvents[ 1 ] ]: scopedGetter,
			};

			function apply(): void {
				getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
					newEntityState( {
						id: entityId,
					} ),
					moduleGetters,
					newApplicationState( { applicationStatus: ApplicationStatus.READY } ),
					jest.fn(),
				)( 'P23' );
			}

			expect( apply ).toThrow();
			expect( scopedGetter ).toBeCalledTimes( 2 );
			expect( scopedGetter ).toBeCalledWith( entityId, id );
		} );

		it( 'throws error for not value snak types', () => {
			const entityId = 'Q42';
			const namespacedEvents = [
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS ),
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS ),
				namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.snakType ),
			];

			const scopedTrueeGetter = jest.fn( () => {
				return true;
			} );

			const scopedFalseGetter = jest.fn( () => {
				return false;
			} );

			const scopedSnakTypeGetter = jest.fn( () => {
				return 'novalue';
			} );

			const id = 'P23';
			const moduleGetters = {
				[ namespacedEvents[ 0 ] ]: scopedTrueeGetter,
				[ namespacedEvents[ 1 ] ]: scopedFalseGetter,
				[ namespacedEvents[ 2 ] ]: scopedSnakTypeGetter,
			};

			function apply(): void {
				getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
					newEntityState( {
						id: entityId,
					} ),
					moduleGetters,
					newApplicationState( { applicationStatus: ApplicationStatus.READY } ),
					jest.fn(),
				)( 'P23' );
			}

			expect( apply ).toThrow();
			expect( scopedTrueeGetter ).toHaveBeenNthCalledWith( 1, entityId, id );
			expect( scopedFalseGetter ).toHaveBeenNthCalledWith( 1, entityId, id );
			expect( scopedSnakTypeGetter ).toHaveBeenNthCalledWith( 1, { entityId, propertyId: id, index: 0 } );

		} );

		it( 'throws error for non string data types', () => {
			const entityId = 'Q42';
			const namespacedEvents = [
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS ),
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS ),
				namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.snakType ),
				namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.dataValueType ),
			];

			const scopedTrueeGetter = jest.fn( () => {
				return true;
			} );

			const scopedFalseGetter = jest.fn( () => {
				return false;
			} );

			const scopedSnakTypeGetter = jest.fn( () => {
				return 'value';
			} );

			const scopedDataValueTypeGetter = jest.fn( () => {
				return 'invalid';
			} );

			const id = 'P23';
			const moduleGetters = {
				[ namespacedEvents[ 0 ] ]: scopedTrueeGetter,
				[ namespacedEvents[ 1 ] ]: scopedFalseGetter,
				[ namespacedEvents[ 2 ] ]: scopedSnakTypeGetter,
				[ namespacedEvents[ 3 ] ]: scopedDataValueTypeGetter,
			};

			function apply(): void {
				getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
					newEntityState( {
						id: entityId,
					} ),
					moduleGetters,
					newApplicationState( { applicationStatus: ApplicationStatus.READY } ),
					jest.fn(),
				)( 'P23' );
			}

			expect( apply ).toThrow();
			expect( scopedTrueeGetter ).toHaveBeenNthCalledWith( 1, entityId, id );
			expect( scopedFalseGetter ).toHaveBeenNthCalledWith( 1, entityId, id );
			expect( scopedSnakTypeGetter ).toHaveBeenNthCalledWith( 1, { entityId, propertyId: id, index: 0 } );
			expect( scopedDataValueTypeGetter ).toHaveBeenNthCalledWith( 1, { entityId, propertyId: id, index: 0 } );
		} );

		it( 'returns value for only string statement', () => {
			const entityId = 'Q42';
			const namespacedEvents = [
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS ),
				namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS ),
				namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.snakType ),
				namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.dataValueType ),
				namespacedStoreEvent( NS_STATEMENTS, mainSnakGetterTypes.dataValue ),
			];

			const scopedTrueeGetter = jest.fn( () => {
				return true;
			} );

			const scopedFalseGetter = jest.fn( () => {
				return false;
			} );

			const scopedSnakTypeGetter = jest.fn( () => {
				return 'value';
			} );

			const scopedDataValueTypeGetter = jest.fn( () => {
				return 'string';
			} );

			const value = 'finally';
			const scopedDataValueGetter = jest.fn( () => {
				return { value };
			} );

			const id = 'P23';
			const moduleGetters = {
				[ namespacedEvents[ 0 ] ]: scopedTrueeGetter,
				[ namespacedEvents[ 1 ] ]: scopedFalseGetter,
				[ namespacedEvents[ 2 ] ]: scopedSnakTypeGetter,
				[ namespacedEvents[ 3 ] ]: scopedDataValueTypeGetter,
				[ namespacedEvents[ 4 ] ]: scopedDataValueGetter,
			};

			expect( getters[ ENTITY_ONLY_MAIN_STRING_VALUE ](
				newEntityState( {
					id: entityId,
				} ),
				moduleGetters,
				newApplicationState( { applicationStatus: ApplicationStatus.READY } ),
				jest.fn(),
			)( 'P23' ) ).toBe( value );

			expect( scopedTrueeGetter ).toHaveBeenNthCalledWith( 1, entityId, id );
			expect( scopedFalseGetter ).toHaveBeenNthCalledWith( 1, entityId, id );
			expect( scopedSnakTypeGetter ).toHaveBeenNthCalledWith( 1, { entityId, propertyId: id, index: 0 } );
			expect( scopedDataValueTypeGetter ).toHaveBeenNthCalledWith( 1, { entityId, propertyId: id, index: 0 } );
			expect( scopedDataValueGetter ).toHaveBeenNthCalledWith( 1, { entityId, propertyId: id, index: 0 } );
		} );
	} );
} );
