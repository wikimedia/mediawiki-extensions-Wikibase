/**
 * Handling of the copyright license acceptance status and message
 *
 * The code for copyright license acceptance is the same between wbui2025 and the Wikibase
 * desktop interface. This library contains the common code to reduce potential duplication.
 *
 * @license GPL-2.0-or-later
 */
const cookieKey = 'wikibase.acknowledgedcopyrightversion';
const optionsKey = 'wb-acknowledgedcopyrightversion';

function getConfig() {
	return mw.config.exists( 'wbCopyright' ) ?
		mw.config.get( 'wbCopyright' ) : {};
}

function getVersion() {
	return getConfig().version;
}

function getMessageHtml() {
	return getConfig().messageHtml;
}

function isAccepted() {
	if ( !getVersion() ) {
		return true;
	}
	return (
		mw.cookie.get( cookieKey ) === getVersion() ||
		( mw.user && mw.user.options && mw.user.options.get( optionsKey ) === getVersion() )
	);
}

function setCookie() {
	mw.cookie.set(
		cookieKey,
		getVersion(),
		{ expires: 3 * 365 * 24 * 60 * 60, path: '/' }
	);
}

function persistAcceptance( api ) {
	return () => {
		if ( mw.user.isNamed() ) {
			api.saveOption( optionsKey, getVersion() )
				.done( () => mw.user.options.set( optionsKey, getVersion() ) )
				.fail( setCookie );
		} else {
			setCookie();
		}
	};
}

module.exports = {
	getVersion,
	getMessageHtml,
	isAccepted,
	persistAcceptance
};
