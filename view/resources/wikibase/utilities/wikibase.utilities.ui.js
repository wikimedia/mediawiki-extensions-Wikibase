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
	 * @param {string} kindOfQuantityMessage Message string of a message expressing the nature of
	 *        the quantity, e.g. a message which would return 'items' for displaying something like
	 *        "3 +1 items". The message will receive as parameter $1 the total quantity (fixed +
	 *        pending).
	 * @param {string} pendingQuantityTooltipMessage Message string of a message which will be
	 *        displayed in the tooltip which will be appended to the number of pending quantity.
	 *        Parameter $1 will be the number of pending quantity, $2 will be the fixed quantity
	 *        and $3 the sum of both.
	 * @return {jQuery} The formatted counter output. Does not have a root node, collection of
	 *         multiple DOM elements.
	 */
	wb.utilities.ui.buildPendingCounter = function(
		fixedQuantity, pendingQuantity, kindOfQuantityMessage, pendingQuantityTooltipMessage
	) {
		var fqNumMsg = mw.language.convertNumber( fixedQuantity ),
			pqNumMsg = mw.language.convertNumber( pendingQuantity ),
			tqNumMsg = mw.language.convertNumber( fixedQuantity + pendingQuantity ),
			qTypeLabel = kindOfQuantityMessage ? mw.msg( kindOfQuantityMessage, tqNumMsg ) : '';

		var msg = !pendingQuantity || pendingQuantity === '0'
			? mw.msg( 'wikibase-ui-pendingquantitycounter-nonpending', qTypeLabel, fqNumMsg )
			: mw.msg( 'wikibase-ui-pendingquantitycounter-pending',
				qTypeLabel,
				fqNumMsg,
				'__3__' // can't insert html here since it would be escaped!
			);

		// replace __3__ with a span we can grab next
		var $msg = $( ( '<span>' + msg + '</span>' ).replace( /__3__/g, '<span/>' ) ),
			$msgSpan = $msg.children( 'span' );

		if ( $msgSpan.length > 0 ) {
			$msgSpan.attr(
				'title', // the message displayed in the tooltip
				mw.msg( pendingQuantityTooltipMessage, pqNumMsg, fqNumMsg, tqNumMsg )
			);
			$msgSpan.text( // the '+1' part, displaying the pending quantity
				mw.msg( 'wikibase-ui-pendingquantitycounter-pending-pendingsubpart', pqNumMsg )
			);
			$msgSpan.tipsy( {
				gravity: ( IS_RTL ? 'ne' : 'nw' )
			} );
		}

		$msg.addClass( 'wb-ui-pendingcounter' );
		return $msg;
	};

}( mediaWiki, wikibase, jQuery ) );
