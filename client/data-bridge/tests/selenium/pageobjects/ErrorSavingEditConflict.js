const DataBridgePage = require( './dataBridge.page' );

class ErrorSavingEditConflict {
	get root() {
		return DataBridgePage.error.$( '.wb-db-error-saving-edit-conflict' );
	}

	get reloadButton() {
		return this.root.$( '.wb-db-error-saving-edit-conflict__reload' );
	}

	isDisplayed() {
		return this.root.isDisplayed();
	}
}

module.exports = new ErrorSavingEditConflict();
