'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddItemAliasesRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newAddItemAliasesRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let originalLastModified;
	let originalRevisionId;

	function assertValidResponse( response, aliases ) {
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		assert.deepEqual( response.body, aliases );
	}

	function assertValid200Response( response, aliases ) {
		expect( response ).to.have.status( 200 );
		assertValidResponse( response, aliases );
	}

	function assertValid201Response( response, aliases ) {
		expect( response ).to.have.status( 201 );
		assertValidResponse( response, aliases );
	}

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			labels: { en: { language: 'en', value: `english label ${utils.uniq()}` } },
			aliases: { en: [
				{ language: 'en', value: 'first english alias' },
				{ language: 'en', value: 'second english alias' }
			] }
		} );
		testItemId = createEntityResponse.entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifying aliases to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '20x success response ', () => {
		it( 'can create a new list of aliases with edit metadata omitted', async () => {
			const newAliases = [ 'first new alias', 'second new alias' ];
			const response = await newAddItemAliasesRequestBuilder( testItemId, 'de', newAliases )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, newAliases );
		} );

		it( 'can add to an existing list of aliases with edit metadata omitted', async () => {
			const response = await newAddItemAliasesRequestBuilder( testItemId, 'en', [ 'next english alias' ] )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response(
				response,
				[ 'first english alias', 'second english alias', 'next english alias' ]
			);
		} );
	} );
} );
