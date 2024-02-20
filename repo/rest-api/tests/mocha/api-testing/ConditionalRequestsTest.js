'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	getLatestEditMetadata,
	newLegacyStatementWithRandomStringValue,
	createUniqueStringProperty,
	createEntity,
	editEntity,
	createLocalSitelink,
	getLocalSiteId
} = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const {
	editRequestsOnItem,
	editRequestsOnProperty,
	getRequestsOnItem,
	getRequestsOnProperty
} = require( '../helpers/happyPathRequestBuilders' );

function assertValid200Response( response, revisionId, lastModified ) {
	expect( response ).to.have.status( 200 );
	assert.equal( response.header[ 'last-modified' ], lastModified );
	assert.equal( response.header.etag, makeEtag( revisionId ) );
}

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

describe( 'Conditional requests', () => {
	const itemRequestInputs = {};
	const propertyRequestInputs = {};
	const linkedArticle = utils.title( 'Article-linked-to-test-item' );

	async function resetEntityTestData( id, statementPropertyId ) {
		if ( id.startsWith( 'Q' ) ) {
			await createLocalSitelink( id, linkedArticle );
		}

		return ( await editEntity( id, {
			labels: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
			descriptions: [ { language: 'en', value: `entity-with-statements-${utils.uniq()}` } ],
			aliases: [ { language: 'en', value: 'entity' }, { language: 'en', value: 'thing' } ],
			claims: [ newLegacyStatementWithRandomStringValue( statementPropertyId ) ]
		} ) ).entity;
	}

	before( async () => {
		const statementPropertyId = ( await createUniqueStringProperty() ).entity.id;

		const itemId = ( await createEntity( 'item', {} ) ).entity.id;
		const itemData = await resetEntityTestData( itemId, statementPropertyId );

		itemRequestInputs.mainTestSubject = itemId;
		itemRequestInputs.itemId = itemId;
		itemRequestInputs.statementId = itemData.claims[ statementPropertyId ][ 0 ].id;
		itemRequestInputs.statementPropertyId = statementPropertyId;
		itemRequestInputs.siteId = await getLocalSiteId();
		itemRequestInputs.linkedArticle = linkedArticle;

		const latestItemRevision = await getLatestEditMetadata( itemId );
		itemRequestInputs.latestRevId = latestItemRevision.revid;
		itemRequestInputs.latestRevTimestamp = latestItemRevision.timestamp;

		const propertyId = ( await createUniqueStringProperty() ).entity.id;
		const propertyData = await resetEntityTestData( propertyId, statementPropertyId );
		propertyRequestInputs.mainTestSubject = propertyId;
		propertyRequestInputs.propertyId = propertyId;
		propertyRequestInputs.statementId = propertyData.claims[ statementPropertyId ][ 0 ].id;
		propertyRequestInputs.statementPropertyId = statementPropertyId;

		const latestPropertyRevision = await getLatestEditMetadata( propertyId );
		propertyRequestInputs.latestRevId = latestPropertyRevision.revid;
		propertyRequestInputs.latestRevTimestamp = latestPropertyRevision.timestamp;
	} );

	const useRequestInputs = ( requestInputs ) => ( newReqBuilder ) => ( {
		newRequestBuilder: () => newReqBuilder( requestInputs ),
		requestInputs
	} );

	[
		...getRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...getRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	].forEach( ( { newRequestBuilder, requestInputs } ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			describe( 'If-None-Match - 200 response', () => {
				it( 'if the current revision is newer than the ETag provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId - 1 ) )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the current revision is newer than any of the ETags provided', async () => {
					const ifNoneMatchHeader = makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId - 2 );
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', ifNoneMatchHeader )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the provided ETag is not a valid revision ID', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '"foo"' )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if all the provided ETags are not valid revision IDs', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( 'foo', 'bar' ) )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the current revision is newer than an ETag (while other ETags are invalid)', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( 'foo', requestInputs.latestRevId - 1, 'bar' ) )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the header is invalid', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', 'not in spec for an If-None-Match header - 200 response' )
						.assertInvalidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Modified-Since', requestInputs.latestRevTimestamp )
						// the If-None-Match header on its own would return 304
						.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId - 1 ) )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );
			} );

			describe( 'If-None-Match - 304 response', () => {
				it( 'if the current revision ID matches the ETag provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId ) )
						.assertValidRequest()
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId );
				} );

				it( 'if the current revision ID matches one of the ETags provided', async () => {
					const ifNoneMatchHeader = makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId );
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', ifNoneMatchHeader )
						.assertValidRequest()
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId );
				} );

				it( 'if the current revision matches one of the ETags (while other ETags are invalid)', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', makeEtag( 'foo', requestInputs.latestRevId ) )
						.assertValidRequest()
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the header is *', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-None-Match', '*' )
						.assertValidRequest()
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'If-None-Match takes precedence over If-Modified-Since', async () => {
					const response = await newRequestBuilder()
						// the If-Modified-Since header on its own would return 200
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId ) )
						.assertValidRequest()
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );
			} );

			describe( 'If-Match - 200 response', () => {
				it( 'if the current revision matches the ETag provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId ) )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the header is *', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', '*' )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'if the current revision matches one of the ETags provided', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId ) )
						.assertValidRequest()
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );
			} );

			describe( 'If-Match - 412 response', () => {
				it( 'if the provided ETag is a previous revision ID', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId - 1 ) )
						.assertValidRequest()
						.makeRequest();

					assertValid412Response( response );
				} );

				it( 'if all the provided ETags are previous revision IDs', async () => {
					const ifMatchHeader = makeEtag( requestInputs.latestRevId - 1, requestInputs.latestRevId - 2 );
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', ifMatchHeader )
						.assertValidRequest()
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

			describe( 'If-Modified-Since - 200 response', () => {
				it( 'If-Modified-Since header is older than current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', 'Fri, 1 Apr 2022 12:00:00 GMT' )
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );
			} );

			describe( 'If-Modified-Since - 304 response', () => {
				it( 'If-Modified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', requestInputs.latestRevTimestamp )
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId );
				} );

				it( 'If-Modified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( requestInputs.latestRevTimestamp ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Modified-Since', futureDate )
						.makeRequest();

					assertValid304Response( response, requestInputs.latestRevId );
				} );
			} );

			describe( 'If-Unmodified-Since - 200 response', () => {
				it( 'If-Unmodified-Since header is same as current revision', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Unmodified-Since', requestInputs.latestRevTimestamp )
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );

				it( 'If-Unmodified-Since header is after current revision', async () => {
					const futureDate = new Date(
						new Date( requestInputs.latestRevTimestamp ).getTime() + 5000
					).toUTCString();
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-Unmodified-Since', futureDate )
						.makeRequest();

					assertValid200Response( response, requestInputs.latestRevId, requestInputs.latestRevTimestamp );
				} );
			} );

			it( 'responds 412 given If-Unmodified-Since is before current revision', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newRequestBuilder()
					.withHeader( 'If-Unmodified-Since', yesterday )
					.assertValidRequest()
					.makeRequest();

				assertValid412Response( response );
			} );
		} );
	} );

	[
		...editRequestsOnItem.map( useRequestInputs( itemRequestInputs ) ),
		...editRequestsOnProperty.map( useRequestInputs( propertyRequestInputs ) )
	].forEach( ( { newRequestBuilder, requestInputs } ) => {
		describe( newRequestBuilder().getRouteDescription(), () => {
			beforeEach( async () => {
				const latestRevision = await getLatestEditMetadata( requestInputs.mainTestSubject );
				requestInputs.latestRevId = latestRevision.revid;
				requestInputs.latestRevTimestamp = latestRevision.timestamp;
			} );

			afterEach( async () => {
				if ( newRequestBuilder().getMethod() === 'DELETE' ) {
					const entityData = await resetEntityTestData(
						requestInputs.mainTestSubject,
						requestInputs.statementPropertyId
					);
					requestInputs.statementId = entityData.claims[ requestInputs.statementPropertyId ][ 0 ].id;
				}
			} );

			describe( 'If-Match', () => {
				it( 'responds with 412 given an outdated revision id', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId - 1 ) )
						.makeRequest();

					assertValid412Response( response );
				} );

				it( 'responds with 2xx and makes the edit given the latest revision id', async () => {
					const response = await newRequestBuilder()
						.withHeader( 'If-Match', makeEtag( requestInputs.latestRevId ) )
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
						.withHeader( 'If-Unmodified-Since', requestInputs.latestRevTimestamp )
						.makeRequest();

					expect( response ).status.to.be.within( 200, 299 );
				} );
			} );

			describe( 'If-None-Match', () => {
				it( 'responds 2xx if the ETag does not match the current revision id', async () => {
					const response = await newRequestBuilder()
						.assertValidRequest()
						.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId - 1 ) )
						.makeRequest();

					expect( response ).status.to.be.within( 200, 299 );
				} );

				describe( '412 response', () => {
					it( 'If-None-Match header matches the current revision id', async () => {
						const response = await newRequestBuilder()
							.assertValidRequest()
							.withHeader( 'If-None-Match', makeEtag( requestInputs.latestRevId ) )
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
							.withHeader( 'If-Modified-Since', requestInputs.latestRevTimestamp )
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
