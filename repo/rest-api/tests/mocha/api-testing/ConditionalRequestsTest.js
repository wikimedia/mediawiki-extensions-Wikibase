'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	getLatestEditMetadata,
	newStatementWithRandomStringValue,
	newLegacyStatementWithRandomStringValue,
	createUniqueStringProperty,
	createEntity
} = require( '../helpers/entityHelper' );
const { newAddItemStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { getRequests, editRequests } = require( '../helpers/happyPathRequestBuilders' );

function assertValid304Response( response, revisionId ) {
	expect( response ).to.have.status( 304 );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
	assert.equal( response.text, '' );
}

function assertValid412Response( response ) {
	expect( response ).to.have.status( 412 );
	assert.isUndefined( response.header.etag );
	assert.isUndefined( response.header[ 'last-modified' ] );
	assert.isEmpty( response.text );
}

function assertValid200Response( response, revisionId, lastModified ) {
	expect( response ).to.have.status( 200 );
	assert.equal( response.header[ 'last-modified' ], lastModified );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
}

describe( 'Conditional requests', () => {

	const requestInputs = {};
	let latestRevisionId;
	let lastModifiedDate;

	before( async () => {
		requestInputs.stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createItemResponse = await createEntity(
			'item',
			{
				claims: [ newLegacyStatementWithRandomStringValue( requestInputs.stringPropertyId ) ],
				descriptions: { en: { language: 'en', value: `item-with-statements-${utils.uniq()}` } },
				labels: { en: { language: 'en', value: `item-with-statements-${utils.uniq()}` } },
				aliases: {
					en: [ { language: 'en', value: 'Douglas Noël Adams' }, { language: 'en', value: 'DNA' } ]
				}
			}
		);
		requestInputs.itemId = createItemResponse.entity.id;
		requestInputs.statementId = createItemResponse.entity.claims[ requestInputs.stringPropertyId ][ 0 ].id;

		const testItemCreationMetadata = await getLatestEditMetadata( requestInputs.itemId );
		lastModifiedDate = testItemCreationMetadata.timestamp;
		latestRevisionId = testItemCreationMetadata.revid;
	} );

	getRequests.forEach( ( newRequestBuilder ) => {
		describe( `If-None-Match - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, () => {
			describe( '200 response', () => {
				it( 'if the current item revision is newer than the ID provided', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision is newer than any of the IDs provided', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1, latestRevisionId - 2 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the provided ETag is not a valid revision ID', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', '"foo"' )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if all the provided ETags are not valid revision IDs', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision is newer than any IDs provided (while others are invalid IDs)',
					async () => {
						const response = await newRequestBuilder( requestInputs )
							.withHeader( 'If-None-Match', makeEtag( 'foo', latestRevisionId - 1, 'bar' ) )
							.assertValidRequest()
							.makeRequest();
						assertValid200Response( response, latestRevisionId, lastModifiedDate );
					}
				);

				it( 'if the header is invalid', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', 'not in spec for a If-None-Match header - 200 response' )
						.assertInvalidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Modified-Since', lastModifiedDate ) // this header on its own would return 304
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

			} );

			describe( '304 response', () => {
				it( 'if the current revision ID is the same as the one provided', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'if the current revision ID is the same as one of the IDs provided', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1, latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'if the current revision ID is the same as one of the IDs provided (while others are invalid IDs)',
					async () => {
						const response = await newRequestBuilder( requestInputs )
							.withHeader( 'If-None-Match', makeEtag( 'foo', latestRevisionId ) )
							.assertValidRequest()
							.makeRequest();
						assertValid304Response( response, latestRevisionId );
					}
				);

				it( 'if the header is *', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-None-Match', '*' )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder( requestInputs )
						// this header on its own would return 200
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );
			} );
		} );

		describe( `If-Match - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, () => {
			describe( '200 response', () => {
				it( 'if the current item revision matches the ID provided', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Match', makeEtag( latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the header is *', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Match', '*' )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'if the current revision matches one of the IDs provided', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Match', makeEtag( latestRevisionId - 1, latestRevisionId ) )
						.assertValidRequest()
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

			} );

			describe( '412 response', () => {
				it( 'if the provided revision ID is outdated', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Match', makeEtag( latestRevisionId - 1 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid412Response( response );
				} );

				it( 'if all of the provided revision IDs are outdated', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Match', makeEtag( latestRevisionId - 1, latestRevisionId - 2 ) )
						.assertValidRequest()
						.makeRequest();
					assertValid412Response( response );
				} );

				it( 'if the provided ETag is not a valid revision ID', async () => {
					const response = await newRequestBuilder( requestInputs )
						.withHeader( 'If-Match', '"foo"' )
						.assertValidRequest()
						.makeRequest();
					assertValid412Response( response );
				} );

			} );
		} );

		describe( `If-Modified-Since - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, () => {
			describe( '200 response', () => {
				it( 'If-Modified-Since header is older than current revision', async () => {
					const response = await newRequestBuilder( requestInputs )
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.makeRequest();
					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );
			} );

			describe( '304 response', () => {
				it( 'If-Modified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder( requestInputs )
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', lastModifiedDate )
						.makeRequest();
					assertValid304Response( response, latestRevisionId );
				} );

				it( 'If-Modified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( lastModifiedDate ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder( requestInputs )
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', futureDate )
						.makeRequest();

					assertValid304Response( response, latestRevisionId );
				} );
			} );
		} );

		describe( `If-Unmodified-Since - ${newRequestBuilder( requestInputs ).getRouteDescription()}`, () => {
			describe( '200 response', () => {
				it( 'If-Unmodified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder( requestInputs )
						.assertValidRequest()
						.withHeader( 'If-Unmodified-Since', lastModifiedDate )
						.makeRequest();

					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );

				it( 'If-Unmodified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( lastModifiedDate ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder( requestInputs )
						.assertValidRequest()
						.withHeader( 'If-Unmodified-Since', futureDate )
						.makeRequest();

					assertValid200Response( response, latestRevisionId, lastModifiedDate );
				} );
			} );

			it( 'responds 412 given the specified date is older than current revision', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newRequestBuilder( requestInputs )
					.withHeader( 'If-Unmodified-Since', yesterday )
					.assertValidRequest()
					.makeRequest();

				assertValid412Response( response );
			} );
		} );
	} );

	describe( 'Conditional edit requests', () => {

		editRequests.forEach( ( newRequestBuilder ) => {
			const routeDescription = newRequestBuilder( requestInputs ).getRouteDescription();
			describe( routeDescription, () => {

				beforeEach( async () => {
					const testItemCreationMetadata = await getLatestEditMetadata( requestInputs.itemId );
					lastModifiedDate = new Date( testItemCreationMetadata.timestamp );
					latestRevisionId = testItemCreationMetadata.revid;
				} );

				afterEach( async () => {
					if ( newRequestBuilder( requestInputs ).getMethod() === 'DELETE' ) {
						// restore the item state in between tests that removed the statement
						requestInputs.statementId = ( await newAddItemStatementRequestBuilder(
							requestInputs.itemId,
							newStatementWithRandomStringValue( requestInputs.stringPropertyId )
						).makeRequest() ).body.id;
					}
				} );

				describe( 'If-Match', () => {
					it( 'responds with 412 given an outdated revision id', async () => {
						const response = await newRequestBuilder( requestInputs )
							.withHeader( 'If-Match', makeEtag( latestRevisionId - 1 ) )
							.makeRequest();

						assertValid412Response( response );
					} );

					it( 'responds with 2xx and makes the edit given the latest revision id', async () => {
						const response = await newRequestBuilder( requestInputs )
							.withHeader( 'If-Match', makeEtag( latestRevisionId ) )
							.makeRequest();

						expect( response ).status.to.be.within( 200, 299 );
					} );
				} );

				describe( 'If-Unmodified-Since', () => {
					it( 'responds with 412 given an outdated last modified date', async () => {
						const response = await newRequestBuilder( requestInputs )
							.withHeader( 'If-Unmodified-Since', 'Wed, 27 Jul 2022 08:24:29 GMT' )
							.makeRequest();

						assertValid412Response( response );
					} );

					it( 'responds with 2xx and makes the edit given the latest modified date', async () => {
						const response = await newRequestBuilder( requestInputs )
							.withHeader( 'If-Unmodified-Since', lastModifiedDate )
							.makeRequest();

						expect( response ).status.to.be.within( 200, 299 );
					} );
				} );

				describe( 'If-None-Match', () => {
					it( 'responds 2xx if the header does not match the current revision id', async () => {
						const response = await newRequestBuilder( requestInputs )
							.assertValidRequest()
							.withHeader( 'If-None-Match', makeEtag( latestRevisionId - 1 ) )
							.makeRequest();

						expect( response ).status.to.be.within( 200, 299 );
					} );

					describe( '412 response', () => {
						it( 'If-None-Match header is same as current revision', async () => {
							const response = await newRequestBuilder( requestInputs )
								.assertValidRequest()
								.withHeader( 'If-None-Match', makeEtag( latestRevisionId ) )
								.makeRequest();

							assertValid412Response( response );
						} );

						it( 'If-None-Match header is a wildcard', async () => {
							const response = await newRequestBuilder( requestInputs )
								.withHeader( 'If-None-Match', '*' )
								.makeRequest();

							assertValid412Response( response );
						} );
					} );
				} );

				describe( 'If-Modified-Since', () => {
					describe( 'header ignored - 2xx response', () => {
						it( 'If-Modified-Since header is same as current revision', async () => {
							const response = await newRequestBuilder( requestInputs )
								.assertValidRequest()
								.withHeader( 'If-Modified-Since', lastModifiedDate )
								.makeRequest();

							expect( response ).status.to.be.within( 200, 299 );
						} );

						it( 'If-Modified-Since header is after current revision', async () => {
							const tomorrow = new Date( Date.now() + 24 * 60 * 60 * 1000 ).toUTCString();
							const response = await newRequestBuilder( requestInputs )
								.assertValidRequest()
								.withHeader( 'If-Modified-Since', tomorrow )
								.makeRequest();

							expect( response ).status.to.be.within( 200, 299 );
						} );

						it( 'If-Modified-Since header is before the current revision', async () => {
							const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
							const response = await newRequestBuilder( requestInputs )
								.assertValidRequest()
								.withHeader( 'If-Modified-Since', yesterday )
								.makeRequest();

							expect( response ).status.to.be.within( 200, 299 );
						} );
					} );
				} );
			} );
		} );
	} );

} );
