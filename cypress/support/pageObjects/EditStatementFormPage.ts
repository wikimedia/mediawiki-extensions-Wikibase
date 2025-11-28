import Chainable = Cypress.Chainable;

export class EditStatementFormPage {

	public static SELECTORS = {
		FORM: '.wikibase-wbui2025-edit-statement',
		PROPERTY_NAME: '.wikibase-wbui2025-property-name > a',
		REMOVE_VALUE_BUTTONS: '.wikibase-wbui2025-remove-value > .cdx-button',
		ADD_VALUE_BUTTONS: '.wikibase-wbui2025-add-value > .cdx-button',
		SUBMIT_BUTTONS: '.wikibase-wbui2025-edit-form-actions > .cdx-button',
		ADD_QUALIFIER_BUTTON: '.wikibase-wbui2025-add-qualifier-button',
		TEXT_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-text-input input',
		LOOKUP_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup input',
		LOOKUP_COMPONENT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup',
		MENU: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu',
		MENU_ITEM: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu-item',
		RANK_SELECT: '.wikibase-wbui2025-rank-input .cdx-select-vue',
		SNAK_TYPE_SELECT: '.wikibase-wbui2025-edit-statement-value-input .wikibase-snaktypeselector .cdx-menu-button',
		ADD_REFERENCE_BUTTON: '.wikibase-wbui2025-add-reference-button',
		REFERENCES: '.wikibase-wbui2025-editable-reference',
		REFERENCES_ACCORDION: '.wikibase-wbui2025-editable-references-section .cdx-accordion summary',
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

	public addQualifierButton(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.ADD_QUALIFIER_BUTTON );
	}

	public textInput(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.TEXT_INPUT ).first();
	}

	public publishButton(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).last();
	}

	public cancelButton(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).first();
	}

	public lookupInput(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.LOOKUP_INPUT );
	}

	public lookupComponent(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.LOOKUP_COMPONENT );
	}

	public menu(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.MENU );
	}

	public rankSelect(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.RANK_SELECT );
	}

	public snakTypeSelect(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.SNAK_TYPE_SELECT );
	}

	public menuItems(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.MENU_ITEM ).filter( ':visible' );
	}

	public references(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.REFERENCES );
	}

	public referencesAccordion(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.REFERENCES_ACCORDION );
	}

	public getLookupComponentSelector(): string {
		return EditStatementFormPage.SELECTORS.LOOKUP_COMPONENT;
	}

	public getLookupInputSelector(): string {
		return EditStatementFormPage.SELECTORS.LOOKUP_INPUT;
	}

	public addReferenceButton(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.ADD_REFERENCE_BUTTON );
	}

	public form(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.FORM );
	}
}
