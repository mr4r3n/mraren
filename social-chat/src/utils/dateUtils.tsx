export function formatMessageDate(sentAt: string) {
    const messageDate = new Date(sentAt);
    const now = new Date();

    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);

    const messageDay = new Date(messageDate.getFullYear(), messageDate.getMonth(), messageDate.getDate());

    if (messageDay.getTime() === today.getTime()) {
        return `Hoy a las ${messageDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    } else if (messageDay.getTime() === yesterday.getTime()) {
        return `Ayer a las ${messageDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
    } else {
        return messageDate.toLocaleDateString([], { day: '2-digit', month: 'short', year: 'numeric' });
    }
}
