/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */
( function( vv ) {
	'use strict';

	var PARENT = vv.BifidExpert,
		editableExpert = vv.experts.TimeInput;

	/**
	 * Valueview expert for handling time values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.BifidExpert
	 */
	vv.experts.TimeValue = vv.expert( 'TimeValue', PARENT, {
		/**
		 * @see jQuery.valueview.BifidExpert._editableExpert
		 */
		_editableExpert: editableExpert,

		/**
		 * @see jQuery.valueview.BifidExpert._editableExpertOptions
		 */
		_editableExpertOptions: {},

		/**
		 * @see jQuery.valueview.BifidExpert._staticExpert
		 */
		_staticExpert: vv.experts.StaticDom,

		/**
		 * @see jQuery.valueview.BifidExpert._staticExpertOptions
		 */
		_staticExpertOptions: {
			/**
			 * @param {time.Time|null} currentRawValue
			 * @param {jQuery.valueview.ViewState} viewState
			 * @param {util.MessageProvider} messageProvider
			 */
			domBuilder: function( currentRawValue, viewState, messageProvider ) {
				return viewState.getFormattedValue();
			},
			baseExpert: editableExpert
		}
	} );

}( jQuery.valueview ) );
