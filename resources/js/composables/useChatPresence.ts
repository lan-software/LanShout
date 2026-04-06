import { ref, onMounted, onUnmounted } from 'vue';

export interface ActiveUser {
    id: number;
    name: string;
    chat_color?: string | null;
    lancore_user_id?: number | null;
    roles: string[];
}

export function useChatPresence() {
    const activeUsers = ref<ActiveUser[]>([]);
    const slowModeActive = ref(false);
    let heartbeatInterval: ReturnType<typeof setInterval> | null = null;
    let activeUsersInterval: ReturnType<typeof setInterval> | null = null;

    async function sendHeartbeat() {
        try {
            const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
            const res = await fetch('/chat/heartbeat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token ?? '',
                },
            });
            if (res.ok) {
                const data = await res.json();
                slowModeActive.value = data.slowModeActive ?? false;
            }
        } catch {
            // Silently fail heartbeat
        }
    }

    async function fetchActiveUsers() {
        try {
            const res = await fetch('/chat/active-users', {
                headers: { Accept: 'application/json' },
            });
            if (res.ok) {
                const json = await res.json();
                activeUsers.value = json?.data ?? [];
            }
        } catch {
            // Silently fail
        }
    }

    function start() {
        sendHeartbeat();
        fetchActiveUsers();

        heartbeatInterval = setInterval(sendHeartbeat, 60_000);
        activeUsersInterval = setInterval(fetchActiveUsers, 30_000);
    }

    function stop() {
        if (heartbeatInterval) {
            clearInterval(heartbeatInterval);
            heartbeatInterval = null;
        }
        if (activeUsersInterval) {
            clearInterval(activeUsersInterval);
            activeUsersInterval = null;
        }
    }

    onMounted(start);
    onUnmounted(stop);

    return { activeUsers, slowModeActive, fetchActiveUsers };
}
