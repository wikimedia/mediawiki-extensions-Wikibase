( function( wb, $, mw ) {
	'use strict';

	/**
	 * Generates standardized output for errors.
	 *
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @param {Error} error
	 * @param {Object} [animationOptions={ duration: 'fast' }] jQuery animation options.
	 * @return {jQuery}
	 */
	wb.buildErrorOutput = function( error, animationOptions ) {
		var $message = $( '<div/>' ).addClass( 'wb-error' );

		$message.append( $( '<div/>' ).addClass( 'wb-error-message' ).text( error.message ) );

		// Append detailed error message if given; hide it behind toggle:
		if ( error.detailedMessage ) {
			var $detailedMessage = $( '<div/>', {
				'class': 'wb-error-details',
				html: error.detailedMessage
			} )
			.hide();

			var $toggler = $( '<a/>' )
				.addClass( 'wb-error-details-link' )
				.text( mw.msg( 'wikibase-tooltip-error-details' ) )
				.toggler( $.extend( {
					$subject: $detailedMessage,
					duration: 'fast'
				}, animationOptions || {} ) );

			$message
			.append( $toggler )
			.append( $detailedMessage );
		}

		return $message;
	};

}( wikibase, jQuery, mediaWiki ) );
