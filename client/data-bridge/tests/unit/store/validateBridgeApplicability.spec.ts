import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { STATEMENTS_IS_AMBIGUOUS } from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import validateBridgeApplicability from '@/store/validateBridgeApplicability';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import { ENTITY_ID } from '@/store/entity/getterTypes';

function mockedStore(
	gettersOverride?: any,
	entityId: string = 'Q815',
	targetProperty: string = 'P4711',
): any {
	return newMockStore( {
		state: {
			targetProperty,
		},
		getters: {
			...{
				get [ getter( NS_ENTITY, ENTITY_ID ) ]() {
					return entityId;
				},
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					STATEMENTS_IS_AMBIGUOUS,
				) ]: jest.fn( () => false ),
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.snakType,
				) ]: jest.fn( () => 'value' ),
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.dataValueType,
				) ]: jest.fn( () => 'string' ),
			}, ...gettersOverride,
		},
	} );
}

describe( 'validateBridgeApplicability', () => {

	it( 'returns true if applicable', () => {
		const targetProperty = 'P4711';
		const entityId = 'Q815';
		const context = mockedStore( {}, entityId, targetProperty );

		expect( validateBridgeApplicability( context ) )
			.toBe( true );
		expect(
			context.getters[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				STATEMENTS_IS_AMBIGUOUS,
			) ],
		).toHaveBeenCalledWith( entityId, targetProperty );
		expect(
			context.getters[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.snakType,
			) ],
		).toHaveBeenCalledWith( {
			entityId,
			propertyId: targetProperty,
			index: 0,
		} );
		expect(
			context.getters[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.dataValueType,
			) ],
		).toHaveBeenCalledWith( {
			entityId,
			propertyId: targetProperty,
			index: 0,
		} );
	} );

	it( 'returns false on ambiguous statements', () => {
		const context = mockedStore( {
			[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				STATEMENTS_IS_AMBIGUOUS,
			) ]: jest.fn( () => true ),
		} );

		expect( validateBridgeApplicability( context ) )
			.toBe( false );
	} );

	it( 'returns false for non-value snak types', () => {
		const context = mockedStore( {
			[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.snakType,
			) ]: jest.fn( () => 'novalue' ),
		} );

		expect( validateBridgeApplicability( context ) )
			.toBe( false );
	} );

	it( 'returns false for non-string data types', () => {
		const context = mockedStore( {
			[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.dataValueType,
			) ]: jest.fn( () => 'noStringType' ),
		} );

		expect( validateBridgeApplicability( context ) )
			.toBe( false );
	} );
} );
