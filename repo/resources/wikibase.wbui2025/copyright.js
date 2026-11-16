const { api } = require( './api/api.js' );
const copyright = require( 'wikibase.copyright' );

function persistAcceptance() {
	copyright.persistAcceptance( api )();
}

module.exports = {
	getVersion: copyright.getVersion,
	getMessageHtml: copyright.getMessageHtml,
	isAccepted: copyright.isAccepted,
	persistAcceptance
};
