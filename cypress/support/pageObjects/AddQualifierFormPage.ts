import Chainable = Cypress.Chainable;

export class AddQualifierFormPage {
	public static SELECTORS = {
		MODAL_ROOT: '.wikibase-wbui2025-add-qualifier-modal',
		HEADING: '.wikibase-wbui2025-modal-overlay__header__title-group h2',
		PROPERTY_INPUT: '.wikibase-wbui2025-property-lookup input',
		SNAK_VALUE_INPUT: '.wikibase-wbui2025-add-qualifier-value input, .wikibase-wbui2025-add-qualifier-value textarea',
		SNAK_VALUE_LOOKUP: '.wikibase-wbui2025-add-qualifier-value.cdx-lookup',
		SNAK_VALUE_TEXT_INPUT: '.wikibase-wbui2025-add-qualifier-value.cdx-text-input',
		SNAK_VALUE_LOOKUP_INPUT: '.wikibase-wbui2025-add-qualifier-value .cdx-lookup__input input',
		SNAK_VALUE_MENU_ITEMS: '.wikibase-wbui2025-add-qualifier-value .cdx-menu-item',
		MENU: '.cdx-menu',
		MENU_ITEM: '.wikibase-wbui2025-add-qualifier-form .cdx-menu-item',
		ADD_BUTTON: '.wikibase-wbui2025-add-qualifier-button > .cdx-button',
		PROPERTY_OPTIONS: '.wikibase-wbui2025-property-lookup .cdx-menu-item',
	};

	public root(): Chainable {
		return cy.get( AddQualifierFormPage.SELECTORS.MODAL_ROOT );
	}

	public heading(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.HEADING );
	}

	public propertyInput(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.PROPERTY_INPUT );
	}

	public menuItems(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.MENU_ITEM ).filter( ':visible' );
	}

	public snakValueInput(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.SNAK_VALUE_INPUT );
	}

	public snakValueLookup(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.SNAK_VALUE_LOOKUP );
	}

	public snakValueLookupInput(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.SNAK_VALUE_LOOKUP_INPUT );
	}

	public snakValueMenuItems(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.SNAK_VALUE_MENU_ITEMS );
	}

	public menu(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.MENU );
	}

	public addButton(): Chainable {
		return this.root().find( AddQualifierFormPage.SELECTORS.ADD_BUTTON );
	}

	public setProperty( searchTerm: string ): this {
		this.propertyInput().type( searchTerm );
		this.root().find( AddQualifierFormPage.SELECTORS.PROPERTY_OPTIONS ).first().click();
		return this;
	}

	public setSnakValue( inputText: string ): this {
		this.snakValueInput().clear();
		this.snakValueInput().click();
		this.snakValueInput().type( inputText );
		return this;
	}
}
