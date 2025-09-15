import Chainable = Cypress.Chainable;

export class ValueForm {

	public static VALUE_INPUT_SELECTOR = '.cdx-text-input input';

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

}
