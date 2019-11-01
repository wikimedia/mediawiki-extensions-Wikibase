/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function () {
	'use strict';

	/**
	 * @param {string} messageKey Name of a message for the counter. The message will receive the
	 *  quantity as parameter $1.
	 * @param {number} quantity
	 * @return {jQuery} The formatted counter output.
	 */
	module.exports = function ( messageKey, quantity ) {
		return $( '<span>' )
			// TODO: Legacy name kept for compatibility reasons. It's not "pending" any more.
			.addClass( 'wb-ui-pendingcounter' )
			.text(
				// Messages used here:
				// * wikibase-sitelinks-counter
				// * wikibase-statementview-references-counter
				mw.msg( messageKey, mw.language.convertNumber( quantity ) )
			);
	};

}() );
