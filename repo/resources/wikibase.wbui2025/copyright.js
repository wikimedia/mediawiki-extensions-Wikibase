const { api } = require( './api/api.js' );
const cookieKey = 'wikibase.acknowledgedcopyrightversion';
const optionsKey = 'wb-acknowledgedcopyrightversion';

const { version, messageHtml } = mw.config.exists( 'wbCopyright' ) ?
	mw.config.get( 'wbCopyright' ) : {};

function isAccepted() {
	if ( !version ) {
		return true;
	}
	return (
		mw.cookie.get( cookieKey ) === version ||
			mw.user.options.get( optionsKey ) === version
	);
}

function setCookie() {
	mw.cookie.set(
		cookieKey,
		version,
		{ expires: 3 * 365 * 24 * 60 * 60, path: '/' }
	);
}

function persistAcceptance() {
	if ( mw.user.isAnon() || mw.user.isTemp() ) {
		setCookie();
	} else {
		api.saveOption( optionsKey, version )
			.done( () => mw.user.options.set( optionsKey, version ) )
			.fail( setCookie );
	}
}

module.exports = {
	version,
	messageHtml,
	isAccepted,
	persistAcceptance
};
