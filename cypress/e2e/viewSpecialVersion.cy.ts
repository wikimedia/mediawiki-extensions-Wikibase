import { SpecialVersionPage } from '../support/pageObjects/SpecialVersionPage';

const specialVersionPage = new SpecialVersionPage();

describe( 'Special Version Page', () => {
	it( 'verifies that the Wikibase extension loads', () => {
		specialVersionPage.open().checkWikibaseRepositoryExtensionLoaded();
	} );
} );
