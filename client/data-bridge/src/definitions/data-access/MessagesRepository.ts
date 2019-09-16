export default interface MessagesRepository {
	get( messageKey: string ): string;
}
