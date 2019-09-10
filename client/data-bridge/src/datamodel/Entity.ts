import EntityId from '@/datamodel/EntityId';
import StatementMap from '@/datamodel/StatementMap';

export default class Entity {
	public readonly id: EntityId;
	public readonly statements: StatementMap;

	public constructor(
		id: EntityId,
		statements: StatementMap,
	) {
		this.id = id;
		this.statements = statements;
	}
}
