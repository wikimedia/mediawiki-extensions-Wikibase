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
		if ( !state[ this.entityId ] ) {
			return null;
		}

		if ( !state[ this.entityId ][ this.propertyId ] ) {
			return null;
		}

		if ( !state[ this.entityId ][ this.propertyId ][ this.index ] ) {
			return null;
		}

		return state[ this.entityId ][ this.propertyId ][ this.index ];
	}

	public resolveSnakInStatement( state: StatementState ): Snak|null {
		const statement = this.resolveStatement( state );
		if ( statement === null ) {
			return null;
		}

		return statement.mainsnak;
	}
}
