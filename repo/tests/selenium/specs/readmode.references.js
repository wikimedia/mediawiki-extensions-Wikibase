const Util = require( 'wdio-mediawiki/Util' ),
	ItemPage = require( '../pageobjects/item.page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' ),
	WikibaseApi = require( '../wikibase.api' );

describe( 'WikibaseReferenceOnProtectedPage', function () {
	const MAIN_STATEMENTS = 'div.wikibase-entityview-main > .wikibase-statementgrouplistview ',
		ADD_STATEMENT = '> div.wikibase-addtoolbar > span > a ',
		SAVE = '.wikibase-toolbar-button-save ',
		PROPERTY_INPUT = '.ui-entityselector-input ',
		VALUE_INPUT = '.valueview-input ',
		QUALIFIERS = '.wikibase-statementview-qualifiers ',
		REFERENCES = '.wikibase-statementview-references ',
		NTH_ITEM = ( n ) => `.wikibase-listview > .listview-item:nth-child(${n}) `;

		function saveButtonEnabled() {
		return $( MAIN_STATEMENTS + SAVE ).getAttribute( 'aria-disabled' ) === 'false';
	}

	it( 'can expand collapsed references on a protected page as unprivileged user', function () {
		// high-level overview: add statement, add qualifier, add second qualifier, add reference, save
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

		console.log({itemId});

		ItemPage.open( itemId );
		ItemPage.addMainStatement( 'P121', 'foo' );
		ItemPage.addReferenceToNthStatementOfStatementGroup( 0, 'P121', 'P121', 'reference value 1-1');

		ItemPage.protectPage( itemId );

		// LoginPage.loginAdmin();
		// ItemPage.open( itemId );
		// browser.waitForVisible('#p-cactions');
		// $('#p-cactions').click();
		// browser.waitForVisible('#ca-protect');
		// $('#ca-protect').click();
		// browser.waitForVisible('#mwProtect-level-edit');
		// $('#mwProtect-level-edit').$('[value="sysop"]').click();
		// $('#mw-Protect-submit').click();
		// browser.waitForVisible('#pt-logout');
		// $('#pt-logout a').click();
		// ItemPage.open( itemId );

		browser.waitForVisible('.wikibase-statementview-references-container .wikibase-statementview-references-heading a.ui-toggler');

	});
});
