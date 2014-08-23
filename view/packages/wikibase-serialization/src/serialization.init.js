/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
	'use strict';

	var MODULE = wb.serialization;

	// Register serializers:

	MODULE.SerializerFactory.registerSerializer(
		MODULE.ClaimSerializer,
		wb.datamodel.Claim
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.EntityIdSerializer,
		wb.datamodel.EntityId
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.ReferenceSerializer,
		wb.datamodel.Reference
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.SnakListSerializer,
		wb.datamodel.SnakList
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.SnakSerializer,
		wb.datamodel.Snak
	);

	// Register unserializers:

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.ClaimUnserializer,
		wb.datamodel.Claim
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.EntityIdUnserializer,
		wb.datamodel.EntityId
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.EntityUnserializer,
		wb.datamodel.Entity
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.ReferenceUnserializer,
		wb.datamodel.SnakList
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.SiteLinkUnserializer,
		wb.datamodel.SiteLink
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.SnakListUnserializer,
		wb.datamodel.SnakList
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.SnakUnserializer,
		wb.datamodel.Snak
	);

}( wikibase ) );
