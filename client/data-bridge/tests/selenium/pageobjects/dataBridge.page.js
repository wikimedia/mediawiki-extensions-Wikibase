const Page = require( 'wdio-mediawiki/Page' ),
	Util = require( 'wdio-mediawiki/Util' ),
	ForwardCompatUtil = require( '../ForwardCompatUtil' );

class DataBridgePage extends Page {
	static get OOUI() {
		return '.oo-ui-dialog';
	}

	static get ROOT() {
		return '#data-bridge-app';
	}

	static get ROOT_SWITCH() {
		return {
			INIT: '.wb-db-init',
			ERROR: '.wb-db-error',
			BRIDGE: '.wb-db-bridge',
		};
	}

	getDummyTitle() {
		return Util.getTestString( 'Talk:Data-bridge-test-page-' );
	}

	open( title ) {
		super.openTitle( title );
		ForwardCompatUtil.waitForModuleState( 'wikibase.client.data-bridge.app' );
	}

	get overloadedLink() {
		return $( 'a=Edit this on Wikidata' );
	}

	get dialog() {
		return $( '.oo-ui-dialog' );
	}

	get app() {
		return $( '#data-bridge-app' );
	}

	get int() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.INIT}`
		);
	}

	get error() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.ERROR}`
		);
	}

	get bridge() {
		return $(
			`${DataBridgePage.OOUI} ${DataBridgePage.ROOT} ${DataBridgePage.ROOT_SWITCH.BRIDGE}`
		);
	}
}

module.exports = new DataBridgePage();
