'use strict';

jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( {
		renderSnakValueText: jest.fn(),
		renderSnakValueHtml: jest.fn( () => Promise.resolve( '' ) ),
		parseValue: jest.fn( () => Promise.resolve( {} ) )
	} )
);

jest.mock(
	'../../../resources/wikibase.wbui2025/api/commons.js',
	() => ( {} )
);

const { renderSnakValueText } =
	require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

const {
	GlobeCoordinateValueStrategy
} = require( '../../../resources/wikibase.wbui2025/store/snakValueStrategies.js' );

describe( 'GlobeCoordinateValueStrategy', () => {
	it( 'renderValueForTextInput delegates to renderSnakValueText', async () => {
		renderSnakValueText.mockResolvedValueOnce( 'formatted coordinate' );

		const fakeStore = { property: 'P625', precision: 'auto', textvalue: '' };
		const strategy = new GlobeCoordinateValueStrategy( fakeStore );

		const dataValue = {
			type: 'globecoordinate',
			value: { latitude: 46, longitude: 14 }
		};

		const out = await strategy.renderValueForTextInput( dataValue );

		expect( renderSnakValueText ).toHaveBeenCalledWith( dataValue );
		expect( out ).toBe( 'formatted coordinate' );
	} );
} );
