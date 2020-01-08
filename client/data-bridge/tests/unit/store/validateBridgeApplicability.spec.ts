import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { STATEMENTS_IS_AMBIGUOUS } from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import validateBridgeApplicability from '@/store/validateBridgeApplicability';
import newMockStore from '@wmde/vuex-helpers/dist/newMockStore';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';

const defaultEntity = 'Q815';
const defaultProperty = 'P4711';

function mockedStore(
	gettersOverride?: any,
	targetProperty: string = defaultProperty,
): any {
	return newMockStore( {
		state: {
			targetProperty,
		},
		getters: {
			...{
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					STATEMENTS_IS_AMBIGUOUS,
				) ]: jest.fn().mockReturnValue( false ),
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.snakType,
				) ]: jest.fn().mockReturnValue( 'value' ),
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.dataValueType,
				) ]: jest.fn().mockReturnValue( 'string' ),
			}, ...gettersOverride,
		},
	} );
}

describe( 'validateBridgeApplicability', () => {

	it( 'returns true if applicable', () => {
		const context = mockedStore( {}, defaultProperty );

		expect( validateBridgeApplicability(
			context,
			{
				entityId: defaultEntity,
				propertyId: defaultProperty,
				index: 0,
			},
		) ).toBe( true );
		expect(
			context.getters[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				STATEMENTS_IS_AMBIGUOUS,
			) ],
		).toHaveBeenCalledWith( defaultEntity, defaultProperty );
		expect(
			context.getters[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.snakType,
			) ],
		).toHaveBeenCalledWith( {
			entityId: defaultEntity,
			propertyId: defaultProperty,
			index: 0,
		} );
		expect(
			context.getters[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.dataValueType,
			) ],
		).toHaveBeenCalledWith( {
			entityId: defaultEntity,
			propertyId: defaultProperty,
			index: 0,
		} );
	} );

	it( 'returns false on ambiguous statements', () => {
		const context = mockedStore( {
			[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				STATEMENTS_IS_AMBIGUOUS,
			) ]: jest.fn().mockReturnValue( true ),
		} );

		expect( validateBridgeApplicability(
			context,
			{ entityId: defaultEntity, propertyId: defaultProperty, index: 0 },
		) ).toBe( false );
	} );

	it( 'returns false for non-value snak types', () => {
		const context = mockedStore( {
			[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.snakType,
			) ]: jest.fn().mockReturnValue( 'novalue' ),
		} );

		expect( validateBridgeApplicability( context,
			{ entityId: defaultEntity, propertyId: defaultProperty, index: 0 } ) ).toBe( false );
	} );

	it( 'returns false for non-string data types', () => {
		const context = mockedStore( {
			[ getter(
				NS_ENTITY,
				NS_STATEMENTS,
				mainSnakGetterTypes.dataValueType,
			) ]: jest.fn().mockReturnValue( 'noStringType' ),
		} );

		expect( validateBridgeApplicability(
			context,
			{ entityId: defaultEntity, propertyId: defaultProperty, index: 0 },
		) ).toBe( false );
	} );
} );
