'use strict';

const { assert } = require( 'api-testing' );
const rbf = require( '../helpers/RequestBuilderFactory' );
const {
	getLatestEditMetadata,
	newStatementWithRandomStringValue,
	createUniqueStringProperty,
	createItemWithStatements
} = require( '../helpers/entityHelper' );
const hasJsonDiffLib = require( '../helpers/hasJsonDiffLib' );
const { newAddItemStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );

function makeEtag( ...revisionIds ) {
	return revisionIds.map( ( revId ) => `"${revId}"` ).join( ',' );
}

function assertValid304Response( response, revisionId ) {
	assert.equal( response.status, 304 );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
	assert.equal( response.text, '' );
}

function assertValid200Response( response, revisionId, lastModified ) {
	assert.equal( response.status, 200 );
	assert.equal( response.header[ 'last-modified' ], lastModified );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
}

describe( 'Conditional requests', () => {

	let itemId;
	let statementId;
	let statementPropertyId;
	let latestRevisionId;
	let lastModifiedDate;

	before( async () => {
		statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createItemResponse = await createItemWithStatements( [
			newStatementWithRandomStringValue( statementPropertyId )
		] );
		itemId = createItemResponse.entity.id;
		statementId = createItemResponse.entity.claims[ statementPropertyId ][ 0 ].id;

		const testItemCreationMetadata = await getLatestEditMetadata( itemId );
		lastModifiedDate = testItemCreationMetadata.timestamp;
		latestRevisionId = testItemCreationMetadata.revid;
	} );

	[
		() => rbf.newGetItemRequestBuilder( itemId ),
		() => rbf.newGetItemStatementsRequestBuilder( itemId ),
		() => rbf.newGetItemStatementsRequestBuilder( itemId, statementId ),
		() => rbf.newGetStatementRequestBuilder( statementId )
	].forEach( ( newRequestBuilder ) => {
		describe( `If-None-Match - ${newRequestBuilder().getRouteDescription()}`, () => {
			describe( '200 response', () => {
				it( 'if the current item revision is newer than the ID provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision is newer than any of the IDs provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1, latestRevisionId - 2 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the provided ETag is not a valid revision ID', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '"foo"' )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if all the provided ETags are not valid revision IDs', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision is newer than any IDs provided (while others are invalid IDs)',
					async () => {
						const response = await newRequestBuilder()
							.withHeader( 'If-None-Match', makeEtag( 'foo', latestRevisionId - 1, 'bar' ) )
							.assertValidRequest()
							.makeRequest();
						assertValid200Response( response, latestRevisionId, lastModifiedDate );
					}
				);

				it( 'if the header is invalid', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', 'not in spec for a If-None-Match header - 200 response' )
						.assertInvalidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Modified-Since', lastModifiedDate ) // this header on its own would return 304
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

			} );

			describe( '304 response', () => {
				it( 'if the current revision ID is the same as the one provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'if the current revision ID is the same as one of the IDs provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1, latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'if the current revision ID is the same as one of the IDs provided (while others are invalid IDs)',
					async () => {
						const response = await newRequestBuilder()
							.withHeader( 'If-None-Match', makeEtag( 'foo', latestRevisionId ) )
							.assertValidRequest()
							.makeRequest();
						assertValid304Response( response, latestRevisionId );
					}
				);

				it( 'if the header is *', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '*' )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder()
						// this header on its own would return 200
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );
			} );
		} );

		describe( `If-Modified-Since - ${newRequestBuilder().getRouteDescription()}`, () => {
			describe( '200 response', () => {
				it( 'If-Modified-Since header is older than current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );
			} );

			describe( '304 response', () => {
				it( 'If-Modified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', lastModifiedDate )
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'If-Modified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( lastModifiedDate ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', futureDate )
						.makeRequest();

					assertValid304Response( response, latestRevisionId );
				} );
			} );
		} );

		describe( 'If-Match', () => {
			it( 'responds 412 given an outdated revision ID', async () => {
				const response = await newRequestBuilder()
					.withHeader( 'If-Match', makeEtag( latestRevisionId - 1 ) )
					.assertValidRequest()
					.makeRequest();

				assert.strictEqual( response.status, 412 );
			} );
		} );

		describe( 'If-Unmodified-Since', () => {
			describe( '200 response', () => {
				it( 'If-Unmodified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Unmodified-Since', lastModifiedDate )
						.makeRequest();

					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'If-Unmodified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( lastModifiedDate ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Unmodified-Since', futureDate )
						.makeRequest();

					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );
			} );

			it( 'responds 412 given the specified date is older than current revision', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newRequestBuilder()
					.withHeader( 'If-Unmodified-Since', yesterday )
					.assertValidRequest()
					.makeRequest();

				assert.strictEqual( response.status, 412 );
			} );
		} );
	} );

	describe( 'Conditional edit requests', () => {

		beforeEach( async () => {
			// restore the item state in between tests that may or may not edit its data
			statementId = ( await newAddItemStatementRequestBuilder(
				itemId,
				entityHelper.newStatementWithRandomStringValue( statementPropertyId )
			).makeRequest() ).body.id;

			const testItemCreationMetadata = await getLatestEditMetadata( itemId );
			lastModifiedDate = new Date( testItemCreationMetadata.timestamp );
			latestRevisionId = testItemCreationMetadata.revid;
		} );

		const editRoutes = [
			() => rbf.newReplaceStatementRequestBuilder(
				statementId,
				newStatementWithRandomStringValue( statementPropertyId )
			),
			() => rbf.newReplaceItemStatementRequestBuilder(
				itemId,
				statementId,
				newStatementWithRandomStringValue( statementPropertyId )
			),
			() => rbf.newRemoveStatementRequestBuilder( statementId ),
			() => rbf.newRemoveItemStatementRequestBuilder( itemId, statementId ),
			() => rbf.newAddItemStatementRequestBuilder(
				itemId,
				newStatementWithRandomStringValue( statementPropertyId )
			)
		];

		if ( hasJsonDiffLib() ) { // awaiting security review (T316245)
			editRoutes.push( () => rbf.newPatchItemStatementRequestBuilder(
				itemId,
				statementId,
				[ {
					op: 'replace',
					path: '/mainsnak',
					value: newStatementWithRandomStringValue( statementPropertyId ).mainsnak
				} ]
			) );
			editRoutes.push( () => rbf.newPatchStatementRequestBuilder(
				statementId,
				[ {
					op: 'replace',
					path: '/mainsnak',
					value: newStatementWithRandomStringValue( statementPropertyId ).mainsnak
				} ]
			) );
		}

		editRoutes.forEach( ( newRequestBuilder ) => {
			describe( `If-Match - ${newRequestBuilder().getRouteDescription()}`, () => {
				it( 'responds with 412 given an outdated revision id', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( latestRevisionId - 1 ) )
						.makeRequest();

					assert.strictEqual( response.status, 412 );
				} );

				it( 'responds with 2xx and makes the edit given the latest revision id', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( latestRevisionId ) )
						.makeRequest();

					assert.ok(
						response.status >= 200 && response.status < 300,
						`expected 2xx status, but got ${response.status}`
					);
				} );
			} );

			describe( `If-Unmodified-Since - ${newRequestBuilder().getRouteDescription()}`, () => {
				it( 'responds with 412 given an outdated last modified date', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Unmodified-Since', 'Wed, 27 Jul 2022 08:24:29 GMT' )
						.makeRequest();

					assert.strictEqual( response.status, 412 );
				} );

				it( 'responds with 2xx and makes the edit given the latest modified date', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Unmodified-Since', lastModifiedDate )
						.makeRequest();

					assert.ok(
						response.status >= 200 && response.status < 300,
						`expected 2xx status, but got ${response.status}`
					);
				} );
			} );

			describe( `If-None-Match - ${newRequestBuilder().getRouteDescription()}`, () => {
				it( 'responds with 412 given a wildcard', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '*' )
						.makeRequest();

					assert.strictEqual( response.status, 412 );
				} );
			} );
		} );

	} );

} );
