import {
	DataValue,
	Snak,
	Statement,
	StatementMap,
} from '@wmde/wikibase-datamodel-types';
import StatementMutationStrategy from '@/change-op/statement-mutation/strategies/StatementMutationStrategy';
import EntityId from '@/datamodel/EntityId';
import { PathToStatement } from '@/store/statements/PathToStatement';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { PathToStatementGroup } from '@/store/statements/PathToStatementGroup';
import StatementMutationError from '@/change-op/statement-mutation/StatementMutationError';

export default class UpdateMutationStrategy implements StatementMutationStrategy {
	public apply<T extends Record<EntityId, StatementMap>>(
		targetValue: DataValue,
		path: PathToStatement & PathToSnak & PathToStatementGroup,
		state: T,
	): T {
		const statementGroup = path.resolveStatementGroup( state );
		const oldStatement = path.resolveStatement( state );
		if ( statementGroup === null ) {
			throw new Error( StatementMutationError.NO_STATEMENT_GROUP_FOUND );
		}
		if ( oldStatement === null ) {
			throw new Error( StatementMutationError.NO_SNAK_FOUND );
		}

		const oldSnak = oldStatement.mainsnak;
		if ( oldSnak.datavalue !== undefined && targetValue.type !== oldSnak.datavalue.type ) {
			throw new Error( StatementMutationError.INCONSISTENT_PAYLOAD_TYPE );
		}

		oldStatement.rank = 'normal';

		const newStatement = this.buildNewPreferredStatement(
			oldSnak,
			targetValue,
		);

		statementGroup.push( newStatement );

		return state;
	}

	private buildNewPreferredStatement( oldSnak: Snak, newDataValue: DataValue ): Statement {
		return {
			rank: 'preferred',
			type: 'statement',
			mainsnak: {
				snaktype: 'value',
				property: oldSnak.property,
				datatype: oldSnak.datatype,
				datavalue: newDataValue,
			},
		};
	}

}
