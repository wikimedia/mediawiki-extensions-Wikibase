import EntityId from '@/datamodel/EntityId';
import { StatementState } from '@/store/statements';
import Snak from '@/datamodel/Snak';
import { PathToSnak } from '@/store/statements/PathToSnak';

export class MainSnakPath implements PathToSnak {

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

	public resolveSnakInStatement( state: StatementState ): Snak|null {
		if ( !state[ this.entityId ] ) {
			return null;
		}

		if ( !state[ this.entityId ][ this.propertyId ] ) {
			return null;
		}

		if ( !state[ this.entityId ][ this.propertyId ][ this.index ] ) {
			return null;
		}

		return state[ this.entityId ][ this.propertyId ][ this.index ].mainsnak;
	}
}
