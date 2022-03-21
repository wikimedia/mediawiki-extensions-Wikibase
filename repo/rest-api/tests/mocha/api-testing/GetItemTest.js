'use strict';

const { REST, assert, action, utils } = require( 'api-testing' );
const createEntity = require( '../helpers/entityHelper' ).createEntity;

const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	let testModified;
	let testRevisionId;
	const basePath = 'rest.php/wikibase/v0';

	before( async () => {
		const createItemResponse = await createEntity( 'item', {
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			},
			descriptions: {
				en: { language: 'en', value: englishDescription }
			}
		} );
		testItemId = createItemResponse.entity.id;

		const getItemMetadata = await action.getAnon().action( 'wbgetentities', {
			ids: testItemId
		}, 'GET' );

		testModified = new Date( getItemMetadata.entities[ testItemId ].modified ).toUTCString();
		testRevisionId = getItemMetadata.entities[ testItemId ].lastrevid;
	} );

	it( 'can GET an item with metadata', async () => {
		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${testItemId}` );

		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testItemId );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
	} );

	it( 'can GET a partial item with single _fields param', async () => {
		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${testItemId}?_fields=labels` );

		assert.equal( response.status, 200 );
		assert.deepEqual( response.body, {
			id: testItemId,
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			}
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
	} );

	it( 'can GET a partial item with multiple _fields params', async () => {
		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${testItemId}?_fields=labels|descriptions|aliases` );

		assert.equal( response.status, 200 );
		assert.deepEqual( response.body, {
			id: testItemId,
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			},
			descriptions: {
				en: { language: 'en', value: englishDescription }
			},
			aliases: {} // expect {}, not []
		} );
		assert.equal( response.header[ 'last-modified' ], testModified );
		assert.equal( response.header.etag, testRevisionId );
	} );

	it( '400 error - bad request', async () => {
		const itemId = 'X123';
		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${itemId}` );

		assert.equal( response.status, 400 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'invalid-item-id' );
		assert.include( response.body.message, itemId );
	} );

	it( '404 error - item not found', async () => {
		const itemId = 'Q999999';
		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${itemId}` );

		assert.equal( response.status, 404 );
		assert.header( response, 'Content-Language', 'en' );
		assert.equal( response.body.code, 'item-not-found' );
		assert.include( response.body.message, itemId );
	} );
} );
