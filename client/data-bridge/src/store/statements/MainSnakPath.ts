import {
	Snak,
	Statement,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { PathToStatement } from '@/store/statements/PathToStatement';
import { PathToStatementGroup } from '@/store/statements/PathToStatementGroup';

export class MainSnakPath implements PathToStatement, PathToSnak, PathToStatementGroup {

	private readonly entityId: EntityId;
	private readonly propertyId: EntityId;
	private readonly index: number;

	public constructor(
		entityId: EntityId,
		propertyId: EntityId,
		index: number,
	) {
		this.entityId = entityId;
		this.propertyId = propertyId;
		this.index = index;
	}

	public resolveStatementGroup( state: Record<EntityId, StatementMap> ): Statement[] | null {
		return state?.[ this.entityId ]?.[ this.propertyId ] ?? null;
	}

	public resolveStatement( state: Record<EntityId, StatementMap> ): Statement | null {
		return this.resolveStatementGroup( state )?.[ this.index ] ?? null;
	}

	public resolveSnakInStatement( state: Record<EntityId, StatementMap> ): Snak|null {
		return this.resolveStatement( state )?.mainsnak ?? null;
	}
}
