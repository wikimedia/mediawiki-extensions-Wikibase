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
const {
	editRequestsOnItem,
	editRequestsOnProperty,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );

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

	const itemRequestInputs = {};
	const propertyRequestInputs = {};

	before( async () => {
		const propertyId = ( await createUniqueStringProperty() ).entity.id;

		const entityParts = {
			claims: [ newLegacyStatementWithRandomStringValue( propertyId ) ],
			descriptions: { en: { language: 'en', value: `entity-with-statements-${utils.uniq()}` } },
			labels: { en: { language: 'en', value: `entity-with-statements-${utils.uniq()}` } },
			aliases: {
				en: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ]
			}
		};

		const createItemResponse = await createEntity( 'item', entityParts );
		itemRequestInputs.entityType = 'item';
		itemRequestInputs.stringPropertyId = propertyId;
		itemRequestInputs.itemId = createItemResponse.entity.id;
		itemRequestInputs.statementId = createItemResponse.entity.claims[ propertyId ][ 0 ].id;
		itemRequestInputs.mainTestSubject = itemRequestInputs.itemId;
		itemRequestInputs.latestRevision = await getLatestEditMetadata( itemRequestInputs.mainTestSubject );

		entityParts.datatype = 'string';
		const createPropertyResponse = await createEntity( 'property', entityParts );
		propertyRequestInputs.entityType = 'property';
		propertyRequestInputs.statementPropertyId = propertyId;
		propertyRequestInputs.stringPropertyId = createPropertyResponse.entity.id;
		propertyRequestInputs.statementId = createPropertyResponse.entity.claims[ propertyId ][ 0 ].id;
		propertyRequestInputs.mainTestSubject = propertyRequestInputs.stringPropertyId;
		propertyRequestInputs.latestRevision = await getLatestEditMetadata( propertyRequestInputs.mainTestSubject );
	} );
	const useRequestInputs = ( requestInputs, newReqBuilder ) => () => newReqBuilder( requestInputs );

	const getRequestsByEntityType = [
		{ requests: getRequestsOnItem, requestInputs: itemRequestInputs },
		{ requests: getRequestsOnProperty, requestInputs: propertyRequestInputs }
	];

	getRequestsByEntityType.forEach( ( { requests, requestInputs } ) => {
		describe( `${requestInputs.entityType} GET requests`, () => {

			requests.forEach( ( newRequestBuilder ) => {
				newRequestBuilder = useRequestInputs( requestInputs, newRequestBuilder );
				describe(
					`If-None-Match - ${newRequestBuilder().getRouteDescription()}`, () => {
						describe( '200 response', () => {
							it( 'if the current entity revision is newer than the ID provided', async () => {
								const response = await newRequestBuilder()
									.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevision.revid - 1 ) )
									.assertValidRequest()
									.makeRequest();
								assertValid200Response(
									response,
									requestInputs.latestRevision.revid,
									requestInputs.latestRevision.timestamp
								);
							} );

							it( 'if the current revision is newer than any of the IDs provided', async () => {
								const response = await newRequestBuilder()
									.withHeader(
										'If-None-Match',
										makeEtag(
											requestInputs.latestRevision.revid - 1,
											requestInputs.latestRevision.revid - 2
										)
									).assertValidRequest()
									.makeRequest();
								assertValid200Response(
									response,
									requestInputs.latestRevision.revid,
									requestInputs.latestRevision.timestamp
								);
							} );

							it( 'if the provided ETag is not a valid revision ID', async () => {
								const response = await newRequestBuilder()
									.withHeader( 'If-None-Match', '"foo"' )
									.assertValidRequest()
									.makeRequest();
								assertValid200Response(
									response,
									requestInputs.latestRevision.revid,
									requestInputs.latestRevision.timestamp
								);
							} );

							it( 'if all the provided ETags are not valid revision IDs', async () => {
								const response = await newRequestBuilder()
									.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
									.assertValidRequest()
									.makeRequest();
								assertValid200Response(
									response,
									requestInputs.latestRevision.revid,
									requestInputs.latestRevision.timestamp
								);
							} );

							it( 'if the current revision is newer than any IDs provided (while others are invalid IDs)',
								async () => {
									const response = await newRequestBuilder()
										.withHeader(
											'If-None-Match',
											makeEtag( 'foo', requestInputs.latestRevision.revid - 1, 'bar' )
										)
										.assertValidRequest()
										.makeRequest();
									assertValid200Response(
										response,
										requestInputs.latestRevision.revid,
										requestInputs.latestRevision.timestamp
									);
								}
							);

							it( 'if the header is invalid', async () => {
								const response = await newRequestBuilder()
									.withHeader(
										'If-None-Match',
										'not in spec for a If-None-Match header - 200 response'
									).assertInvalidRequest()
									.makeRequest();
								assertValid200Response(
									response,
									requestInputs.latestRevision.revid,
									requestInputs.latestRevision.timestamp
								);
							} );

							it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
								const response = await newRequestBuilder()
									.withHeader(
										'If-Modified-Since',
										requestInputs.latestRevision.timestamp
									) // this header on its own would return 304
									.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevision.revid - 1 ) )
									.assertValidRequest()
									.makeRequest();
								assertValid200Response(
									response,
									requestInputs.latestRevision.revid,
									requestInputs.latestRevision.timestamp
								);
							} );

						} );

						describe( '304 response', () => {
							it( 'if the current revision ID is the same as the one provided', async () => {
								const response = await newRequestBuilder()
									.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevision.revid ) )
									.assertValidRequest()
									.makeRequest();
								assertValid304Response( response, requestInputs.latestRevision.revid );
							} );

							it(
								'if the current revision ID is the same as one of the IDs provided',
								async () => {
									const response = await newRequestBuilder()
										.withHeader(
											'If-None-Match',
											makeEtag(
												requestInputs.latestRevision.revid - 1,
												requestInputs.latestRevision.revid
											)
										)
										.assertValidRequest()
										.makeRequest();
									assertValid304Response( response, requestInputs.latestRevision.revid );
								}
							);

							it(
								'if the current revision ID is one of the IDs provided (while others are invalid IDs)',
								async () => {
									const response = await newRequestBuilder()
										.withHeader(
											'If-None-Match',
											makeEtag( 'foo', requestInputs.latestRevision.revid )
										)
										.assertValidRequest()
										.makeRequest();
									assertValid304Response( response, requestInputs.latestRevision.revid );
								}
							);

							it( 'if the header is *', async () => {
								const response = await newRequestBuilder()
									.withHeader( 'If-None-Match', '*' )
									.assertValidRequest()
									.makeRequest();
								assertValid304Response( response, requestInputs.latestRevision.revid );
							} );

							it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
								const response = await newRequestBuilder()
									// this header on its own would return 200
									.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
									.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevision.revid ) )
									.assertValidRequest()
									.makeRequest();
								assertValid304Response( response, requestInputs.latestRevision.revid );
							} );
						} );
					} );

				describe( `If-Match - ${newRequestBuilder().getRouteDescription()}`, () => {
					describe( '200 response', () => {
						it( 'if the current revision matches the ID provided', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Match', makeEtag( requestInputs.latestRevision.revid ) )
								.assertValidRequest()
								.makeRequest();
							assertValid200Response(
								response,
								requestInputs.latestRevision.revid,
								requestInputs.latestRevision.timestamp
							);
						} );

						it( 'if the header is *', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Match', '*' )
								.assertValidRequest()
								.makeRequest();
							assertValid200Response(
								response,
								requestInputs.latestRevision.revid,
								requestInputs.latestRevision.timestamp
							);
						} );

						it( 'if the current revision matches one of the IDs provided', async () => {
							const response = await newRequestBuilder()
								.withHeader(
									'If-Match',
									makeEtag(
										requestInputs.latestRevision.revid - 1,
										requestInputs.latestRevision.revid
									)
								)
								.assertValidRequest()
								.makeRequest();
							assertValid200Response(
								response,
								requestInputs.latestRevision.revid,
								requestInputs.latestRevision.timestamp
							);
						} );

					} );

					describe( '412 response', () => {
						it( 'if the provided revision ID is outdated', async () => {
							const response = await newRequestBuilder()
								.withHeader(
									'If-Match',
									makeEtag( requestInputs.latestRevision.revid - 1 )
								)
								.assertValidRequest()
								.makeRequest();
							assertValid412Response( response );
						} );

						it( 'if all of the provided revision IDs are outdated', async () => {
							const response = await newRequestBuilder()
								.withHeader(
									'If-Match',
									makeEtag(
										requestInputs.latestRevision.revid - 1,
										requestInputs.latestRevision.revid - 2
									)
								).assertValidRequest()
								.makeRequest();
							assertValid412Response( response );
						} );

						it( 'if the provided ETag is not a valid revision ID', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Match', '"foo"' )
								.assertValidRequest()
								.makeRequest();
							assertValid412Response( response );
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
							assertValid200Response(
								response,
								requestInputs.latestRevision.revid,
								requestInputs.latestRevision.timestamp
							);
						} );
					} );

					describe( '304 response', () => {
						it( 'If-Modified-Since header is same as current revision', async () => {
							const response = await newRequestBuilder()
								.assertValidRequest()
								.withHeader( 'If-Modified-Since', requestInputs.latestRevision.timestamp )
								.makeRequest();
							assertValid304Response( response, requestInputs.latestRevision.revid );
						} );

						it( 'If-Modified-Since header is after current revision', async () => {
							const futureDate = new Date(
								new Date( requestInputs.latestRevision.timestamp ).getTime() + 5000
							).toUTCString();
							const response = await newRequestBuilder()
								.assertValidRequest()
								.withHeader( 'If-Modified-Since', futureDate )
								.makeRequest();

							assertValid304Response( response, requestInputs.latestRevision.revid );
						} );
					} );
				} );

				describe( `If-Unmodified-Since - ${newRequestBuilder().getRouteDescription()}`, () => {
					describe( '200 response', () => {
						it( 'If-Unmodified-Since header is same as current revision', async () => {
							const response = await newRequestBuilder()
								.assertValidRequest()
								.withHeader( 'If-Unmodified-Since', requestInputs.latestRevision.timestamp )
								.makeRequest();

							assertValid200Response(
								response,
								requestInputs.latestRevision.revid,
								requestInputs.latestRevision.timestamp
							);
						} );

						it( 'If-Unmodified-Since header is after current revision', async () => {
							const futureDate = new Date(
								new Date( requestInputs.latestRevision.timestamp ).getTime() + 5000
							).toUTCString();
							const response = await newRequestBuilder()
								.assertValidRequest()
								.withHeader( 'If-Unmodified-Since', futureDate )
								.makeRequest();

							assertValid200Response(
								response,
								requestInputs.latestRevision.revid,
								requestInputs.latestRevision.timestamp
							);
						} );
					} );

					it( 'responds 412 given the specified date is older than current revision', async () => {
						const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
						const response = await newRequestBuilder()
							.withHeader( 'If-Unmodified-Since', yesterday )
							.assertValidRequest()
							.makeRequest();

						assertValid412Response( response );
					} );
				} );
			} );

		} );
	} );

	const editRequestsByEntityType = [
		{ requests: editRequestsOnItem, requestInputs: itemRequestInputs },
		{ requests: editRequestsOnProperty, requestInputs: propertyRequestInputs }
	];

	editRequestsByEntityType.forEach( ( { requests, requestInputs } ) => {
		describe( `${requestInputs.entityType} edit requests`, () => {

			requests.forEach( ( newRequestBuilder ) => {
				newRequestBuilder = useRequestInputs( requestInputs, newRequestBuilder );

				describe( newRequestBuilder().getRouteDescription(), () => {

					beforeEach( async () => {
						requestInputs.latestRevision = await getLatestEditMetadata( requestInputs.mainTestSubject );
					} );

					afterEach( async () => {
						if ( newRequestBuilder().getMethod() === 'DELETE' ) {
							// restore the item state in between tests that removed the statement
							itemRequestInputs.statementId = ( await newAddItemStatementRequestBuilder(
								itemRequestInputs.itemId,
								newStatementWithRandomStringValue( itemRequestInputs.stringPropertyId )
							).makeRequest() ).body.id;
						}
					} );

					describe( 'If-Match', () => {
						it( 'responds with 412 given an outdated revision id', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Match', makeEtag( requestInputs.latestRevision.revid - 1 ) )
								.makeRequest();

							assertValid412Response( response );
						} );

						it( 'responds with 2xx and makes the edit given the latest revision id', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Match', makeEtag( requestInputs.latestRevision.revid ) )
								.makeRequest();

							expect( response ).status.to.be.within( 200, 299 );
						} );
					} );

					describe( 'If-Unmodified-Since', () => {
						it( 'responds with 412 given an outdated last modified date', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Unmodified-Since', 'Wed, 27 Jul 2022 08:24:29 GMT' )
								.makeRequest();

							assertValid412Response( response );
						} );

						it( 'responds with 2xx and makes the edit given the latest modified date', async () => {
							const response = await newRequestBuilder()
								.withHeader( 'If-Unmodified-Since', requestInputs.latestRevision.timestamp )
								.makeRequest();

							expect( response ).status.to.be.within( 200, 299 );
						} );
					} );

					describe( 'If-None-Match', () => {
						it( 'responds 2xx if the header does not match the current revision id', async () => {
							const response = await newRequestBuilder()
								.assertValidRequest()
								.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevision.revid - 1 ) )
								.makeRequest();

							expect( response ).status.to.be.within( 200, 299 );
						} );

						describe( '412 response', () => {
							it( 'If-None-Match header is same as current revision', async () => {
								const response = await newRequestBuilder()
									.assertValidRequest()
									.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevision.revid ) )
									.makeRequest();

								assertValid412Response( response );
							} );

							it( 'If-None-Match header is a wildcard', async () => {
								const response = await newRequestBuilder()
									.withHeader( 'If-None-Match', '*' )
									.makeRequest();

								assertValid412Response( response );
							} );
						} );
					} );

					describe( 'If-Modified-Since', () => {
						describe( 'header ignored - 2xx response', () => {
							it( 'If-Modified-Since header is same as current revision', async () => {
								const response = await newRequestBuilder()
									.assertValidRequest()
									.withHeader( 'If-Modified-Since', requestInputs.latestRevision.timestamp )
									.makeRequest();

								expect( response ).status.to.be.within( 200, 299 );
							} );

							it( 'If-Modified-Since header is after current revision', async () => {
								const tomorrow = new Date( Date.now() + 24 * 60 * 60 * 1000 ).toUTCString();
								const response = await newRequestBuilder()
									.assertValidRequest()
									.withHeader( 'If-Modified-Since', tomorrow )
									.makeRequest();

								expect( response ).status.to.be.within( 200, 299 );
							} );

							it( 'If-Modified-Since header is before the current revision', async () => {
								const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
								const response = await newRequestBuilder()
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

} );
