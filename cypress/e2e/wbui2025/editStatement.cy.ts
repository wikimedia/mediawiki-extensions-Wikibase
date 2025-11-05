import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';

describe( 'wbui2025 item view edit statements', () => {
	context( 'mobile view', () => {
		let propertyName: string;
		let itemId: string;

		before( () => {
			propertyName = Util.getTestString( 'property' );
			cy.task( 'MwApi:CreateProperty', { label: propertyName, data: { datatype: 'string' } } )
				.then( ( newPropertyId: string ) => {
					const statementData = {
						claims: [ {
							mainsnak: {
								snaktype: 'value',
								property: newPropertyId,
								datavalue: {
									value: 'ExampleString',
									type: 'string',
								},
							},
							type: 'statement',
							rank: 'normal',
						} ],
					};
					cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'item' ), data: statementData } )
						.then( ( newItemId: string ) => {
							itemId = newItemId;
						} );
				} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'loads the item view and shows a statement, which can be edited', () => {
			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );
			itemViewPage.editLinks().first().click();
			const editFormPage = new EditStatementFormPage();
			editFormPage.propertyName().should( 'have.text', propertyName );
			editFormPage.textInput().should( 'have.value', 'ExampleString' );
			// Check that the value form is present (i.e. one 'add value' form is present)
			editFormPage.valueForms();
			editFormPage.removeValueButtons().first().click();
			editFormPage.valueForms().should( 'not.exist' );
			editFormPage.addValueButtons().first().click();
			// The form should exist again
			editFormPage.valueForms();
			editFormPage.cancelButton().click();
			editFormPage.formHeading().should( 'not.exist' );
		} );
	} );
} );
