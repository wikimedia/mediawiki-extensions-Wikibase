interface WikibaseEntityId {
	'entity-type': string;
	'numeric-id'?: number;
	id: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
}

export default WikibaseEntityId;
