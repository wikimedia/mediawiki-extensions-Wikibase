( function () {
	'use strict';

	/**
	 * Generates standardized output for errors.
	 *
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @param {Error} error
	 * @return {jQuery}
	 */
	var buildErrorOutput = function ( error ) {
		var $message = $( '<div>' ).addClass( 'wb-error' );

		$message.append( $( '<div>' ).addClass( 'wb-error-message' ).text( error.message ) );

		if ( error.detailedMessage ) {
			$message.append(
				$( '<p>' ).addClass( 'wb-error-details' ).html( error.detailedMessage )
			);
		}

		return $message;
	};

	module.exports = buildErrorOutput;
}() );
