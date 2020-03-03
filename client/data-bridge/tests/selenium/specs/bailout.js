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
		const content = DataBridgePage.createInfoboxWikitext( [ {
			label: 'official website',
			entityId,
			propertyId,
			editFlow: 'overwrite',
		} ] );
		browser.call( () => Api.bot().then( ( bot ) => bot.edit( title, content ) ) );

		DataBridgePage.open( title );
		DataBridgePage.overloadedLink.click();
		DataBridgePage.error.waitForDisplayed( 5000 );

		assert.ok( /\burl\b/.test( DataBridgePage.error.getText() ) );
		testBailoutActions();
	} );
} );
