/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv ) {
	'use strict';

	var PARENT = vv.BifidExpert,
		editableExpert = vv.experts.StringValue;

	/**
	 * Valueview expert for displaying values for URL data type as actual URLs. Editing will behave
	 * just like editing strings.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.BifidExpert
	 */
	vv.experts.UrlType = vv.expert( 'UrlType', PARENT, {
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
			domBuilder: function( currentRawValue, viewState ) {
				return viewState.getFormattedValue();
			},
			baseExpert: editableExpert
		}
	} );

}( jQuery, jQuery.valueview ) );
