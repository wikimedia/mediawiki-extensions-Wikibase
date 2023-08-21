'use strict';

const MWBot = require( 'mwbot' );
const Page = require( 'wdio-mediawiki/Page' );
const LoginPage = require( 'wdio-mediawiki/LoginPage' );

const bot = new MWBot( {
	apiUrl: browser.config.baseUrl + '/api.php'
} );

describe( 'blocked user cannot use', function () {

	before( async () => {
		await bot.loginGetEditToken( {
			username: browser.config.mwUser,
			password: browser.config.mwPwd
		} );

		await LoginPage.loginAdmin();
	} );

	beforeEach( async () => {
		await bot.request( {
			action: 'block',
			user: browser.config.mwUser,
			expiry: '1 minute',
			reason: 'Wikibase browser test (T211120)',
			token: bot.editToken
		} );
	} );

	afterEach( async () => {
		await bot.request( {
			action: 'unblock',
			user: browser.config.mwUser,
			reason: 'Wikibase browser test done (T211120)',
			token: bot.editToken
		} );
	} );

	async function assertIsUserBlockedError() {
		await $( '#mw-returnto' ).waitForDisplayed();

		await expect( $( '#firstHeading' ) ).toHaveText( 'User is blocked' );
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
		// eslint-disable-next-line mocha/no-setup-in-describe
		const title = `Special:${test.name}`;
		it( title, async () => {
			await ( new Page() ).openTitle( title );

			await assertIsUserBlockedError();

			for ( const id of test.ids ) {
				const selector = `#${id}`;
				await expect( $( selector ) ).not.toExist();
			}
		} );
	}
} );
