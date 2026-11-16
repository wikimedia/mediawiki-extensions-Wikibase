/**
 * Floating OOUI message used as a toast; only one is shown at a time.
 *
 * @license GPL-2.0-or-later
 */
function hide() {
	$( '.wikibase-toast' ).remove();
}

/**
 * @param {string} type 'success' auto-hides after 4 seconds, 'error' stays until closed
 * @param {string} label
 */
function show( type, label ) {
	hide();
	const $toast = new OO.ui.MessageWidget( { type, label, showClose: true } )
		.$element.addClass( 'wikibase-toast' ).appendTo( document.body );
	if ( type === 'success' ) {
		window.setTimeout( () => $toast.remove(), 4000 );
	}
}

module.exports = { show, hide };
