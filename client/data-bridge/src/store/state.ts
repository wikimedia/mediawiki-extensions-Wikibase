import DataValue from '@/datamodel/DataValue';
import Term from '@/datamodel/Term';
import ApplicationError from '@/definitions/ApplicationError';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import EditDecision from '@/definitions/EditDecision';
import Application from '@/store/Application';

export class BaseState implements Application {
	public applicationErrors: ApplicationError[] = [];
	public applicationStatus: ValidApplicationStatus = ValidApplicationStatus.INITIALIZING;
	public editDecision: EditDecision|null = null;
	public targetValue: DataValue|null = null;
	public renderedTargetReferences: string[] = [];
	public editFlow = '';
	public entityTitle = '';
	public originalHref = '';
	public pageTitle = '';
	public targetLabel: Term|null = null;
	public targetProperty = '';
}
