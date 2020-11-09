import {
	DataType,
	DataValue,
	DataValueType,
	Rank,
	SnakType,
} from '@wmde/wikibase-datamodel-types';
import { StatementState } from './StatementState';
import { PathToStatement } from '@/store/statements/PathToStatement';
import { Getters } from 'vuex-smart-module';
import EntityId from '@/datamodel/EntityId';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { PathToStatementGroup } from '@/store/statements/PathToStatementGroup';

export class StatementGetters extends Getters<StatementState> {
	public get containsEntity() {
		return ( entityId: EntityId ): boolean => {
			return this.state[ entityId ] !== undefined;
		};
	}

	public get propertyExists() {
		return (
			pathToStatementGroup: PathToStatementGroup,
		): boolean => {
			return pathToStatementGroup.resolveStatementGroup( this.state ) !== null;
		};
	}

	public get isStatementGroupAmbiguous() {
		return (
			pathToStatementGroup: PathToStatementGroup,
		): boolean => {
			const statementGroup = pathToStatementGroup.resolveStatementGroup( this.state );
			return statementGroup !== null && statementGroup.length > 1;
		};
	}

	public get rank() {
		return ( pathToStatement: PathToStatement ): Rank | null => {
			const statement = pathToStatement.resolveStatement( this.state );
			return statement?.rank ?? null;
		};
	}

	public get dataValue() {
		return ( pathToSnak: PathToSnak ): DataValue | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			return snak?.datavalue ?? null;
		};
	}

	public get snakType() {
		return ( pathToSnak: PathToSnak ): SnakType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			return snak?.snaktype ?? null;
		};
	}

	public get dataType() {
		return ( pathToSnak: PathToSnak ): DataType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			return snak?.datatype ?? null;
		};
	}

	public get dataValueType() {
		return ( pathToSnak: PathToSnak ): DataValueType | null => {
			const snak = pathToSnak.resolveSnakInStatement( this.state );
			return snak?.datavalue?.type ?? null;
		};
	}
}
