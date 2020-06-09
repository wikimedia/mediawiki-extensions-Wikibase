import { StatementMap } from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';

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
