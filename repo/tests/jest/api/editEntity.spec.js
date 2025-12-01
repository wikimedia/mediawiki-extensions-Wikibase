const { api } = require( '../../../resources/wikibase.wbui2025/api/api.js' );
const editEntity = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

describe( 'renderPropertyLinkHtml', () => {
	const formattedEntities = {
		P1: '<a>html for P1</a>',
		P2: '<a>html for P2</a>'
	};
	const apiSpy = jest.spyOn( api, 'get' );
	apiSpy.mockResolvedValue( {
		wbformatentities: formattedEntities
	} );

	it( 'calls the api with wbformatentities with an array of property ids', async () => {
		const propertyIds = [ 'P1', 'P2' ];

		await editEntity.renderPropertyLinkHtml( propertyIds );
		expect( apiSpy ).toHaveBeenCalledWith( {
			action: 'wbformatentities',
			generate: 'text/html',
			ids: propertyIds
		} );
	} );

	describe( 'when there are more than 50 ids', () => {
		const propertyIds = Array.from(
			{ length: 105 },
			( _val, index ) => `P${ index + 1 }`
		);

		it( 'makes api calls in batches when there are more than 50 ids', async () => {
			await editEntity.renderPropertyLinkHtml( propertyIds );
			expect( apiSpy ).toHaveBeenCalledTimes( 3 );
			expect( apiSpy ).toHaveBeenNthCalledWith(
				1,
				expect.objectContaining( { action: 'wbformatentities', ids: propertyIds.slice( 0, 50 ) } )
			);
			expect( apiSpy ).toHaveBeenNthCalledWith(
				2,
				expect.objectContaining( { action: 'wbformatentities', ids: propertyIds.slice( 50, 100 ) } )
			);
			expect( apiSpy ).toHaveBeenNthCalledWith(
				3,
				expect.objectContaining( { action: 'wbformatentities', ids: propertyIds.slice( 100, 105 ) } )
			);
		} );

		it( 'collects responses from multiple calls into one object', async () => {
			const firstResponse = {
				P1: '<a>P1</a>',
				P2: '<a>P2</a>'
			};
			const secondResponse = {
				P51: '<a>P51</a>',
				P52: '<a>P52</a>'
			};
			const thirdResponse = {
				P101: '<a>P101</a>'
			};
			apiSpy.mockResolvedValueOnce( { wbformatentities: firstResponse } );
			apiSpy.mockResolvedValueOnce( { wbformatentities: secondResponse } );
			apiSpy.mockResolvedValueOnce( { wbformatentities: thirdResponse } );

			const response = await editEntity.renderPropertyLinkHtml( propertyIds );
			expect( response ).toEqual( {
				P1: '<a>P1</a>',
				P2: '<a>P2</a>',
				P51: '<a>P51</a>',
				P52: '<a>P52</a>',
				P101: '<a>P101</a>'
			} );
		} );
	} );
} );
