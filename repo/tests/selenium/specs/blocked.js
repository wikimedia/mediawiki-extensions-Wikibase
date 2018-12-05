const assert = require( 'assert' ),
	MWBot = require( 'mwbot' ),
	Page = require( 'wdio-mediawiki/Page' ),
	LoginPage = require( 'wdio-mediawiki/LoginPage' );

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

	beforeEach( function blockUser() {
		return bot.request( {
			action: 'block',
			user: browser.options.username,
			expiry: '1 minute',
			reason: 'Wikibase browser test (T211120)',
			token: bot.editToken
		} );
	} );

	afterEach( function unblockUser() {
		return bot.request( {
			action: 'unblock',
			user: browser.options.username,
			reason: 'Wikibase browser test done (T211120)',
			token: bot.editToken
		} );
	} );

	function assertIsUserBlockedError() {
		$( '#mw-returnto' ).waitForVisible();

		assert.strictEqual( $( '#firstHeading' ).getText(), 'User is blocked' );
	}

	function assertDoesNotExist( selector ) {
		const element = $( selector );
		assert.strictEqual( typeof element, 'object' );
		assert.strictEqual( element.type, 'NoSuchElement' );
	}

	const immediateIsUserBlockedTests = [
		{ name: 'SetLabel', selectors: [ 'wb-modifyentity-id', 'wb-setlabel-submit' ] },
		{ name: 'SetDescription', selectors: [ 'wb-modifyentity-id', 'wb-setdescription-submit' ] },
		{ name: 'SetAliases', selectors: [ 'wb-modifyentity-id', 'wb-setaliases-submit' ] },
		{ name: 'SetLabelDescriptionAliases', selectors: [ 'wb-modifyentity-id', 'wb-setlabeldescriptionaliases-submit' ] },
		{ name: 'SetSiteLink', selectors: [ 'wb-modifyentity-id', 'wb-setsitelink-submit' ] },
		{ name: 'NewItem', selectors: [ 'wb-newentity-label', 'wb-newentity-submit' ] },
		{ name: 'NewProperty', selectors: [ 'wb-newentity-label', 'wb-newentity-submit' ] }
	];

	for ( const test of immediateIsUserBlockedTests ) {
		const title = `Special:${test.name}`;
		it( title, function () {
			( new Page() ).openTitle( title );

			assertIsUserBlockedError();

			for ( const selector of test.selectors ) {
				assertDoesNotExist( selector );
			}
		} );
	}
} );
