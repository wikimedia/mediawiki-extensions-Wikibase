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
		MODULE.SiteLinkSetSerializer,
		wb.datamodel.SiteLinkSet
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
		MODULE.MultiTermSetSerializer,
		wb.datamodel.MultiTermSet
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.MultiTermSerializer,
		wb.datamodel.MultiTerm
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.TermSerializer,
		wb.datamodel.Term
	);

	MODULE.SerializerFactory.registerSerializer(
		MODULE.TermSetSerializer,
		wb.datamodel.TermSet
	);

	// Register deserializers:

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.ClaimListDeserializer,
		wb.datamodel.ClaimList
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.ClaimDeserializer,
		wb.datamodel.Claim
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.EntityIdDeserializer,
		wb.datamodel.EntityId
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.EntityDeserializer,
		wb.datamodel.Entity
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.FingerprintDeserializer,
		wb.datamodel.Fingerprint
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.ReferenceListDeserializer,
		wb.datamodel.ReferenceList
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.ReferenceDeserializer,
		wb.datamodel.Reference
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.SiteLinkSetDeserializer,
		wb.datamodel.SiteLinkSet
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.SiteLinkDeserializer,
		wb.datamodel.SiteLink
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.SnakListDeserializer,
		wb.datamodel.SnakList
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.SnakDeserializer,
		wb.datamodel.Snak
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.StatementDeserializer,
		wb.datamodel.Statement
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.StatementListDeserializer,
		wb.datamodel.StatementList
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.MultiTermSetDeserializer,
		wb.datamodel.MultiTermSet
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.MultiTermDeserializer,
		wb.datamodel.MultiTerm
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.TermDeserializer,
		wb.datamodel.Term
	);

	MODULE.DeserializerFactory.registerDeserializer(
		MODULE.TermSetDeserializer,
		wb.datamodel.TermSet
	);

}( wikibase ) );
