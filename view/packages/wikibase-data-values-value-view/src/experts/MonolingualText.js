( function( vv, LanguageSelector ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * @class jQuery.valueview.experts.MonolingualText
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.6
	 * @licence GNU GPL v2+
	 * @author Adrian Lang < adrian.lang@wikimedia.de >
	 */
	vv.experts.MonolingualText = vv.expert( 'MonolingualText', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this._languageSelector = new LanguageSelector(
			this._options.contentLanguages,
			this._messageProvider,
			function() {
				var value = self.viewState().value();
				return value && value.getLanguageCode();
			},
			function() {
				self._viewNotifier.notify( 'change' );
			}
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this._languageSelector
			]
		);

		this.addExtension( inputExtender );
	}, {
		/**
		 * @property {jQuery.valueview.ExpertExtender.LanguageSelector}
		 * @private
		 */
		_languageSelector: null,

		/**
		 * @inheritdoc
		 */
		valueCharacteristics: function() {
			var options = {};
			if ( this._languageSelector ) {
				options.valuelang = this._languageSelector.getValue();
			}
			return options;
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );
			this._languageSelector = null;
		}
	} );

}( jQuery.valueview, jQuery.valueview.ExpertExtender.LanguageSelector ) );
