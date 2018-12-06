const Util = require( 'wdio-mediawiki/Util' ),
	ItemPage = require( 'wdio-wikibase/pageobjects/item.page' ),
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );

describe( 'WikibaseReferenceOnProtectedPage', function () {

	it( 'can expand collapsed references on a protected page as unprivileged user', function () {
		let itemId, propertyId;

		browser.call( () => {
			return WikibaseApi.createItem( Util.getTestString( 'T186006-' ) )
				.then( ( id ) => {
					itemId = id;
				} );
		} );
		browser.call( () => {
			return WikibaseApi.getProperty( 'string' )
				.then( ( id ) => {
					propertyId = id;
				} );
		} );

		ItemPage.open( itemId );
		ItemPage.addMainStatement( propertyId, 'mval' );
		ItemPage.addReferenceToNthStatementOfStatementGroup( 0, propertyId, propertyId, 'refval' );
		browser.call( () => WikibaseApi.protectEntity( itemId ) );
		ItemPage.open( itemId );

		browser.waitForVisible( '.wikibase-statementview-references-container .wikibase-statementview-references-heading a.ui-toggler' );
		if ( browser.isExisting( '.wikibase-addtoolbar' ) ) {
			throw new Error( 'This shouldn\'t exist on a protected page!' );
		}
	} );
} );
