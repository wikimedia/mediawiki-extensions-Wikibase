import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { AddStatementFormPage } from '../../support/pageObjects/AddStatementFormPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { LoginPage } from '../../support/pageObjects/LoginPage';

describe( 'wbui2025 item view add statement', () => {
	context( 'mobile view', () => {
		let itemViewPage: ItemViewPage;
		const secondPropertyLabel: string = Util.getTestString( 'property' );
		let secondPropertyId: string;

		before( () => {
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					cy.wrap( propertyId ).as( 'propertyId' );
					const statementData = {
						claims: Array.from( Array( 20 ).keys() ).reduce( ( acc, i ) => acc.concat( [ {
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datavalue: {
									value: 'example string ' + i,
									type: 'string',
								},
							},
							type: 'statement',
							rank: 'normal',
						} ] ), [] ),
					};
					cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'item' ), data: statementData } )
						.then( ( itemId: string ) => {
							itemViewPage = new ItemViewPage( itemId );
						} );
					cy.task( 'MwApi:CreateProperty', { label: secondPropertyLabel, datatype: 'string' } )
						.then( ( newPropertyId: string ) => {
							secondPropertyId = newPropertyId;
						} );
				} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
			const loginPage = new LoginPage();
			cy.task(
				'MwApi:CreateUser',
				{ usernamePrefix: 'mextest' },
			).then( ( { username, password } ) => {
				loginPage.login( username, password );
			} );
		} );

		it( 'adds a statement, shows a duplicate warning for an existing property, and tests the floating add statement button', { scrollBehavior: false }, () => {
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );
			itemViewPage.addStatementFloatingDisc().should( 'not.exist' );
			itemViewPage.addStatementButton().scrollIntoView().click();

			const addStatementFormPage = new AddStatementFormPage();
			addStatementFormPage.propertyLookup().should( 'exist' );
			addStatementFormPage.setProperty( secondPropertyId );
			addStatementFormPage.publishButton().should( 'be.disabled' );
			addStatementFormPage.snakValueInput().should( 'exist' );
			addStatementFormPage.setSnakValue( 'some string' );
			addStatementFormPage.publishButton().click();
			addStatementFormPage.form().should( 'not.exist' );
			itemViewPage.assertStatementIsInViewport( secondPropertyId );
			itemViewPage.mainSnakValues().eq( 20 ).should( 'have.text', 'some string' );

			itemViewPage.addStatementButton().scrollIntoView().click();
			addStatementFormPage.propertyLookup().should( 'exist' );
			cy.get( '@propertyId' ).then( ( propertyId ) => {
				addStatementFormPage.setProperty( propertyId );
			} );
			addStatementFormPage.duplicateWarning().should( 'exist' );
			addStatementFormPage.snakValueInput().should( 'not.exist' );
			addStatementFormPage.publishButton().should( 'be.disabled' );
			addStatementFormPage.editExistingStatementButton().click();

			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.formRoot().should( 'exist' );
			editStatementFormPage.cancelButton().click();

			// Scroll to see the floating add statement button and click it twice, then dismiss the form
			itemViewPage.statementsHeading().scrollIntoView();
			itemViewPage.scrollToTopOfStatementWrapper( secondPropertyId );
			itemViewPage.addStatementFloatingDisc().click();
			itemViewPage.addStatementFloatingButton().click();
			addStatementFormPage.cancelButton().click();

			// Click the floating add statement button then cancel it back to a disc
			itemViewPage.addStatementFloatingDisc().click();
			itemViewPage.addStatementFloatingDisc().should( 'not.exist' );
			itemViewPage.addStatementFloatingButtonCloseIcon().click();
			itemViewPage.addStatementFloatingDisc().should( 'be.visible' );
		} );

	} );
} );
