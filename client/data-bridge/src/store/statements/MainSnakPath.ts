import EntityId from '@/datamodel/EntityId';
import Statement from '@/datamodel/Statement';
import { StatementState } from '@/store/statements';
import Snak from '@/datamodel/Snak';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { PathToStatement } from '@/store/statements/PathToStatement';

export class MainSnakPath implements PathToStatement, PathToSnak {

	public readonly entityId: EntityId;
	public readonly propertyId: EntityId;
	public readonly index: number;

	public constructor(
		entityId: EntityId,
		propertyId: EntityId,
		index: number,
	) {
		this.entityId = entityId;
		this.propertyId = propertyId;
		this.index = index;
	}

	public resolveStatement( state: StatementState ): Statement | null {
		return state?.[ this.entityId ]?.[ this.propertyId ]?.[ this.index ] ?? null;
	}

	public resolveSnakInStatement( state: StatementState ): Snak|null {
		return this.resolveStatement( state )?.mainsnak ?? null;
	}
}
