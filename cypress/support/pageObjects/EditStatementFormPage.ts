import Chainable = Cypress.Chainable;

export class EditStatementFormPage {

	public static SELECTORS = {
		MODAL_ROOT: '.wikibase-wbui2025-edit-statement-group-modal',
		MODAL_CONTENT: '.wikibase-wbui2025-modal-overlay__content',
		MODAL_HEADER: '.wikibase-wbui2025-modal-overlay__header',
		PROPERTY_NAME: '.wikibase-wbui2025-property-name > a',
		REMOVE_VALUE_BUTTONS: '.wikibase-wbui2025-remove-value > .cdx-button',
		ADD_VALUE_BUTTONS: '.wikibase-wbui2025-add-value > .cdx-button',
		FOOTER_BUTTONS: '.wikibase-wbui2025-modal-overlay__footer__actions > .cdx-button',
		ADD_QUALIFIER_BUTTON: '.wikibase-wbui2025-add-qualifier-button',
		TEXT_INPUT:
			'.wikibase-wbui2025-edit-statement-value-input .cdx-text-input input, .wikibase-wbui2025-edit-statement-value-input textarea',
		LOOKUP_INPUT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup input',
		LOOKUP_COMPONENT: '.wikibase-wbui2025-edit-statement-value-input .cdx-lookup',
		MENU: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu',
		MENU_ITEM: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu-item',
		MENU_ITEM_THUMBNAIL: '.wikibase-wbui2025-edit-statement-value-input .cdx-menu-item .cdx-thumbnail',
		RANK_SELECT: '.wikibase-wbui2025-rank-input .cdx-select-vue',
		SNAK_TYPE_SELECT: '.wikibase-wbui2025-edit-statement-value-input .wikibase-snaktypeselector .cdx-menu-button',
		ADD_REFERENCE_BUTTON: '.wikibase-wbui2025-add-reference-button',
		REFERENCES: '.wikibase-wbui2025-editable-reference',
		REFERENCE_SNAKS: '.wikibase-wbui2025-editable-reference-snak',
		REFERENCES_ACCORDION: '.wikibase-wbui2025-editable-references-section .cdx-accordion summary',
		VALUE_FORMS: '.wikibase-wbui2025-edit-statement-value-form',
	};

	public propertyName(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.PROPERTY_NAME );
	}

	public formHeading(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MODAL_HEADER );
	}

	public addQualifierButton(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.ADD_QUALIFIER_BUTTON );
	}

	public textInput(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.TEXT_INPUT ).first();
	}

	public publishButton(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.FOOTER_BUTTONS ).last();
	}

	public cancelButton(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.FOOTER_BUTTONS ).first();
	}

	public lookupInput(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.LOOKUP_INPUT );
	}

	public lookupComponent(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.LOOKUP_COMPONENT );
	}

	public menu(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU );
	}

	public rankSelect(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.RANK_SELECT );
	}

	public snakTypeSelect(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.SNAK_TYPE_SELECT );
	}

	public menuItems(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU_ITEM ).filter( ':visible' );
	}

	public menuItemByName( name: string ): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU_ITEM )
			.filter( ':visible' ).contains( name ).first();
	}

	public menuItemThumbnails(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.MENU_ITEM_THUMBNAIL );
	}

	public references(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.REFERENCES );
	}

	public referenceSnaks(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.REFERENCE_SNAKS );
	}

	public referencesAccordion(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.REFERENCES_ACCORDION );
	}

	public addReferenceButton(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.ADD_REFERENCE_BUTTON );
	}

	public root(): Chainable {
		return cy.get( EditStatementFormPage.SELECTORS.MODAL_ROOT );
	}

	public valueForms(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.VALUE_FORMS );
	}

	public addValueButtons(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.ADD_VALUE_BUTTONS );
	}

	public removeValueButtons(): Chainable {
		return this.root().find( EditStatementFormPage.SELECTORS.REMOVE_VALUE_BUTTONS );
	}

}
