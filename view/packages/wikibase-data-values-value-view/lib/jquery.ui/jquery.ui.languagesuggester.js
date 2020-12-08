( function () {
	'use strict';

var PARENT = $.ui.suggester;

/**
 * @class jQuery.ui.languagesuggester
 * @extends jQuery.ui.suggester
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 *
 * @constructor
 */
$.widget( 'ui.languagesuggester', PARENT, {
	/**
	 * @property {*}
	 * @protected
	 */
	_selectedItem: null,

	/**
	 * @inheritdoc
	 * @protected
	 */
	_initMenu: function( ooMenu ) {
		var self = this,
			retVal = PARENT.prototype._initMenu.apply( this, arguments );

		this.element.on( 'languagesuggesterchange', function ( e, data ) {
			var curVal = self.element.val();
			if ( data.item ) {
				self._selectedItem = data.item;
			} else if (
				self._selectedItem && curVal !== self._selectedItem.getLabel() &&
				curVal !== self._selectedItem.getValue()
			) {
				self._selectedItem = null;
			}
		} );

		$( retVal )
		.on( 'selected.languagesuggester', function( event, item ) {
			self._trigger( 'change', null, { item: item } );
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
			prefixMatcher = new RegExp( '^' + regex, 'i' ),
			promoters = [
				function( item ) { return item.code.toLowerCase() === term.toLowerCase(); },
				function( item ) { return subCodeMatcher.test( item.code ); },
				function( item ) { return prefixMatcher.test( item.label ); },
				function( item ) { return prefixMatcher.test( item.code ); }
			];

		deferred.resolve( $.grep( source, function( item ) {
			return matcher.test( item.code ) || matcher.test( item.label );
		} ).sort( function( a, b ) {
			for ( var i = 0; i < promoters.length; i++ ) {
				var promoterA = promoters[i]( a ),
					promoterB = promoters[i]( b );

				if ( promoterA !== promoterB ) {
					return promoterB - promoterA;
				}
			}

			return self._localeCompare( a.label, b.label );
		} ), term );

		return deferred.promise();
	},

	/**
	 * Instantiates a menu item instance from a suggestion.
	 *
	 * @see jQuery.ui.suggester._createMenuItemFromSuggestion
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
		return this._selectedItem && this._selectedItem.getValue();
	},

	/**
	 * @param {string} value The language code
	 * @param {string} label The label
	 */
	setSelectedValue: function( value, label ) {
		this._selectedItem = this._createMenuItemFromSuggestion( { label: label, code: value } );
		this.element.val( label );
	}
} );

}() );
