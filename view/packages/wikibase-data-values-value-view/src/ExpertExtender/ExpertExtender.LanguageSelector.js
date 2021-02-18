( function( $, PrefixingMessageProvider ) {
	'use strict';

	/**
	 * An `ExpertExtender` module for selecting a language.
	 *
	 * @class jQuery.valueview.ExpertExtender.LanguageSelector
	 * @since 0.6
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {util.ContentLanguages} contentLanguages
	 * @param {util.MessageProvider} messageProvider
	 * @param {Function} getUpstreamValue
	 * @param {Function} onValueChange
	 */
	var LanguageSelector = function(
		contentLanguages,
		messageProvider,
		getUpstreamValue,
		onValueChange
	) {
		this._contentLanguages = contentLanguages;
		this._messageProvider = new PrefixingMessageProvider(
			this._prefix + '-',
			messageProvider
		);
		this._getUpstreamValue = getUpstreamValue;
		this._onValueChange = onValueChange;

		this.$selector = $( '<input />' );
	};

	$.extend( LanguageSelector.prototype, {
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
		 * @property {util.ContentLanguages}
		 * @private
		 */
		_contentLanguages: null,

		/**
		 * @property {Object}
		 * @private
		 */
		_labels: null,

		/**
		 * @property {jQuery}
		 * @private
		 * @readonly
		 */
		$selector: null,

		/**
		 * @property {string} [_prefix='valueview-expertextender-languageselector']
		 * @private
		 */
		_prefix: 'valueview-expertextender-languageselector',

		/**
		 * @private
		 */
		_initLabels: function() {
			var languages = this._contentLanguages.getAll();

			var self = this;

			if ( languages !== null ) {
				this._labels = {};
				$.each( languages, function( i, code ) {
					var languageName = self._contentLanguages.getName( code );

					self._labels[code] = languageName ? self._messageProvider.getMessage(
						'languagetemplate',
						[ languageName, code ]
					) : code;
				} );
			}
		},

		/**
		 * Callback for the `init` `ExpertExtender` event.
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			this._initLabels();

			if ( this._labels ) {
				this.$selector.languagesuggester( {
					source: $.map( this._labels, function( label, code ) {
						return { code: code, label: label };
					} )
				} ).on( 'languagesuggesterchange', this._onValueChange );
			} else {
				this.$selector.on( 'eachchange', this._onValueChange );
			}
			$extender
				.append( $( '<span />' ).text( this._messageProvider.getMessage( 'label' ) + ' ' ) )
				.append( this.$selector );
		},

		/**
		 * Callback for the `onInitialShow` `ExpertExtender` event.
		 */
		onInitialShow: function() {
			var value = this._getUpstreamValue();
			if ( this._labels && value ) {
				this.$selector.data( 'languagesuggester' ).setSelectedValue( value, this._labels[ value ] || value );
			} else {
				this.$selector.val( value );
			}
		},

		/**
		 * Callback for the `destroy` `ExpertExtender` event.
		 */
		destroy: function() {
			this._getUpstreamValue = null;
			this.$selector = null;
			this._contentLanguages = null;
			this._labels = null;
			this._messageProvider = null;
			this._onValueChange = null;
		},

		/**
		 * Gets the value currently set in the rotator.
		 *
		 * @return {string|null} The current value
		 */
		getValue: function() {
			var languageSuggester = this.$selector.data( 'languagesuggester' );
			var selectedMenuValue = languageSuggester && languageSuggester.getSelectedValue();
			return selectedMenuValue || this.$selector.val();
		}
	} );

	module.exports = LanguageSelector;

}( jQuery, util.PrefixingMessageProvider ) );
