export default interface MessagesRepository {
	get( messageKey: string, ...params: string[] ): string;
}
