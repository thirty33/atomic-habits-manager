import { onUnmounted } from 'vue';

export default function (channelName, listeners) {
    let activeChannel = null;

    const subscribe = (name) => {
        unsubscribe();
        activeChannel = name;
        const channel = window.Echo.private(name);
        for (const { event, callback } of listeners) {
            channel.listen(event, callback);
        }
    };

    const unsubscribe = () => {
        if (activeChannel) {
            window.Echo.leave(activeChannel);
            activeChannel = null;
        }
    };

    if (channelName) {
        subscribe(channelName);
    }

    onUnmounted(() => {
        unsubscribe();
    });

    return { subscribe, unsubscribe };
}