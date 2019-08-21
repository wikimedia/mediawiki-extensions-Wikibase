import StatementsState from '@/store/entity/statements/StatementsState';
import Snak from '@/datamodel/Snak';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';

export default function resolveMainSnak( state: StatementsState, coordinates: MainSnakPath ): Snak|null {
	if ( !state[ coordinates.entityId ] ) {
		return null;
	}

	if ( !state[ coordinates.entityId ][ coordinates.propertyId ] ) {
		return null;
	}

	if ( !state[ coordinates.entityId ][ coordinates.propertyId ][ coordinates.index ] ) {
		return null;
	}

	return state[ coordinates.entityId ][ coordinates.propertyId ][ coordinates.index ].mainsnak;
}
