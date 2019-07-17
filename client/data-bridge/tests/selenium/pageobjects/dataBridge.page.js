const Page = require( 'wdio-mediawiki/Page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	ForwardCompatUtil = require( '../ForwardCompatUtil' );

class DataBridgePage extends Page {
	getDummyTitle() {
		return Util.getTestString( 'Data-bridge-test-page-' );
	}

	open( title ) {
		super.openTitle( title );
		ForwardCompatUtil.waitForModuleState( 'wikibase.client.data-bridge.app' );
	}

	get overloadedLink() {
		return browser.element( 'a=Edit this on Wikidata' );
	}

	get dialog() {
		return browser.element( '.oo-ui-dialog' );
	}

	get app() {
		return this.dialog.element( '#data-bridge-app' );
	}
}

module.exports = new DataBridgePage();
