const assert = require( 'assert' ),
	MWBot = require( 'mwbot' ),
	Page = require( 'wdio-mediawiki/Page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );
let WikibaseApi;
try {
	WikibaseApi = require( 'wdio-wikibase/wikibase.api' );
} catch ( e ) {
	WikibaseApi = require( '../wdio-wikibase/wikibase.api' );
}

describe( 'blocked user cannot use', function () {

	const bot = new MWBot( {
		apiUrl: browser.options.baseUrl + '/api.php'
	} );

	before( function setupBot() {
		return bot.loginGetEditToken( {
			username: browser.options.username,
			password: browser.options.password
		} );
	} );

	before( function loginUser() {
		return LoginPage.loginAdmin();
	} );

	beforeEach( WikibaseApi.blockUser );

	afterEach( WikibaseApi.unblockUser );

	function assertIsUserBlockedError() {
		$( '#mw-returnto' ).waitForVisible();

		assert.strictEqual( $( '#firstHeading' ).getText(), 'User is blocked' );
	}

	function assertDoesNotExist( selector ) {
		const element = $( selector );
		assert.strictEqual( typeof element, 'object', `$( '${selector}' ) should have returned a NoSuchElement error` );
		assert.strictEqual( element.type, 'NoSuchElement', `$( '${selector}' ) should have returned a NoSuchElement error` );
	}

	const tests = [
		{ name: 'SetLabel', ids: [ 'wb-modifyentity-id', 'wb-setlabel-submit' ] },
		{ name: 'SetDescription', ids: [ 'wb-modifyentity-id', 'wb-setdescription-submit' ] },
		{ name: 'SetAliases', ids: [ 'wb-modifyentity-id', 'wb-setaliases-submit' ] },
		{ name: 'SetLabelDescriptionAliases', ids: [ 'wb-modifyentity-id', 'wb-setlabeldescriptionaliases-submit' ] },
		{ name: 'SetSiteLink', ids: [ 'wb-modifyentity-id', 'wb-setsitelink-submit' ] },
		{ name: 'NewItem', ids: [ 'wb-newentity-label', 'wb-newentity-submit' ] },
		{ name: 'NewProperty', ids: [ 'wb-newentity-label', 'wb-newentity-submit' ] },
		{ name: 'MergeItems', ids: [ 'wb-mergeitems-fromid', 'wb-mergeitems-submit' ] },
		{ name: 'RedirectEntity', ids: [ 'wb-redirectentity-fromid', 'wb-redirectentity-submit' ] }
	];

	for ( const test of tests ) {
		const title = `Special:${test.name}`;
		it( title, function () {
			( new Page() ).openTitle( title );

			assertIsUserBlockedError();

			for ( const id of test.ids ) {
				const selector = `#${id}`;
				assertDoesNotExist( selector );
			}
		} );
	}
} );
