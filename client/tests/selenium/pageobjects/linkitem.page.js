'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class LinkItemPage extends Page {
	async open() {
		await super.openTitle( 'Project:LinkItemTest' );
	}

	get addInterlanguageLinks() {
		return $( '#wbc-editpage > a' );
	}

	get linkItemDialog() {
		return $( '#wbclient-linkItem-dialog' );
	}

}

module.exports = new LinkItemPage();
