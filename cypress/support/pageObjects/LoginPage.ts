import Chainable = Cypress.Chainable;

export class LoginPage {

	private static get LOGIN_FORM_SELECTORS(): Record<string, string> {
		return {
			USERNAME: '#wpName1',
			PASSWORD: '#wpPassword1',
			LOGIN_BUTTON: '#wpLoginAttempt',
		};
	}

	public setUsername( username: string ): Chainable {
		return cy.get( LoginPage.LOGIN_FORM_SELECTORS.USERNAME ).clear().type( username );
	}

	public setPassword( password: string ): Chainable {
		return cy.get( LoginPage.LOGIN_FORM_SELECTORS.PASSWORD ).clear().type( password );
	}

	private getLoginButton(): Chainable {
		return cy.get( LoginPage.LOGIN_FORM_SELECTORS.LOGIN_BUTTON );
	}

	public open(): Chainable {
		return cy.visitTitle( 'Special:UserLogin' );
	}

	public login( username: string, password: string ): Chainable {
		return this.open()
			.then( () => this.setUsername( username ) )
			.then( () => this.setPassword( password ) )
			.then( () => this.getLoginButton().click() );
	}

}
