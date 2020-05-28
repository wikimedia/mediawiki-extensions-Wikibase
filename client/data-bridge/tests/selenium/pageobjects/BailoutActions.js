const DataBridgePage = require( './dataBridge.page' );

class BailoutActions {
	get root() {
		return DataBridgePage.error.$( '.wb-db-bailout-actions' );
	}

	get suggestionGoToRepo() {
		return this.root.$( '.wb-db-bailout-actions__suggestion:nth-child(1)' );
	}

	get suggestionEditArticle() {
		return this.root.$( '.wb-db-bailout-actions__suggestion:nth-child(2)' );
	}
}

module.exports = new BailoutActions();
