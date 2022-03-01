'use strict';

const { REST, assert, action, utils } = require( 'api-testing' );
const germanLabel = 'a-German-label-' + utils.uniq();
const englishLabel = 'an-English-label-' + utils.uniq();
const englishDescription = 'an-English-description-' + utils.uniq();

describe( 'GET /entities/items/{id} ', () => {
	let testItemId;
	const basePath = 'rest.php/wikibase/v0';

	before( async () => {
		const response = await action.getAnon().action( 'wbeditentity', {
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
		testItemId = response.entity.id;
	} );

	it( 'can GET an item', async () => {
		const rest = new REST( basePath );
		const response = await rest.get( `/entities/items/${testItemId}` );

		assert.equal( response.status, 200 );
		assert.equal( response.body.id, testItemId );
	} );
} );
