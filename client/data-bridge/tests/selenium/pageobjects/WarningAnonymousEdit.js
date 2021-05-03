class WarningAnonymousEdit {
	get root() {
		// #data-bridge-app duplicates DataBridgePage.app to avoid cyclic dependency
		return $( '#data-bridge-app .wb-db-warning-anonymous-edit' );
	}

	get proceedButton() {
		return this.root.$( '.wb-db-warning-anonymous-edit__proceed' );
	}

	get loginButton() {
		return this.root.$( '.wb-db-warning-anonymous-edit__login' );
	}

	dismiss() {
		if ( this.root.isDisplayed() ) {
			this.proceedButton.click();
			this.root.waitForDisplayed( {
				reverse: true,
			} );
		}
	}
}

module.exports = new WarningAnonymousEdit();
