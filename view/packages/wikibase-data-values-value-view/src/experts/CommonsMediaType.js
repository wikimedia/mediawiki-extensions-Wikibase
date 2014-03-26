/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( vv ) {
	'use strict';

	var PARENT = vv.experts.SuggestedStringValue;

	/**
	 * Valueview expert for adding specialized handling for CommonsMedia data type. Without this
	 * more specialized expert, the StringValue expert would be used since the CommonsMedia data
	 * type is using the String data value type.
	 * This expert is based on the StringValue expert but will add a dropdown for choosing commons
	 * media sources. It will also display the value as a link to commons.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.experts.SuggestedStringValue
	 */
	vv.experts.CommonsMediaType = vv.expert( 'CommonsMediaType', PARENT, {
		_options: {
			suggesterOptions: {
				ajax: {
					url: location.protocol + '//commons.wikimedia.org/w/api.php',
					params: {
						action: 'opensearch',
						namespace: 6
					}
				},
				replace: [/^File:/, '']
			}
		}
	} );

}( jQuery.valueview ) );
