import Chainable = Cypress.Chainable;

export class AddReferenceFormPage {
	public static SELECTORS = {
		HEADING: '.wikibase-wbui2025-add-reference-heading h2',
		PROPERTY_INPUT: '.wikibase-wbui2025-property-lookup input',
		SNAK_VALUE_INPUT: '.wikibase-wbui2025-add-reference-value input',
		ADD_BUTTON: '.wikibase-wbui2025-add-reference-form .cdx-button',
		PROPERTY_OPTIONS: '.wikibase-wbui2025-property-lookup .cdx-menu-item',
	};

	public heading(): Chainable {
		return cy.get( AddReferenceFormPage.SELECTORS.HEADING );
	}

	public propertyInput(): Chainable {
		return cy.get( AddReferenceFormPage.SELECTORS.PROPERTY_INPUT );
	}

	public snakValueInput(): Chainable {
		return cy.get( AddReferenceFormPage.SELECTORS.SNAK_VALUE_INPUT );
	}

	public addButton(): Chainable {
		return cy.get( AddReferenceFormPage.SELECTORS.ADD_BUTTON );
	}

	public setProperty( searchTerm: string ): this {
		this.propertyInput().type( searchTerm );
		cy.get( AddReferenceFormPage.SELECTORS.PROPERTY_OPTIONS ).first().click();
		return this;
	}

	public setSnakValue( inputText: string ): this {
		this.snakValueInput().type( inputText );
		return this;
	}
}
