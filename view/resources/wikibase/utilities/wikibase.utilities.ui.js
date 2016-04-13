/**
 *
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * Whether page has rtl context.
	 * @type {boolean}
	 */
	var IS_RTL = false;

	$( document ).ready( function() {
		// have to wait for document to be loaded for this, otherwise 'rtl' might not yet be there!
		IS_RTL = $( 'body' ).hasClass( 'rtl' );
	} );

	/**
	 * UI related utilities required by 'Wikibase' extension.
	 * @type {Object}
	 */
	wb.utilities.ui = {};

	/**
	 * Creates a counter suited for displaying a number of a fixed quantity plus a number of a
	 * pending quantity whereas the quantity can be 0 or higher. If the pending quantity is 0, it
	 * will not be shown and only the fixed quantity will be displayed, otherwise it will be
	 * displayed as "fixedQuantity +pendingQuantity kindOfQuantity", e.g. "32 +2"
	 *
	 * @since 0.4
	 *
	 * @param {number} fixedQuantity
	 * @param {number} pendingQuantity
	 * @param {string} mainMessage Message name of a message for the whole counter. The message
	 *        will receive as parameter $1 the fixed quantity, and as parameter $2 the number of
	 *        pending items.
	 * @param {string} pendingQuantityTooltipMessage Message string of a message which will be
	 *        displayed in the tooltip which will be appended to the number of pending quantity.
	 *        Parameter $1 will be the number of pending quantity.
	 * @return {jQuery} The formatted counter output. Does not have a root node, collection of
	 *         multiple DOM elements.
	 */
	wb.utilities.ui.buildPendingCounter = function(
		fixedQuantity, pendingQuantity, mainMessage, pendingQuantityTooltipMessage
	) {
		var fqNumMsg = mw.language.convertNumber( fixedQuantity ),
			pqNumMsg = mw.language.convertNumber( pendingQuantity );

		var $msg = $( '<span/>' ).html(
			mw.message( mainMessage, fqNumMsg, pqNumMsg, '<tooltip>', '</tooltip>' )
				.escaped()
				.replace( /&lt;tooltip&gt;(.*?)&lt;\/tooltip&gt;/g, '<span>$1</span>' )
		);
		var $msgSpan = $msg.children( 'span' );

		if ( $msgSpan.length > 0 ) {
			$msgSpan.attr(
				'title', // the message displayed in the tooltip
				mw.msg( pendingQuantityTooltipMessage, pqNumMsg )
			);
			$msgSpan.tipsy( {
				gravity: ( IS_RTL ? 'ne' : 'nw' )
			} );
		}

		$msg.addClass( 'wb-ui-pendingcounter' );
		return $msg;
	};

}( mediaWiki, wikibase, jQuery ) );
