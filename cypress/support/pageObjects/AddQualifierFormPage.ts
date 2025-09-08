import Chainable = Cypress.Chainable;

export class AddQualifierFormPage {
	public static SELECTORS = {
		HEADING: '.wikibase-wbui2025-add-qualifier-heading h2',
		PROPERTY_INPUT: '.wikibase-wbui2025-property-lookup input',
		SNAK_VALUE_INPUT: '.wikibase-wbui2025-add-qualifier-value input',
		ADD_BUTTON: '.wikibase-wbui2025-add-qualifier-form .cdx-button',
	};

	public heading(): Chainable {
		return cy.get( AddQualifierFormPage.SELECTORS.HEADING );
	}

	public propertyInput(): Chainable {
		return cy.get( AddQualifierFormPage.SELECTORS.PROPERTY_INPUT );
	}

	public snakValueInput(): Chainable {
		return cy.get( AddQualifierFormPage.SELECTORS.SNAK_VALUE_INPUT );
	}

	public addButton(): Chainable {
		return cy.get( AddQualifierFormPage.SELECTORS.ADD_BUTTON );
	}

	public setProperty( searchTerm: string ): self {
		this.propertyInput().type( searchTerm );
		cy.get( '.wikibase-wbui2025-property-lookup .cdx-menu-item:first' ).click();
		return this;
	}

	public setSnakValue( inputText: string ): self {
		this.snakValueInput().type( inputText );
		return this;
	}
}
