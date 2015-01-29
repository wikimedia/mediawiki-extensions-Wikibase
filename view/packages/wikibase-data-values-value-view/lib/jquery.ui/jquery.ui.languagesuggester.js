( function( $ ) {
	'use strict';

var PARENT = $.ui.suggester;

/**
 * @class jQuery.ui.languagesuggester
 * @extends jQuery.ui.suggester
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 *
 * @constructor
 */
$.widget( 'ui.languagesuggester', PARENT, {
	/**
	 * @property {*}
	 * @protected
	 */
	_selectedValue: null,

	/**
	 * @inheritdoc
	 * @protected
	 */
	_initMenu: function( ooMenu ) {
		var self = this, retVal;

		retVal = PARENT.prototype._initMenu.apply( this, arguments );

		$( retVal )
		.on( 'selected.languagesuggester', function( event, item ) {
			self._selectedValue = item.getValue();
			self.element.val( item.getLabel() );
		} );

		return retVal;
	},

	/**
	 * @inheritdoc
	 * @protected
	 */
	_getSuggestionsFromArray: function( term, source ) {
		var self = this,
			deferred = $.Deferred(),
			regex = this._escapeRegex( term ),
			matcher = new RegExp( regex, 'i' ),
			subCodeMatcher = new RegExp( '^' + regex + '-', 'i' ),
			prefixMatcher =  new RegExp( '^' + regex, 'i' ),
			promoters = [
				function( item ) { return item.code.toLowerCase() === term.toLowerCase(); },
				function( item ) { return subCodeMatcher.test( item.code ); },
				function( item ) { return prefixMatcher.test( item.label ); },
				function( item ) { return prefixMatcher.test( item.code ); },
			];

		deferred.resolve( $.grep( source, function( item ) {
			return matcher.test( item.code ) || matcher.test( item.label );
		} ).sort( function( a, b ) {
			for( var i = 0; i < promoters.length; i++ ) {
				var promoterA = promoters[i]( a ),
					promoterB = promoters[i]( b );

				if( promoterA !== promoterB ) {
					return promoterB - promoterA;
				}
			}

			return self._localeCompare( a.label, b.label );
		} ), term );

		return deferred.promise();
	},

	/**
	 * Instantiates a menu item instance from a suggestion.
	 * @protected
	 *
	 * @param {Object} suggestion
	 * @param {string} requestTerm
	 * @return {jQuery.ui.ooMenu.Item}
	 */
	_createMenuItemFromSuggestion: function( suggestion, requestTerm ) {
		return new $.ui.ooMenu.Item( suggestion.label, suggestion.code );
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
	},

	/**
	 * @return {*}
	 */
	getSelectedValue: function() {
		return this._selectedValue;
	}
} );

}( jQuery ) );
