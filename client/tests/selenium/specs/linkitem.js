'use strict';

const Api = require( 'wdio-mediawiki/Api' );
const LinkItemPage = require( '../pageobjects/linkitem.page.js' );
const LoginPage = require( 'wdio-mediawiki/LoginPage' );

describe( 'Add interlanguage links', () => {

	before( async () => {
		await LoginPage.loginAdmin();

		const testPageTitle = 'Project:LinkItemTest';

		await ( await Api.bot() ).edit( testPageTitle, 'The page exists' );
	} );

	it( 'dialog loads on click', async () => {
		await LinkItemPage.open();

		await expect( await LinkItemPage.addInterlanguageLinks ).toBeDisplayed();

		await LinkItemPage.addInterlanguageLinks.click();

		await expect( await LinkItemPage.linkItemDialog ).toBeDisplayed();
	} );

} );
