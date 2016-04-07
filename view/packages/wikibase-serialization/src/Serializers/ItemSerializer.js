( function( wb, util, $ ) {
	'use strict';

var MODULE = wb.serialization,
	PARENT = MODULE.Serializer;

/**
 * @class wikibase.serialization.ItemSerializer
 * @extends wikibase.serialization.Serializer
 * @since 2.0
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 */
MODULE.ItemSerializer = util.inherit( 'WbItemSerializer', PARENT, {
	/**
	 * @inheritdoc
	 *
	 * @param {wikibase.datamodel.Item} item
	 * @return {Object}
	 *
	 * @throws {Error} if item is not an Item instance.
	 */
	serialize: function( item ) {
		if( !( item instanceof wb.datamodel.Item ) ) {
			throw new Error( 'Not an instance of wikibase.datamodel.Item' );
		}

		var fingerprintSerializer = new MODULE.FingerprintSerializer(),
			statementGroupSetSerializer = new MODULE.StatementGroupSetSerializer(),
			siteLinkSetSerializer = new MODULE.SiteLinkSetSerializer();

		return $.extend( true,
			{
				type: item.getType(),
				id: item.getId(),
				claims: statementGroupSetSerializer.serialize( item.getStatements() ),
				sitelinks: siteLinkSetSerializer.serialize( item.getSiteLinks() )
			},
			fingerprintSerializer.serialize( item.getFingerprint() )
		);
	}
} );

}( wikibase, util, jQuery ) );
