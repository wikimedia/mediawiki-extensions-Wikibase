import Chainable = Cypress.Chainable;

export class ValueForm {

	public static VALUE_INPUT_SELECTOR = '.cdx-text-input input';

	public static RANK_SELECTOR = '.cdx-select-vue';

	public static RANK_SELECTOR_MENU_ITEM = '.cdx-select-vue .cdx-menu .cdx-menu-item__text__label';

	public static SNAK_TYPE_SELECTOR = '.cdx-menu-button button';

	public static SNAK_TYPE_SELECTOR_MENU_ITEM = '.cdx-menu-button__menu-wrapper .cdx-menu .cdx-menu-item__text__label';

	public static NO_VALUE_SOME_VALUE_PLACEHOLDER = '.wikibase-wbui2025-novalue-somevalue-holder';

	private rootElement: HTMLElement;

	public constructor( rootElement: HTMLElement ) {
		this.rootElement = rootElement;
	}

	public valueInput(): Chainable {
		return cy.get( ValueForm.VALUE_INPUT_SELECTOR, { withinSubject: this.rootElement } );
	}

	public setValueInput( newInputValue: string ): Chainable {
		return this.valueInput().clear().type( newInputValue );
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
}
