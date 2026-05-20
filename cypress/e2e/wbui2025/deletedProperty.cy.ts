import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';

describe( 'wbui2025 deleted property', () => {
	context( 'mobile view', () => {
		let propertyId: string;
		let itemId: string;

		before( () => {
			cy.task( 'MwApi:CreateProperty', {
				label: Util.getTestString( 'property' ),
				data: { datatype: 'string' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				const statementData = {
					claims: [ {
						mainsnak: {
							snaktype: 'value',
							property: newPropertyId,
							datavalue: {
								value: 'some string value',
								type: 'string',
							},
						},
						type: 'statement',
						rank: 'normal',
					} ],
				};
				// TODO: T427281 (follow-up), we might need to adjust this test to check for any deleted properties and use it
				// instead of creating and deleting the property within the test setup.
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'item' ),
					data: statementData,
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					cy.task( 'MwApi:DeletePage', {
						title: 'Property:' + newPropertyId,
					} );
				} );
			} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'shows the property name with the deleted modifier class with the error message and does not open the edit modal', () => {
			const page = new ItemViewPage( itemId );
			page.open().statementsSection();

			page.deletedPropertyName()
				.should( 'exist' )
				.and( 'contain.text', propertyId );

			page.mainSnakValues()
				.should( 'contain.text', 'some string value' );

			page.invalidSnakValueError()
				.should( 'contain.text', propertyId )
				.and( 'have.css', 'display', 'block' );

			page.editLinksDeletedProperty()
				.should( 'exist' )
				.click();

			const editForm = new EditStatementFormPage();
			editForm.formRoot().should( 'not.exist' );
		} );
	} );
} );
