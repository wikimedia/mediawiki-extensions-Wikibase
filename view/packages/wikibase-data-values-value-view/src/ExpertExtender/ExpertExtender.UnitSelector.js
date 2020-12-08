( function( $ ) {
	'use strict';

	require( '../../lib/jquery.ui/jquery.ui.unitsuggester.js' );

	/**
	 * An `ExpertExtender` module for selecting a quantity's unit.
	 *
	 * @class jQuery.valueview.ExpertExtender.UnitSelector
	 * @since 0.15.0
	 * @license GNU GPL v2+
	 *
	 * @constructor
	 *
	 * @param {util.MessageProvider} messageProvider
	 * @param {Function} getUpstreamValue
	 * @param {Function} onValueChange
	 * @param {Object} [options={}]
	 * @param {string|null} [options.language=null]
	 * @param {string|null} [options.vocabularyLookupApiUrl=null]
	 */
	var UnitSelector = function(
		messageProvider,
		getUpstreamValue,
		onValueChange,
		options
	) {
		this._messageProvider = messageProvider;
		this._getUpstreamValue = getUpstreamValue;
		this._onValueChange = onValueChange;
		this._options = options || {};

		this.$selector = $( '<input>' );
	};

	$.extend( UnitSelector.prototype, {
		/**
		 * @property {util.MessageProvider}
		 * @private
		 */
		_messageProvider: null,

		/**
		 * @property {Function}
		 * @private
		 */
		_getUpstreamValue: null,

		/**
		 * @property {Function}
		 * @private
		 */
		_onValueChange: null,

		/**
		 * @property {Object}
		 * @private
		 */
		_options: null,

		/**
		 * @property {jQuery}
		 * @private
		 * @readonly
		 */
		$selector: null,

		/**
		 * Callback for the `init` `ExpertExtender` event.
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			var upstreamValue = this._getUpstreamValue(),
				label = this._messageProvider.getMessage(
					'valueview-expertextender-unitsuggester-label'
				);

			this.$selector.unitsuggester( {
				language: this._options.language || null,
				vocabularyLookupApiUrl: this._options.vocabularyLookupApiUrl || null,
				change: this._onValueChange,
				defaultSelectedUrl: upstreamValue ? upstreamValue.conceptUri : null
			} );

			$extender
				.append( $( '<span>' ).text( label + ' ' ) )
				.append( this.$selector );
		},

		/**
		 * Callback for the `onInitialShow` `ExpertExtender` event.
		 */
		onInitialShow: function() {
			var upstreamValue = this._getUpstreamValue(),
				value = upstreamValue ? upstreamValue.label : null;

			if ( value === '1' ||
				value === 'http://qudt.org/vocab/unit#Unitless' ||
				/^(?:https?:)?\/\/(?:www\.)?wikidata\.org\/\w+\/Q199$/i.test( value )
			) {
				value = null;
			}

			this.$selector.val( value );
		},

		/**
		 * Callback for the `destroy` `ExpertExtender` event.
		 */
		destroy: function() {
			this._messageProvider = null;
			this._getUpstreamValue = null;
			this._onValueChange = null;
			this._options = null;
			this.$selector = null;
		},

		/**
		 * Gets the value currently set in the rotator.
		 *
		 * @return {string|null} The current value
		 */
		getConceptUri: function() {
			var unitSuggester = this.$selector.data( 'unitsuggester' );
			return ( unitSuggester && unitSuggester.getSelectedConceptUri() ) ||
				this.$selector.val();
		}
	} );

	module.exports = UnitSelector;

}( jQuery ) );
