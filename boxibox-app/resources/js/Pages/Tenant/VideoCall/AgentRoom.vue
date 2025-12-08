<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    call: Object,
    settings: Object,
    iceServers: Array,
});

// WebRTC refs
const localVideo = ref(null);
const remoteVideo = ref(null);
const peerConnection = ref(null);
const localStream = ref(null);

// UI state
const isConnected = ref(false);
const isVideoEnabled = ref(true);
const isAudioEnabled = ref(true);
const isScreenSharing = ref(false);
const showChat = ref(true);
const callDuration = ref(0);
const durationInterval = ref(null);

// Chat
const messages = ref([]);
const newMessage = ref('');
const chatContainer = ref(null);

// Call summary
const showEndCallModal = ref(false);
const callSummary = ref('');
const callNotes = ref('');

const startCamera = async () => {
    try {
        localStream.value = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true,
        });
        if (localVideo.value) {
            localVideo.value.srcObject = localStream.value;
        }
    } catch (error) {
        console.error('Error accessing camera:', error);
    }
};

const initWebRTC = () => {
    const config = {
        iceServers: props.iceServers,
    };

    peerConnection.value = new RTCPeerConnection(config);

    // Add local stream tracks
    if (localStream.value) {
        localStream.value.getTracks().forEach(track => {
            peerConnection.value.addTrack(track, localStream.value);
        });
    }

    // Handle incoming tracks
    peerConnection.value.ontrack = (event) => {
        if (remoteVideo.value) {
            remoteVideo.value.srcObject = event.streams[0];
            isConnected.value = true;
            startDurationTimer();
        }
    };

    // ICE candidate handling
    peerConnection.value.onicecandidate = (event) => {
        if (event.candidate) {
            // Send candidate to signaling server
            console.log('ICE candidate:', event.candidate);
        }
    };
};

const startDurationTimer = () => {
    durationInterval.value = setInterval(() => {
        callDuration.value++;
    }, 1000);
};

const formatDuration = (seconds) => {
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hrs > 0) {
        return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const toggleVideo = () => {
    if (localStream.value) {
        const videoTrack = localStream.value.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            isVideoEnabled.value = videoTrack.enabled;
        }
    }
};

const toggleAudio = () => {
    if (localStream.value) {
        const audioTrack = localStream.value.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            isAudioEnabled.value = audioTrack.enabled;
        }
    }
};

const toggleScreenShare = async () => {
    if (!isScreenSharing.value) {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({
                video: true,
            });
            const screenTrack = screenStream.getVideoTracks()[0];

            // Replace video track
            const sender = peerConnection.value.getSenders().find(s => s.track?.kind === 'video');
            if (sender) {
                sender.replaceTrack(screenTrack);
            }

            screenTrack.onended = () => {
                toggleScreenShare();
            };

            isScreenSharing.value = true;
        } catch (error) {
            console.error('Error sharing screen:', error);
        }
    } else {
        // Restore camera
        const cameraTrack = localStream.value.getVideoTracks()[0];
        const sender = peerConnection.value.getSenders().find(s => s.track?.kind === 'video');
        if (sender && cameraTrack) {
            sender.replaceTrack(cameraTrack);
        }
        isScreenSharing.value = false;
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;

    try {
        const response = await fetch(route('tenant.video-calls.send-message', props.call.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({ message: newMessage.value }),
        });

        if (response.ok) {
            const message = await response.json();
            messages.value.push(message);
            newMessage.value = '';
            await nextTick();
            scrollChatToBottom();
        }
    } catch (error) {
        console.error('Error sending message:', error);
    }
};

const loadMessages = async () => {
    try {
        const response = await fetch(route('tenant.video-calls.get-messages', props.call.id));
        if (response.ok) {
            messages.value = await response.json();
            await nextTick();
            scrollChatToBottom();
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
};

const scrollChatToBottom = () => {
    if (chatContainer.value) {
        chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
    }
};

const endCall = () => {
    showEndCallModal.value = true;
};

const confirmEndCall = () => {
    // Stop all tracks
    if (localStream.value) {
        localStream.value.getTracks().forEach(track => track.stop());
    }

    // Close peer connection
    if (peerConnection.value) {
        peerConnection.value.close();
    }

    // Stop duration timer
    if (durationInterval.value) {
        clearInterval(durationInterval.value);
    }

    router.post(route('tenant.video-calls.end', props.call.id), {
        summary: callSummary.value,
        notes: callNotes.value,
    });
};

onMounted(async () => {
    await startCamera();
    initWebRTC();
    loadMessages();

    // Simulate connection for demo
    setTimeout(() => {
        isConnected.value = true;
        startDurationTimer();
    }, 2000);
});

onUnmounted(() => {
    if (localStream.value) {
        localStream.value.getTracks().forEach(track => track.stop());
    }
    if (peerConnection.value) {
        peerConnection.value.close();
    }
    if (durationInterval.value) {
        clearInterval(durationInterval.value);
    }
});
</script>

<template>
    <Head :title="$t('video_calls.video_room')" />

    <div class="h-screen bg-gray-900 flex">
        <!-- Main Video Area -->
        <div :class="['flex-1 relative', showChat ? '' : 'w-full']">
            <!-- Remote Video (Full size) -->
            <div class="absolute inset-0">
                <video
                    ref="remoteVideo"
                    autoplay
                    playsinline
                    class="w-full h-full object-cover"
                ></video>

                <!-- Placeholder when not connected -->
                <div v-if="!isConnected" class="absolute inset-0 flex items-center justify-center bg-gray-800">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mx-auto mb-4"></div>
                        <p class="text-white text-lg">{{ $t('video_calls.waiting_for_guest') }}</p>
                        <p class="text-gray-400 text-sm mt-2">{{ call.guest_name || $t('video_calls.guest') }}</p>
                    </div>
                </div>
            </div>

            <!-- Local Video (Picture-in-Picture) -->
            <div class="absolute bottom-24 right-4 w-48 h-36 rounded-lg overflow-hidden shadow-lg border-2 border-gray-700">
                <video
                    ref="localVideo"
                    autoplay
                    muted
                    playsinline
                    class="w-full h-full object-cover mirror"
                ></video>
                <div v-if="!isVideoEnabled" class="absolute inset-0 bg-gray-800 flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 9v6m-7.772-.228a9 9 0 0112.544-12.544"/>
                    </svg>
                </div>
            </div>

            <!-- Call Info Header -->
            <div class="absolute top-0 left-0 right-0 p-4 bg-gradient-to-b from-black/50 to-transparent">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="text-white">
                            <h2 class="font-semibold">{{ call.guest_name || $t('video_calls.guest') }}</h2>
                            <p class="text-sm text-gray-300">{{ call.site?.name }}</p>
                        </div>
                        <span v-if="isConnected" class="flex items-center text-green-400 text-sm">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                            {{ $t('video_calls.connected') }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-white font-mono text-lg">
                            {{ formatDuration(callDuration) }}
                        </span>
                        <span class="px-3 py-1 bg-primary-600 text-white text-sm rounded-full">
                            {{ $t('video_calls.types.' + call.type) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Controls -->
            <div class="absolute bottom-0 left-0 right-0 p-4">
                <div class="flex items-center justify-center space-x-4">
                    <!-- Toggle Video -->
                    <button
                        @click="toggleVideo"
                        :class="[
                            'p-4 rounded-full transition-colors',
                            isVideoEnabled ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-red-600 text-white hover:bg-red-700'
                        ]"
                        :title="isVideoEnabled ? $t('video_calls.disable_video') : $t('video_calls.enable_video')"
                    >
                        <svg v-if="isVideoEnabled" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </button>

                    <!-- Toggle Audio -->
                    <button
                        @click="toggleAudio"
                        :class="[
                            'p-4 rounded-full transition-colors',
                            isAudioEnabled ? 'bg-gray-700 text-white hover:bg-gray-600' : 'bg-red-600 text-white hover:bg-red-700'
                        ]"
                        :title="isAudioEnabled ? $t('video_calls.mute') : $t('video_calls.unmute')"
                    >
                        <svg v-if="isAudioEnabled" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" clip-rule="evenodd"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                    </button>

                    <!-- Screen Share -->
                    <button
                        v-if="settings?.screen_sharing_enabled"
                        @click="toggleScreenShare"
                        :class="[
                            'p-4 rounded-full transition-colors',
                            isScreenSharing ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-700 text-white hover:bg-gray-600'
                        ]"
                        :title="$t('video_calls.screen_share')"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </button>

                    <!-- Toggle Chat -->
                    <button
                        v-if="settings?.chat_enabled"
                        @click="showChat = !showChat"
                        :class="[
                            'p-4 rounded-full transition-colors',
                            showChat ? 'bg-primary-600 text-white hover:bg-primary-700' : 'bg-gray-700 text-white hover:bg-gray-600'
                        ]"
                        :title="$t('video_calls.toggle_chat')"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </button>

                    <!-- End Call -->
                    <button
                        @click="endCall"
                        class="p-4 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors"
                        :title="$t('video_calls.end_call')"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chat Sidebar -->
        <div v-if="showChat && settings?.chat_enabled" class="w-80 bg-gray-800 flex flex-col border-l border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="font-medium text-white">{{ $t('video_calls.chat') }}</h3>
            </div>

            <!-- Messages -->
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                <div
                    v-for="msg in messages"
                    :key="msg.id"
                    :class="[
                        'max-w-[80%] rounded-lg p-3',
                        msg.sender_type === 'agent'
                            ? 'ml-auto bg-primary-600 text-white'
                            : 'bg-gray-700 text-white'
                    ]"
                >
                    <div class="text-xs opacity-75 mb-1">{{ msg.sender_name }}</div>
                    <div>{{ msg.message }}</div>
                </div>

                <div v-if="messages.length === 0" class="text-center text-gray-500 py-8">
                    {{ $t('video_calls.no_messages') }}
                </div>
            </div>

            <!-- Message Input -->
            <div class="p-4 border-t border-gray-700">
                <form @submit.prevent="sendMessage" class="flex space-x-2">
                    <input
                        type="text"
                        v-model="newMessage"
                        :placeholder="$t('video_calls.type_message')"
                        class="flex-1 bg-gray-700 border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-primary-500 focus:border-primary-500"
                    />
                    <button
                        type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- End Call Modal -->
        <div v-if="showEndCallModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70">
            <div class="bg-white dark:bg-gray-800 rounded-lg w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $t('video_calls.end_call_summary') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('video_calls.call_summary') }}
                        </label>
                        <textarea
                            v-model="callSummary"
                            rows="3"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            :placeholder="$t('video_calls.summary_placeholder')"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ $t('video_calls.internal_notes') }}
                        </label>
                        <textarea
                            v-model="callNotes"
                            rows="2"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button
                        @click="showEndCallModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        @click="confirmEndCall"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                    >
                        {{ $t('video_calls.end_call') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.mirror {
    transform: scaleX(-1);
}
</style>
