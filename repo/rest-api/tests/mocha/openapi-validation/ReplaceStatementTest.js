'use strict';

const { RequestBuilder } = require( '../helpers/RequestBuilder' );
const { createUniqueStringProperty, createEntity } = require( '../helpers/entityHelper' );
const { utils } = require( 'api-testing' );
const expect = require( 'chai' ).expect;

function newStatementWithRandomStringValue( property ) {
	return {
		mainsnak: {
			snaktype: 'value',
			datavalue: {
				type: 'string',
				value: 'random-string-value-' + utils.uniq()
			},
			property
		},
		type: 'statement'
	};
}

function newReplaceStatementRequestBuilder( statementId, statement ) {
	return new RequestBuilder()
		.withRoute( 'PUT', '/statements/{statement_id}' )
		.withPathParam( 'statement_id', statementId )
		.withJsonBodyParam( 'statement', statement );
}

describe( 'validate PUT /statements/{statement_id}', () => {

	let itemId;
	let stringPropertyId;
	let statementId;

	before( async () => {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createEntity( 'item', {
			claims: [ newStatementWithRandomStringValue( stringPropertyId ) ]
		} );
		itemId = createEntityResponse.entity.id;
		statementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	it( '200', async () => {
		const response = await newReplaceStatementRequestBuilder(
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		).makeRequest();

		expect( response.status ).to.equal( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid statement serialization', async () => {
		const response = await newReplaceStatementRequestBuilder(
			statementId,
			{ invalid: 'statement' }
		).makeRequest();

		expect( response.status ).to.equal( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - statement does not exist', async () => {
		const response = await newReplaceStatementRequestBuilder(
			`${itemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
			newStatementWithRandomStringValue( stringPropertyId )
		).makeRequest();

		expect( response.status ).to.equal( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newReplaceStatementRequestBuilder(
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		)
			.withHeader( 'If-Unmodified-Since', yesterday )
			.makeRequest();

		expect( response.status ).to.equal( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '415 - unsupported media type', async () => {
		const response = await newReplaceStatementRequestBuilder(
			statementId,
			newStatementWithRandomStringValue( stringPropertyId )
		)
			.withHeader( 'Content-Type', 'text/plain' )
			.makeRequest();

		expect( response.status ).to.equal( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
