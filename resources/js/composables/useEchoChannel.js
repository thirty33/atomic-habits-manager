import { onUnmounted } from 'vue';

export default function (channelName, eventName, callback) {
    let activeChannel = null;

    const subscribe = (name) => {
        unsubscribe();
        activeChannel = name;
        window.Echo.private(name)
            .listen(eventName, callback);
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