import Chainable = Cypress.Chainable;

export class EditStatementFormPage {

	public static SELECTORS = {
		PROPERTY_NAME: '.wikibase-wbui2025-property-name > a',
		REMOVE_VALUE_BUTTONS: '.wikibase-wbui2025-remove-value > .cdx-button',
		ADD_VALUE_BUTTONS: '.wikibase-wbui2025-add-value > .cdx-button',
		TEXT_INPUT: '.wikibase-wbui2025-edit-statement-value-input > .cdx-text-input input',
	};

	public static FORM_HEADING = '.wikibase-wbui2025-edit-statement-heading';

	public static VALUE_FORMS = '.wikibase-wbui2025-edit-statement-value-form';

	public static FORM_ACTIONS = '.wikibase-wbui2025-edit-form-actions';

	public propertyName(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.PROPERTY_NAME );
	}

	public formHeading(): Chainable {
		return cy.get( EditStatementFormPage.FORM_HEADING );
	}

	public addValueButtons(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.ADD_VALUE_BUTTONS );
	}

	public removeValueButtons(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.REMOVE_VALUE_BUTTONS );
	}

	public valueForms(): Chainable {
		return cy.get( EditStatementFormPage.VALUE_FORMS );
	}

	public cancelButton(): Chainable {
		return cy.get( EditStatementFormPage.FORM_ACTIONS ).contains( 'cancel' );
	}

	public textInput(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.TEXT_INPUT );
	}
}
