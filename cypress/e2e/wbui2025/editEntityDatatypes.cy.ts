import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddStatementFormPage } from '../../support/pageObjects/AddStatementFormPage';
import { LoginPage } from '../../support/pageObjects/LoginPage';

describe( 'wbui2025 entityId datatypes (item, property)', () => {

	const createEntityForDatatype = {
		item: ( label: string ) => cy.task( 'MwApi:CreateEntity', {
			entityType: 'item',
			label: label,
			data: { claims: [] },
		} ),
		property: ( label: string ) => cy.task( 'MwApi:CreateEntity', {
			entityType: 'property',
			label: label,
			data: {
				datatype: 'string',
				claims: [],
			},
		} ),
	};

	for ( const datatype of [ 'item', 'property' ] ) {
		context( 'mobile view - ' + datatype + ' datatype', () => {
			let propertyName: string;
			let entityId: string;
			let linkedEntityLabel: string;
			let newLinkedEntityLabel: string;

			before( () => {
				propertyName = Util.getTestString( datatype + '-property' + '-' );
				linkedEntityLabel = Util.getTestString( 'linked-' + datatype + '-' );
				newLinkedEntityLabel = Util.getTestString( 'new-linked-' + datatype + '-' );
				createEntityForDatatype[ datatype ]( linkedEntityLabel );
				createEntityForDatatype[ datatype ]( newLinkedEntityLabel );
				cy.task( 'MwApi:CreateProperty', {
					label: propertyName,
					data: { datatype: 'wikibase-' + datatype },
				} ).then( () => {
					cy.task( 'MwApi:CreateItem', {
						label: Util.getTestString( 'item-with-' + datatype + '-statement' ),
					} ).then( ( newItemId: string ) => {
						entityId = newItemId;
					} );
				} );
			} );

			beforeEach( () => {
				const loginPage = new LoginPage();
				cy.task(
					'MwApi:CreateUser',
					{ usernamePrefix: 'mextest' },
				).then( ( { username, password } ) => {
					loginPage.login( username, password );
				} );
				cy.viewport( 375, 1280 );
			} );

			it( 'allows adding ' + datatype + ' statement to empty item', () => {
				const itemViewPage = new ItemViewPage( entityId );
				itemViewPage.open().statementsSection();

				itemViewPage.addStatementButton().click();

				const addStatementFormPage = new AddStatementFormPage();
				addStatementFormPage.propertyLookup().should( 'exist' );
				addStatementFormPage.setProperty( propertyName );

				addStatementFormPage.publishButton().should( 'be.disabled' );
				addStatementFormPage.snakValueInput().should( 'exist' );

				addStatementFormPage.setSnakValue( linkedEntityLabel );
				addStatementFormPage.snakValueInput().focus();
				addStatementFormPage.selectFirstSnakValueLookupItem();

				addStatementFormPage.publishButton().click();
				addStatementFormPage.form().should( 'not.exist' );

				// TODO: Adding statements to otherwise empty items will be implemented in T406878.
				// This test should be updated at that point.
				// itemViewPage.mainSnakValues().first().should( 'contain.text', linkedEntityLabel );
			} );

			it( 'displays ' + datatype + ' statement and supports full editing workflow', () => {
				const itemViewPage = new ItemViewPage( entityId );
				itemViewPage.open().statementsSection();
				checkA11y( ItemViewPage.STATEMENTS );

				itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
				itemViewPage.editLinks().first().click();

				const editFormPage = new EditStatementFormPage();
				editFormPage.formHeading().should( 'exist' );
				editFormPage.propertyName().should( 'have.text', propertyName );

				editFormPage.lookupComponent()
					.should( 'exist' ).should( 'be.visible' );

				editFormPage.lookupInput()
					.should( 'have.value', linkedEntityLabel );

				editFormPage.lookupInput().clear();
				editFormPage.lookupInput().type( newLinkedEntityLabel );
				editFormPage.lookupInput().focus();

				editFormPage.menu().should( 'be.visible' );

				editFormPage.menuItems().first().click();
				editFormPage.lookupInput().should( 'have.value', newLinkedEntityLabel );

				editFormPage.publishButton().click();

				/* Wait for the form to close, and check the value is changed */
				editFormPage.formHeading().should( 'not.exist' );
				itemViewPage.mainSnakValues().first().should( 'contain.text', newLinkedEntityLabel );

			} );
		} );
	}
} );
