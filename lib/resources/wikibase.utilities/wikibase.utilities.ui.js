/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	/**
	 * UI related utilities required by 'Wikibase' extension.
	 * @type {Object}
	 */
	wb.utilities.ui = {};

	/**
	 * Creates a pretty link to a entity's page. Expects information about the Entity as a plain
	 * Object with 'id', 'url' and 'label' fields. If the label is not set or empty, then the link
	 * will show the entity's ID and some explanatory text describing that the label hast not been
	 * set yet.
	 *
	 * @since 0.4
	 *
	 * @param {Object} entityData Requires 'url', 'id' and optionally 'label' fields
	 * @return jQuery 'a' element
	 */
	wb.utilities.ui.buildEntityLink = function( entityData ) {
		var $propertyLink = $( '<a/>', {
			href: entityData.url,
			text: entityData.label || entityData.id
		} );

		if( !entityData.label ) {
			$propertyLink.append( $( '<span/>', {
				'class': 'wb-entity-undefinedinfo',
				'text': ' (' + mw.msg( 'wikibase-label-empty' ) + ')'
			} ) );
		}

		return $propertyLink;
	};

}( mediaWiki, wikibase, jQuery ) );
