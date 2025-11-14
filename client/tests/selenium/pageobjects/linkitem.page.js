import Page from 'wdio-mediawiki/Page.js';

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

export default new LinkItemPage();
