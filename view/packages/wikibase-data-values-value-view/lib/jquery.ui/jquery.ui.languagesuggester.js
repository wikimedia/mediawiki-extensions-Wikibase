( function( $ ) {
	'use strict';

/**
 * @class jQuery.ui.languagesuggester
 * @extends jQuery.ui.suggester
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 *
 * @constructor
 */
$.widget( 'ui.languagesuggester', $.ui.suggester, {
	/**
	 * @inheritdoc
	 * @protected
	 */
	_getSuggestionsFromArray: function( term, source ) {
		var self = this,
			deferred = $.Deferred(),
			regex = this._escapeRegex( term ),
			matcher = new RegExp( regex, 'i' ),
			promoters = [
				new RegExp( '\\(' + regex + '\\)', 'i' ),
				new RegExp( '\\(' + regex + '-', 'i' ),
				new RegExp( '^' + regex, 'i' ),
				new RegExp( '\\(' + regex, 'i' )
			];

		deferred.resolve( $.grep( source, function( item ) {
			return matcher.test( item );
		} ).sort( function( a, b ) {
			for( var i = 0; i < promoters.length; i++ ) {
				var promoterA = promoters[i].test( a ),
					promoterB = promoters[i].test( b );

				if( promoterA !== promoterB ) {
					return promoterB - promoterA;
				}
			}

			return self._localeCompare( a, b );
		} ), term );

		return deferred.promise();
	},

	/**
	 * @protected
	 *
	 * @param {string} a
	 * @param {string} b
	 * @return {boolean}
	 */
	_localeCompare: function( a, b ) {
		return a.localeCompare
			? a.localeCompare( b )
			: ( a.toUpperCase() < b.toUpperCase() ? -1 : 1 );
	}
} );

}( jQuery ) );
