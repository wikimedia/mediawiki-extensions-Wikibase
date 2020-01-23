import { StatementState } from '@/store/statements';
import Snak from '@/datamodel/Snak';

export interface PathToSnak {
	resolveSnakInStatement( state: StatementState ): Snak | null;
}
