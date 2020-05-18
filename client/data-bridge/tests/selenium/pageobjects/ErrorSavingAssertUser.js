const DataBridgePage = require( './dataBridge.page' );

class ErrorSavingAssertUser {
	get root() {
		return DataBridgePage.error.$( '.wb-db-error-saving-assertuser' );
	}

	get proceedButton() {
		return this.root.$( '.wb-db-error-saving-assertuser__proceed' );
	}

	get loginButton() {
		return this.root.$( '.wb-db-error-saving-assertuser__login' );
	}

	get backButton() { // only visible on desktop
		return this.root.$( '.wb-db-error-saving-assertuser__back' );
	}

	isDisplayed() {
		return this.root.isDisplayed();
	}

	clickBackButton() {
		if ( this.backButton.isDisplayed() ) {
			this.backButton.click();
		} else {
			DataBridgePage.headerBackButton.click();
		}
	}
}

module.exports = new ErrorSavingAssertUser();
