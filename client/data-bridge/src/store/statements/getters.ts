import DataType from '@/datamodel/DataType';
import { Rank } from '@/datamodel/Statement';
import { StatementState } from '@/store/statements';
import { PathToStatement } from '@/store/statements/PathToStatement';
import { Getters } from 'vuex-smart-module';
import EntityId from '@/datamodel/EntityId';
import DataValue from '@/datamodel/DataValue';
import { SnakType } from '@/datamodel/Snak';
import DataValueType from '@/datamodel/DataValueType';
import { PathToSnak } from '@/store/statements/PathToSnak';

export class StatementGetters extends Getters<StatementState> {
	public get containsEntity() {
		return ( entityId: EntityId ): boolean => {
			return this.state[ entityId ] !== undefined;
		};
	}

	public get propertyExists() {
		return (
			entityId: EntityId,
			propertyId: EntityId,
		): boolean => {
			return this.state[ entityId ] !== undefined
				&& this.state[ entityId ][ propertyId ] !== undefined;
		};
	}

	public get isAmbiguous() {
		return (
			entityId: EntityId,
			propertyId: EntityId,
		): boolean => {
			return this.state[ entityId ] !== undefined
				&& this.state[ entityId ][ propertyId ] !== undefined
				&& this.state[ entityId ][ propertyId ].length > 1;
		};
	}

	public get rank() {
		return ( pathToStatement: PathToStatement ): Rank | null => {
			const statement = pathToStatement.resolveStatement( this.state );
			if ( !statement ) {
				return null;
			}

			return statement.rank;
		};
	}

	public get dataValue() {
		return ( pathToSnak: PathToSnak ): DataValue | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak || !snak.datavalue ) {
				return null;
			}

			return snak.datavalue;
		};
	}

	public get snakType() {
		return ( pathToSnak: PathToSnak ): SnakType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak ) {
				return null;
			}

			return snak.snaktype;
		};
	}

	public get dataType() {
		return ( pathToSnak: PathToSnak ): DataType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak ) {
				return null;
			}

			return snak.datatype;
		};
	}

	public get dataValueType() {
		return ( pathToSnak: PathToSnak ): DataValueType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			if ( !snak || !snak.datavalue ) {
				return null;
			}

			return snak.datavalue.type;
		};
	}
}
