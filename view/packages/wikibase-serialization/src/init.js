/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb ) {
	'use strict';

	var MODULE = wb.serialization;

	// Register serializers:

	MODULE.SerializerFactory.registerSerializer(
		MODULE.ClaimListSerializer,
		wb.datamodel.ClaimList
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.ClaimSerializer,
		wb.datamodel.Claim
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.EntityIdSerializer,
		wb.datamodel.EntityId
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.FingerprintSerializer,
		wb.datamodel.Fingerprint
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.ReferenceListSerializer,
		wb.datamodel.ReferenceList
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.ReferenceSerializer,
		wb.datamodel.Reference
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.SiteLinkSerializer,
		wb.datamodel.SiteLink
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.SnakListSerializer,
		wb.datamodel.SnakList
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.SnakSerializer,
		wb.datamodel.Snak
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.StatementListSerializer,
		wb.datamodel.StatementList
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.StatementSerializer,
		wb.datamodel.Statement
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.TermGroupListSerializer,
		wb.datamodel.TermGroupList
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.TermGroupSerializer,
		wb.datamodel.TermGroup
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.TermSerializer,
		wb.datamodel.Term
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.TermListSerializer,
		wb.datamodel.TermList
	);

	// Register unserializers:

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.ClaimListUnserializer,
		wb.datamodel.ClaimList
	);

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
		MODULE.FingerprintUnserializer,
		wb.datamodel.Fingerprint
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.ReferenceListUnserializer,
		wb.datamodel.ReferenceList
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.ReferenceUnserializer,
		wb.datamodel.Reference
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

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.StatementUnserializer,
		wb.datamodel.Statement
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.StatementListUnserializer,
		wb.datamodel.StatementList
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.TermGroupListUnserializer,
		wb.datamodel.TermGroup
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.TermGroupUnserializer,
		wb.datamodel.TermGroup
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.TermUnserializer,
		wb.datamodel.Term
	);

	MODULE.SerializerFactory.registerUnserializer(
		MODULE.TermListUnserializer,
		wb.datamodel.TermList
	);

}( wikibase ) );
