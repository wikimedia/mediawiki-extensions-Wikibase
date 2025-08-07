import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';

describe( 'wbui2025 item view', () => {
	context( 'mobile view', () => {
		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'loads the item view', () => {
			cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'item' ) } )
				.then( ( itemId: string ) => {
					const itemViewPage = new ItemViewPage( itemId );
					itemViewPage.open().statementsSection();
					checkA11y( ItemViewPage.STATEMENTS );
				} );
		} );
	} );
} );
