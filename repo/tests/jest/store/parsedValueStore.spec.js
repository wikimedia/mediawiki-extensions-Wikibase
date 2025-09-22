jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( { parseValue: jest.fn() } )
);

const { setActivePinia, createPinia } = require( 'pinia' );
const { useParsedValueStore } = require( '../../../resources/wikibase.wbui2025/store/parsedValueStore.js' );
const { parseValue: mockedParseValue } = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

describe( 'parsed value store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	it( 'sends one parseValue request with the right parameters', async () => {
		const parsedValueStore = useParsedValueStore();
		mockedParseValue.mockResolvedValueOnce( { type: 'string', value: 'abc' } );

		const parsedValue1 = await parsedValueStore.getParsedValue( 'P123', ' abc ' );
		expect( parsedValue1 ).toEqual( { type: 'string', value: 'abc' } );
		expect( mockedParseValue ).toHaveBeenCalledWith( 'P123', ' abc ' );

		const parsedValue2 = await parsedValueStore.getParsedValue( 'P123', ' abc ' );
		expect( parsedValue2 ).toEqual( { type: 'string', value: 'abc' } );
		expect( mockedParseValue ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'sends separate requests for different properties and values', async () => {
		const parsedValueStore = useParsedValueStore();
		mockedParseValue.mockImplementation( ( propertyId, value ) => Promise.resolve( {
			type: 'string',
			value: `parsed ${ propertyId }:${ value }`
		} ) );

		const parsedValue1 = await parsedValueStore.getParsedValue( 'P123', 'abc' );
		const parsedValue2 = await parsedValueStore.getParsedValue( 'P456', 'def' );
		const parsedValue3 = await parsedValueStore.getParsedValue( 'P123', 'def' );

		expect( mockedParseValue ).toHaveBeenCalledTimes( 3 );
		expect( parsedValue1 ).toEqual( { type: 'string', value: 'parsed P123:abc' } );
		expect( parsedValue2 ).toEqual( { type: 'string', value: 'parsed P456:def' } );
		expect( parsedValue3 ).toEqual( { type: 'string', value: 'parsed P123:def' } );
	} );

} );
