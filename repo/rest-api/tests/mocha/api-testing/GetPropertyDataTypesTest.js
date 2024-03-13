'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { RequestBuilder } = require( '../helpers/RequestBuilder' );

describe( 'GET /property-data-types', () => {

	it( 'can GET the value types of each data type', async () => {
		const dataTypesToValueTypesMap = {
			commonsMedia: 'string',
			'geo-shape': 'string',
			'tabular-data': 'string',
			url: 'string',
			'external-id': 'string',
			'wikibase-item': 'wikibase-entityid',
			'wikibase-property': 'wikibase-entityid',
			'globe-coordinate': 'globecoordinate',
			monolingualtext: 'monolingualtext',
			quantity: 'quantity',
			string: 'string',
			time: 'time'
		};

		const response = await new RequestBuilder()
			.withRoute( 'GET', '/property-data-types' )
			.makeRequest();

		expect( response ).to.have.status( 200 );
		Object.entries( dataTypesToValueTypesMap ).forEach( ( [ dataType, value ] ) => {
			expect( response.body ).to.have.property( dataType );
			expect( response.body[ dataType ] ).to.equal( value );
		} );
	} );
} );
