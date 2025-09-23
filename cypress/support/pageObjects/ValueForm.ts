import Chainable = Cypress.Chainable;

export class ValueForm {

	public static VALUE_INPUT_SELECTOR = '.cdx-text-input input';

	public static RANK_SELECTOR = '.cdx-select-vue';

	public static RANK_SELECTOR_MENU_ITEM = '.cdx-select-vue .cdx-menu .cdx-menu-item__text__label';

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

}
