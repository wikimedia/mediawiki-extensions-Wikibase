'use strict';

const { REST, assert, action, utils } = require( 'api-testing' );
const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	let testModified;
	let testRevisionId;
	const basePath = 'rest.php/wikibase/v0';

	before( async () => {
		const createItem = await action.getAnon().action( 'wbeditentity', {
			new: 'item',
			token: '+\\',
			data: JSON.stringify( {
				labels: {
					de: { language: 'de', value: germanLabel },
					en: { language: 'en', value: englishLabel }
				},
				descriptions: {
					en: { language: 'en', value: englishDescription }
				}
			} )
		}, 'POST' );
		testItemId = createItem.entity.id;

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
} );
