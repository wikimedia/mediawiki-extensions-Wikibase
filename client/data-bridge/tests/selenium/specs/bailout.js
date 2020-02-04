const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	DataBridgePage = require( '../pageobjects/dataBridge.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'bail-out', () => {
	function testBailoutActions() {
		const suggestionGoToRepo = DataBridgePage.bailoutSuggestionGoToRepo;
		const goToRepoLink = suggestionGoToRepo.$( 'a' );
		assert.ok( goToRepoLink.isClickable() );
		const suggestionEditArticle = DataBridgePage.bailoutSuggestionEditArticle;
		const editArticleLink = suggestionEditArticle.$( 'a' );
		assert.ok( editArticleLink.isClickable() );
		assert.ok( /action=edit/.test( editArticleLink.getAttribute( 'href' ) ) );
	}

	it( 'unsupported datatype', () => {
		const title = DataBridgePage.getDummyTitle();
		const propertyId = browser.call( () => WikibaseApi.getProperty( 'url' ) );
		const entityId = browser.call( () => WikibaseApi.createItem( 'data bridge browser test item', {
			'claims': [ {
				'mainsnak': {
					'snaktype': 'value',
					'property': propertyId,
					'datavalue': { 'value': 'https://example.com/', 'type': 'string' },
				},
				'type': 'statement',
				'rank': 'normal',
			} ],
		} ) );
		const content = `{|class="wikitable"
|-
| official website
| {{#statements:${propertyId}|from=${entityId}}}&nbsp;<span data-bridge-edit-flow="overwrite">[https://example.org/wiki/Item:${entityId}?uselang=en#${propertyId} Edit this on Wikidata]</span>
|}`;
		browser.call( () => Api.edit( title, content, browser.config.username, browser.config.password ) );

		DataBridgePage.open( title );
		DataBridgePage.overloadedLink.click();
		DataBridgePage.error.waitForDisplayed( 5000 );

		assert.ok( /\burl\b/.test( DataBridgePage.error.getText() ) );
		testBailoutActions();
	} );
} );
