module.exports = {
	Deserializer: wikibase.serialization.Deserializer,
	EntityDeserializer: require( './Deserializers/EntityDeserializer.js' ),
	SnakDeserializer: wikibase.serialization.SnakDeserializer,
	StatementDeserializer: wikibase.serialization.StatementDeserializer,
	StatementGroupSetDeserializer: wikibase.serialization.StatementGroupSetDeserializer,
	StatementListDeserializer: wikibase.serialization.StatementListDeserializer,
	TermDeserializer: wikibase.serialization.TermDeserializer,
	TermMapDeserializer: wikibase.serialization.TermMapDeserializer,
	ClaimSerializer: wikibase.serialization.ClaimSerializer,
	ReferenceListSerializer: wikibase.serialization.ReferenceListSerializer,
	ReferenceSerializer: wikibase.serialization.ReferenceSerializer,
	Serializer: wikibase.serialization.Serializer,
	SnakListSerializer: wikibase.serialization.SnakListSerializer,
	SnakSerializer: wikibase.serialization.SnakSerializer,
	StatementListSerializer: wikibase.serialization.StatementListSerializer,
	StatementSerializer: wikibase.serialization.StatementSerializer,
	TermMapSerializer: require( './Serializers/TermMapSerializer.js' )
};
