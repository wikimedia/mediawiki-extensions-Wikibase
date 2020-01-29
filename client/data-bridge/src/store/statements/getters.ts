import DataType from '@/datamodel/DataType';
import { StatementState } from '@/store/statements';
import { Getters } from 'vuex-smart-module';
import {
	STATEMENTS_CONTAINS_ENTITY,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/statements/getterTypes';
import EntityId from '@/datamodel/EntityId';
import {
	SNAK_DATA_VALUE,
	SNAK_DATATYPE,
	SNAK_DATAVALUETYPE,
	SNAK_SNAKTYPE,
} from '@/store/statements/snaks/getterTypes';
import DataValue from '@/datamodel/DataValue';
import { SnakType } from '@/datamodel/Snak';
import DataValueType from '@/datamodel/DataValueType';
import { PathToSnak } from '@/store/statements/PathToSnak';

export class StatementGetters extends Getters<StatementState> {
	public get [ STATEMENTS_CONTAINS_ENTITY ]() {
		return ( entityId: EntityId ): boolean => {
			return this.state[ entityId ] !== undefined;
		};
	}

	public get [ STATEMENTS_PROPERTY_EXISTS ]() {
		return (
			entityId: EntityId,
			propertyId: EntityId,
		): boolean => {
			return this.state[ entityId ] !== undefined
				&& this.state[ entityId ][ propertyId ] !== undefined;
		};
	}

	public get [ STATEMENTS_IS_AMBIGUOUS ]() {
		return (
			entityId: EntityId,
			propertyId: EntityId,
		): boolean => {
			return this.state[ entityId ] !== undefined
				&& this.state[ entityId ][ propertyId ] !== undefined
				&& this.state[ entityId ][ propertyId ].length > 1;
		};
	}

	public get [ SNAK_DATA_VALUE ]() {
		return ( pathToSnak: PathToSnak ): DataValue | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak || !snak.datavalue ) {
				return null;
			}

			return snak.datavalue;
		};
	}

	public get [ SNAK_SNAKTYPE ]() {
		return ( pathToSnak: PathToSnak ): SnakType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak ) {
				return null;
			}

			return snak.snaktype;
		};
	}

	public get [ SNAK_DATATYPE ]() {
		return ( pathToSnak: PathToSnak ): DataType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak ) {
				return null;
			}

			return snak.datatype;
		};
	}

	public get [ SNAK_DATAVALUETYPE ]() {
		return ( pathToSnak: PathToSnak ): DataValueType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak || !snak.datavalue ) {
				return null;
			}

			return snak.datavalue.type;
		};
	}
}
