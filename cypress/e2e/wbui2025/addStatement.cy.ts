import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { AddStatementFormPage } from '../../support/pageObjects/AddStatementFormPage';

describe( 'wbui2025 item view add statement', () => {
	context( 'mobile view', () => {
		let itemViewPage: ItemViewPage;

		before( () => {
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					cy.wrap( propertyId ).as( 'propertyId' );
					const statementData = {
						claims: [ {
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datavalue: {
									value: 'example string value',
									type: 'string',
								},
							},
							type: 'statement',
							rank: 'normal',
						} ],
					};
					cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'item' ), data: statementData } )
						.then( ( itemId: string ) => {
							itemViewPage = new ItemViewPage( itemId );
						} );
				} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'loads the item view and shows property selector', () => {
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );
			itemViewPage.addStatementButton().click();

			const addStatementFormPage = new AddStatementFormPage();
			addStatementFormPage.propertyLookup().should( 'exist' );
			cy.get<string>( '@propertyId' ).then( ( propertyId ) => {
				addStatementFormPage.setProperty( propertyId );
			} );
			addStatementFormPage.publishButton().should( 'be.disabled' );
			addStatementFormPage.snakValueInput().should( 'exist' );
			addStatementFormPage.setSnakValue( 'some string' );
			addStatementFormPage.publishButton().click();
			addStatementFormPage.form().should( 'not.exist' );
			itemViewPage.mainSnakValues().eq( 1 ).should( 'have.text', 'some string' );
		} );

	} );
} );
