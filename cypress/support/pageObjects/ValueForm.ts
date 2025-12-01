import Chainable = Cypress.Chainable;

export class ValueForm {

	public static VALUE_INPUT_SELECTOR = '.wikibase-wbui2025-edit-statement-value-input .cdx-text-input input';

	public static QUALIFIER_INPUT_SELECTOR = '.wikibase-wbui2025-qualifiers .cdx-text-input input';

	public static QUALIFIER_REMOVE_BUTTON = '.wikibase-wbui2025-qualifiers button.cdx-button';

	public static RANK_SELECTOR = '.cdx-select-vue';

	public static RANK_SELECTOR_MENU_ITEM = '.cdx-select-vue .cdx-menu .cdx-menu-item__text__label';

	public static SNAK_TYPE_SELECTOR = '.cdx-menu-button button';

	public static SNAK_TYPE_SELECTOR_MENU_ITEM = '.cdx-menu-button__menu-wrapper .cdx-menu .cdx-menu-item__text__label';

	public static NO_VALUE_SOME_VALUE_PLACEHOLDER = '.wikibase-wbui2025-novalue-somevalue-holder';

	public static TEXT_INPUT = '.cdx-text-input input';

	public static ADD_SNAK_BUTTON = 'button.wikibase-wbui2025-add-snak-button';

	public static REMOVE_SNAK_BUTTON = '.wikibase-wbui2025-remove-snak';

	public static REMOVE_REFERENCE_BUTTON = '.wikibase-wbui2025-editable-reference-remove-button-holder button';

	public static PROPERTY_LOOKUP = '.wikibase-wbui2025-property-lookup';

	public static LOOKUP_ITEM = '.cdx-menu .cdx-menu-item';

	private rootElement: HTMLElement;

	public constructor( rootElement: HTMLElement ) {
		this.rootElement = rootElement;
	}

	public valueInput(): Chainable {
		return cy.get( ValueForm.VALUE_INPUT_SELECTOR, { withinSubject: this.rootElement } ).first();
	}

	public setValueInput( newInputValue: string ): Chainable {
		return this.valueInput().clear().type( newInputValue );
	}

	public qualifierInputs(): Chainable {
		return cy.get( ValueForm.QUALIFIER_INPUT_SELECTOR, { withinSubject: this.rootElement } );
	}

	public qualifierRemoveButtons(): Chainable {
		return cy.get( ValueForm.QUALIFIER_REMOVE_BUTTON, { withinSubject: this.rootElement } );
	}

	public setRank( rank: string ): Chainable {
		return cy.get( ValueForm.RANK_SELECTOR, { withinSubject: this.rootElement } ).click()
			.then( () => cy.get(
				ValueForm.RANK_SELECTOR_MENU_ITEM, { withinSubject: this.rootElement },
			).contains( rank ).click() );
	}

	public setSnakType( snakType: string ): Chainable {
		return cy.get( ValueForm.SNAK_TYPE_SELECTOR, { withinSubject: this.rootElement } ).click()
			.then( () => cy.get(
				ValueForm.SNAK_TYPE_SELECTOR_MENU_ITEM, { withinSubject: this.rootElement },
			).contains( snakType ).click() );
	}

	public noValueSomeValuePlaceholder(): Chainable {
		return cy.get( ValueForm.NO_VALUE_SOME_VALUE_PLACEHOLDER, { withinSubject: this.rootElement } );
	}

	public textInput(): Chainable {
		return cy.get( ValueForm.TEXT_INPUT, { withinSubject: this.rootElement } );
	}

	public addSnakButton(): Chainable {
		return cy.get( ValueForm.ADD_SNAK_BUTTON, { withinSubject: this.rootElement } );
	}

	public removeSnakButton(): Chainable {
		return cy.get( ValueForm.REMOVE_SNAK_BUTTON, { withinSubject: this.rootElement } );
	}

	public removeReferenceButton(): Chainable {
		return cy.get( ValueForm.REMOVE_REFERENCE_BUTTON, { withinSubject: this.rootElement } );
	}

	public propertyLookup(): Chainable {
		return cy.get( ValueForm.PROPERTY_LOOKUP, { withinSubject: this.rootElement } );
	}

	public selectPropertyFromLookup( propertyLabel: string ): this {
		this.propertyLookup().within( () => {
			cy.get( ValueForm.TEXT_INPUT )
				.should( 'be.visible' )
				.invoke( 'val', propertyLabel );
			cy.get( ValueForm.TEXT_INPUT ).type( '{moveToEnd}{backspace}' );
			cy.contains( ValueForm.LOOKUP_ITEM, propertyLabel ).click();
			return this;
		} );
	}
}
