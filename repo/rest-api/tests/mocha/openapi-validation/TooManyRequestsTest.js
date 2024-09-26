'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const {
	getItemEditRequests,
	getPropertyEditRequests,
	getItemCreateRequest
} = require( '../helpers/happyPathRequestBuilders' );

describe( 'Too Many Requests', () => {
	before( async () => {
		// ensure one temp user is created
		await newCreateItemRequestBuilder( { labels: { en: `en-label-${utils.uniq()}` } } )
			.withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( { enabled: true } ) )
			.withHeader( 'X-Wikibase-CI-Temp-Account-Limit-One', true )
			.makeRequest();
	} );

	describeWithTestData( 'Temp User Creation Limit', ( itemRequestInputs, propertyRequestInputs ) => {
		[
			...getItemEditRequests( itemRequestInputs ),
			...getPropertyEditRequests( propertyRequestInputs ),
			getItemCreateRequest( itemRequestInputs )
		].forEach( ( { newRequestBuilder } ) => {
			it( `${newRequestBuilder().getRouteDescription()} responds with a valid 429 response`, async () => {
				const response = await newRequestBuilder()
					.withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( { enabled: true } ) )
					.withHeader( 'X-Wikibase-CI-Temp-Account-Limit-One', true )
					.makeRequest();

				expect( response ).to.have.status( 429 );
				expect( response ).to.satisfyApiSpec;
			} );
		} );
	} );
} );
