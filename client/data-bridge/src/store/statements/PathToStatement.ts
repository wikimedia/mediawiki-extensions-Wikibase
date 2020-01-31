import Statement from '@/datamodel/Statement';
import { StatementState } from '@/store/statements/index';

export interface PathToStatement {
	resolveStatement( state: StatementState ): Statement | null;
}
