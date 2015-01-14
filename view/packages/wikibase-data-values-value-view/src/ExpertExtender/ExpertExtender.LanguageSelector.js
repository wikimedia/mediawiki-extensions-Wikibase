( function( $, ExpertExtender, mw ) {

	'use strict';

	/**
	 * An `ExpertExtender` module for selecting a language.
	 * @class jQuery.valueview.ExpertExtender.LanguageSelector
	 * @since 0.6
	 * @licence GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 *
	 * @param {util.MessageProvider} messageProvider
	 * @param {Function} getUpstreamValue
	 * @param {Function} onValueChange
	 */
	ExpertExtender.LanguageSelector = function( messageProvider, getUpstreamValue, onValueChange ) {
		this._messageProvider = messageProvider;
		this._getUpstreamValue = getUpstreamValue;
		this._onValueChange = onValueChange;

		this.$selector = $( '<input />' );

		var self = this;

		this._languagesMap = getLanguagesMap( function( params ) {
			return self._messageProvider.getMessage( self._prefix + '-languagetemplate', params );
		} );
	};

	$.extend( ExpertExtender.LanguageSelector.prototype, {
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
		_languagesMap: null,

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
		 * Callback for the `init` `ExpertExtender` event.
		 *
		 * @param {jQuery} $extender
		 */
		init: function( $extender ) {
			if( this._languagesMap ) {
				this.$selector.languagesuggester( {
					source: $.map( this._languagesMap, function( language, code ) {
						return { code: code, label: language };
					} ),
					change: this._onValueChange
				} );
			} else {
				this.$selector.on( 'eachchange', this._onValueChange );
			}
			$extender
				.append( $( '<span />' ).text( this._messageProvider.getMessage( this._prefix + '-label' ) ) )
				.append( this.$selector );
		},

		/**
		 * Callback for the `onInitialShow` `ExpertExtender` event.
		 */
		onInitialShow: function() {
			var value = this._getUpstreamValue();
			if( this._languagesMap ) {
				// Necessary for mapping to the language code if the language is not changed.
				// FIXME: This is obviously an access violation, and it's probably not a good idea
				// to track this through the suggester given the current design.
				this.$selector.data( 'languagesuggester' )._selectedValue = value;
				value = this._languagesMap[ value ];
			}
			this.$selector.val( value );
		},

		/**
		 * Callback for the `destroy` `ExpertExtender` event.
		 */
		destroy: function() {
			this._getUpstreamValue = null;
			this.$selector = null;
			this._languagesMap = null;
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

	/**
	 * @ignore
	 *
	 * @param {Function} getMsg
	 * @return {Object}
	 */
	function getLanguagesMap( getMsg ) {
		var languages = mw.config.get( 'wgULSLanguages' );
		var languagesMap = {};

		if( !languages ) {
			return null;
		}

		$.each( languages, function( key, language ) {
			if( !language ) {
				return;
			}
			languagesMap[key] = getMsg( [ language, key ] );
		} );
		return languagesMap;
	}
} ( jQuery, jQuery.valueview.ExpertExtender, mediaWiki ) );
