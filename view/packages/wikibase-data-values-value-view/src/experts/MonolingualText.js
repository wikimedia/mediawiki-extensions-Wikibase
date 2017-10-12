module.exports = ( function( vv, LanguageSelector ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * @class jQuery.valueview.experts.MonolingualText
	 * @extends jQuery.valueview.experts.StringValue
	 * @since 0.6
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
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
			return {
				valuelang: this._languageSelector.getValue()
			};
		},

		/**
		 * @inheritdoc
		 */
		destroy: function() {
			PARENT.prototype.destroy.call( this );
			this._languageSelector = null;
		}
	} );

	return vv.experts.MonolingualText;

}( jQuery.valueview, jQuery.valueview.ExpertExtender.LanguageSelector ) );
