<script>
export default {
    name: 'AtomicIAChatPage',
}
</script>

<script setup>
import { ref, nextTick } from 'vue';
import DataProvider from '@/providers/DataProvider.js';
import useDataProvider from '@/composables/useDataProvider.js';
import useAxios from '@/composables/useAxios.js';
import useEchoChannel from '@/composables/useEchoChannel.js';

defineProps({
    jsonUrl: {
        type: String,
        required: true,
    },
});

const { dataProviderKey, updateDataProvider } = useDataProvider();
const { makeRequest } = useAxios();

const conversation = ref(null);
const messages = ref([]);
const newMessage = ref('');
const messagesContainer = ref(null);
const isSending = ref(false);
const storeUrl = ref('');

const { subscribe } = useEchoChannel(null, '.message-sent', (e) => {
    messages.value.push(e.message);
    scrollToBottom();
});

const initConfig = (data) => {
    conversation.value = data.conversation;
    messages.value = data.conversation?.messages ?? [];
    storeUrl.value = data.store_url ?? '';

    if (data.conversation?.conversation_id) {
        subscribe(`conversation.${data.conversation.conversation_id}`);
    }

    scrollToBottom();
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    const text = newMessage.value.trim();
    if (!text || isSending.value) return;

    const tempMessage = {
        message_id: Date.now(),
        role: 'user',
        body: text,
        type: 'text',
        created_at: new Date().toLocaleTimeString('es', { hour: '2-digit', minute: '2-digit' }),
    };

    messages.value.push(tempMessage);
    newMessage.value = '';
    isSending.value = true;
    scrollToBottom();

    try {
        const { data } = await makeRequest({
            method: 'post',
            url: storeUrl.value,
            data: { body: text },
        });

        const index = messages.value.findIndex(m => m.message_id === tempMessage.message_id);
        if (index !== -1) {
            messages.value[index] = data.message;
        }
    } catch (error) {
        const index = messages.value.findIndex(m => m.message_id === tempMessage.message_id);
        if (index !== -1) {
            messages.value.splice(index, 1);
        }
    } finally {
        isSending.value = false;
    }
};
</script>

<template>
    <DataProvider
        :provider-key="dataProviderKey"
        :url="jsonUrl"
        @refreshed="initConfig"
    >
        <template v-slot="{ loading, data, error }">
            <div class="flex flex-col h-[calc(100dvh-4rem)] md:h-[calc(100dvh-10rem)] lg:h-[calc(100vh-10rem)]">
                <!-- Header -->
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 bg-white flex-shrink-0">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-indigo-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900">{{ data?.page_title ?? 'Atomic IA' }}</h1>
                        <p class="text-xs text-gray-500">Tu asistente de hábitos atómicos</p>
                    </div>
                </div>

                <!-- Messages -->
                <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-4 bg-gray-50">
                    <div
                        v-for="message in messages"
                        :key="message.message_id"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <div
                            class="max-w-[80%] lg:max-w-[60%] rounded-2xl px-4 py-2.5"
                            :class="message.role === 'user'
                                ? 'bg-indigo-600 text-white rounded-br-md'
                                : 'bg-white text-gray-800 border border-gray-200 rounded-bl-md shadow-sm'"
                        >
                            <p class="text-sm leading-relaxed whitespace-pre-line">{{ message.body }}</p>
                            <p
                                class="text-[10px] mt-1"
                                :class="message.role === 'user' ? 'text-indigo-200' : 'text-gray-400'"
                            >
                                {{ message.created_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="flex-shrink-0 border-t border-gray-200 bg-white px-4 py-3">
                    <form @submit.prevent="sendMessage" class="flex items-center gap-2">
                        <input
                            v-model="newMessage"
                            type="text"
                            placeholder="Escribe un mensaje..."
                            class="flex-1 rounded-full border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            :disabled="isSending"
                        />
                        <button
                            type="submit"
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isSending"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </DataProvider>
</template>