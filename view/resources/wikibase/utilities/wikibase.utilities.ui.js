/**
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function ( mw, wb, $ ) {
	'use strict';

	/**
	 * UI related utilities required by 'Wikibase' extension.
	 * @type {Object}
	 */
	wb.utilities.ui = wb.utilities.ui || {};

	/**
	 * @param {string} messageKey Name of a message for the counter. The message will receive the
	 *  quantity as parameter $1.
	 * @param {number} quantity
	 * @return {jQuery} The formatted counter output.
	 */
	wb.utilities.ui.buildCounter = function ( messageKey, quantity ) {
		return $( '<span/>' )
			// TODO: Legacy name kept for compatibility reasons. It's not "pending" any more.
			.addClass( 'wb-ui-pendingcounter' )
			.text(
				// Messages:
				// wikibase-sitelinks-counter
				// wikibase-statementview-references-counter
				mw.msg( messageKey, mw.language.convertNumber( quantity ) )
			);
	};

}( mediaWiki, wikibase, jQuery ) );
