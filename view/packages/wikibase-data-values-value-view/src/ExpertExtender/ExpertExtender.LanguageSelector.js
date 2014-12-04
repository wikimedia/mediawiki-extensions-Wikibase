( function( $, uls, ExpertExtender ) {

	// FIXME: uls knows way more languages than \Languages

	'use strict';

	/**
	 * An `ExpertExtender` module for selecting a language.
	 * @class jQuery.valueview.ExpertExtender.LanguageSelector
	 * @since 0.6
	 * @licence GNU GPL v2+
	 * @author Adrian Lang <adrian.lang@wikimedia.de>
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

		var maps = getLanguagesMaps( function( params ) {
			return self._messageProvider.getMessage( self._prefix + '-languagetemplate', params );
		} );
		this._languagesMap = maps[0];
		this._inverseLanguagesMap = maps[1];
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
		 * @property {Object}
		 * @private
		 */
		_inverseLanguagesMap: null,

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
					source: $.map( this._languagesMap, function( language ) {
						return language;
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
				value = this._languagesMap[ this._getUpstreamValue() ];
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
			this._inverseLanguagesMap = null;
			this._messageProvider = null;
			this._onValueChange = null;
		},

		/**
		 * Gets the value currently set in the rotator.
		 *
		 * @return {string|null} The current value
		 */
		getValue: function() {
			var key = this.$selector.val();
			return ( this._inverseLanguagesMap && this._inverseLanguagesMap[key] ) || key;
		}
	} );

	/**
	 * @ignore
	 *
	 * @param {Function} getMsg
	 * @return {Object}
	 */
	function getLanguagesMaps( getMsg ) {
		var languagesMap = {};
		var inverseLanguagesMap = {};
		if( !uls ) {
			return [];
		}

		$.each( uls.data.languages, function( key, language ) {
			var str;
			if( !language[2] ) {
				return;
			}
			str = getMsg( [ language[2], key ] );
			languagesMap[key] = str;
			inverseLanguagesMap[str] = key;
		} );
		return [ languagesMap, inverseLanguagesMap ];
	}
} ( jQuery, jQuery.uls, jQuery.valueview.ExpertExtender ) );
