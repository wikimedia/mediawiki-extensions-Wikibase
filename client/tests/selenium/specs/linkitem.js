import { mwbot } from 'wdio-mediawiki/Api.js';
import LinkItemPage from '../pageobjects/linkitem.page.js';
import LoginPage from 'wdio-mediawiki/LoginPage.js';

describe( 'Add interlanguage links', () => {

	let windowSize;

	before( async () => {
		await LoginPage.loginAdmin();

		const testPageTitle = 'Project:LinkItemTest';

		await ( await mwbot() ).edit( testPageTitle, 'The page exists' );

		windowSize = await browser.getWindowSize();
		await browser.setWindowSize( 1185, windowSize.height );
	} );

	after( async () => {
		await browser.setWindowSize( windowSize.width, windowSize.height );
	} );

	it( 'dialog loads on click', async () => {
		await LinkItemPage.open();

		await expect( await LinkItemPage.addInterlanguageLinks ).toBeDisplayed();

		await LinkItemPage.addInterlanguageLinks.click();

		await expect( await LinkItemPage.linkItemDialog ).toBeDisplayed();
	} );

} );
