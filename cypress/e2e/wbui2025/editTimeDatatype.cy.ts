import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddStatementFormPage } from '../../support/pageObjects/AddStatementFormPage';
import { LoginPage } from '../../support/pageObjects/LoginPage';

describe( 'wbui2025 time datatypes', () => {

	context( 'mobile view - time datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let entityId: string;

		before( () => {
			propertyName = Util.getTestString( 'time-property' + '-' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'time' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'item-with-time-statement' ),
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

		it( 'allows adding time statement to empty item, ' +
			'displays statement and supports full editing workflow', () => {
			const itemViewPage = new ItemViewPage( entityId );
			itemViewPage.open().statementsSection();

			itemViewPage.addStatementButton().click();

			const addStatementFormPage = new AddStatementFormPage();
			addStatementFormPage.propertyLookup().should( 'exist' );
			addStatementFormPage.setProperty( propertyId );

			addStatementFormPage.publishButton().should( 'be.disabled' );
			addStatementFormPage.snakValueInput().should( 'exist' );

			addStatementFormPage.setSnakValue( '12 CE' );

			addStatementFormPage.publishButton().click();
			addStatementFormPage.form().should( 'not.exist' );

			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );

			itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
			itemViewPage.editLinks().first().click();

			const editFormPage = new EditStatementFormPage();
			editFormPage.formHeading().should( 'exist' );
			editFormPage.propertyName().should( 'have.text', propertyName );

			addStatementFormPage.snakValueInput().focus();
			addStatementFormPage.setCalendar( 'GREGORIAN' );

			editFormPage.publishButton().click();

			/* Wait for the form to close, and check the value is changed */
			editFormPage.formHeading().should( 'not.exist' );
			itemViewPage.mainSnakValues().first().should(
				'have.html',
				'12 CE<sup class="wb-calendar-name">Gregorian</sup>',
			);

			itemViewPage.editLinks().first().click();
			editFormPage.formHeading().should( 'exist' );

			addStatementFormPage.snakValueInput().should( 'exist' );
			addStatementFormPage.setSnakValue( '156' );
			addStatementFormPage.setPrecision( '7' );

			editFormPage.publishButton().click();

			/* Wait for the form to close, and check the value is changed */
			editFormPage.formHeading().should( 'not.exist' );
			itemViewPage.mainSnakValues().first().should( 'have.html', '2. century' );
		} );
	} );
} );
