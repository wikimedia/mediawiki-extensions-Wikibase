import StatementMap from '@/datamodel/StatementMap';

export default class Entity {
	public readonly id: string;
	public readonly statements: StatementMap;

	public constructor(
		id: string,
		statements: StatementMap,
	) {
		this.id = id;
		this.statements = statements;
	}
}
