import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddQualifierFormPage } from '../../support/pageObjects/AddQualifierFormPage';

describe( 'wbui2025 add qualifiers', () => {
	context( 'mobile view', () => {
		let itemViewPage: ItemViewPage;
		beforeEach( () => {
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
			cy.viewport( 375, 1280 );
		} );

		it( 'is possible to add a qualifier', () => {
			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.addQualifierButton().click();
			const addQualifierFormPage = new AddQualifierFormPage();
			addQualifierFormPage.heading().should( 'have.text', 'add qualifier' );

			// Before a property is selected
			addQualifierFormPage.addButton().should( 'be.disabled' );
			addQualifierFormPage.snakValueInput().should( 'not.exist' );

			cy.get( '@propertyId' ).then( ( propertyId ) => {
				addQualifierFormPage.setProperty( propertyId );
			} );
			const qualifierSnakValue = Util.getTestString( 'qualifierSnak' );
			addQualifierFormPage.setSnakValue( qualifierSnakValue );
			addQualifierFormPage.addButton().click();

			editStatementFormPage.valueForms().should( 'contain.text', qualifierSnakValue );
			editStatementFormPage.publishButton().click();
			itemViewPage.qualifiersSections().first().then( ( element ) => {
				itemViewPage.qualifiers( element ).should( 'contain.text', qualifierSnakValue );
			} );
		} );
	} );
} );
