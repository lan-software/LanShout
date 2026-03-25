<script setup lang="ts">
import { ref, watch, nextTick, onMounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { Button } from '@/components/ui/button'
import { MessageCircle, X, ChevronDown } from 'lucide-vue-next'
import ChatMessage from './ChatMessage.vue'
import MiniChatInput from './MiniChatInput.vue'

const { t } = useI18n()
const page = usePage()

// Hide on the dedicated chat page
const isOnChatPage = computed(() => page.url.startsWith('/chat'))

interface User { id: number; name: string; chat_color?: string | null }
interface Message { id: number; body: string; created_at: string; user: User }
interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

const isOpen = ref(false)
const messages = ref<Message[]>([])
const loading = ref(false)
const currentPage = ref(0)
const hasMore = ref(true)
const messagesContainer = ref<HTMLElement | null>(null)
const previousScrollHeight = ref(0)
const hasLoaded = ref(false)

function toggle() {
  isOpen.value = !isOpen.value
  if (isOpen.value && !hasLoaded.value) {
    loadMoreMessages()
    hasLoaded.value = true
  }
}

async function loadMoreMessages() {
  if (loading.value || !hasMore.value) return

  loading.value = true
  try {
    const nextPage = currentPage.value + 1
    const res = await fetch(`/messages?page=${nextPage}&per_page=20`, {
      headers: { Accept: 'application/json' }
    })
    const json = await res.json()

    const items: Message[] = json?.data ?? []
    const meta: PaginationMeta | undefined = json?.meta

    if (Array.isArray(items) && items.length > 0) {
      const reversed = [...items].reverse()
      messages.value = [...reversed, ...messages.value]
      currentPage.value = nextPage

      if (meta) {
        hasMore.value = meta.current_page < meta.last_page
      } else {
        hasMore.value = items.length === 20
      }
    } else {
      hasMore.value = false
    }
  } finally {
    loading.value = false
  }
}

async function submitMessage(body: string) {
  const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content
  const res = await fetch('/messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': token ?? '',
    },
    body: JSON.stringify({ body }),
  })
  if (!res.ok) {
    const data = await res.json().catch(() => ({}))
    throw new Error(data?.message || t('chat.errorSending'))
  }
  const data = await res.json()
  const msg: Message = data?.data ?? data
  messages.value = [...messages.value, msg]
  await nextTick()
  scrollToBottom()
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function handleScroll() {
  if (!messagesContainer.value) return
  const { scrollTop, scrollHeight } = messagesContainer.value
  if (scrollTop < 80 && hasMore.value && !loading.value) {
    previousScrollHeight.value = scrollHeight
    loadMoreMessages()
  }
}

// Maintain scroll position when history is prepended
watch(() => messages.value.length, async (newLen, oldLen) => {
  await nextTick()
  if (!messagesContainer.value) return
  if (newLen > oldLen && previousScrollHeight.value > 0) {
    const addedHeight = messagesContainer.value.scrollHeight - previousScrollHeight.value
    if (addedHeight > 0) {
      messagesContainer.value.scrollTop += addedHeight
    }
    previousScrollHeight.value = 0
  }
})

// Scroll to bottom when first opened
watch(isOpen, async (open) => {
  if (open) {
    await nextTick()
    scrollToBottom()
  }
})
</script>

<template>
  <div v-if="!isOnChatPage" class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-2">
    <!-- Chat panel -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-4 scale-95 opacity-0"
      enter-to-class="translate-y-0 scale-100 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 scale-100 opacity-100"
      leave-to-class="translate-y-4 scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="mb-2 flex h-[400px] w-[340px] flex-col overflow-hidden rounded-xl border border-sidebar-border/70 bg-background shadow-xl dark:border-sidebar-border"
      >
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-sidebar-border/70 px-3 py-2 dark:border-sidebar-border">
          <div class="flex items-center gap-2">
            <MessageCircle class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm font-semibold">{{ $t('chat.title') }}</span>
          </div>
          <Button variant="ghost" size="icon" class="h-7 w-7" @click="toggle">
            <X class="h-4 w-4" />
          </Button>
        </div>

        <!-- Messages area -->
        <div
          ref="messagesContainer"
          class="flex flex-1 flex-col gap-1.5 overflow-y-auto px-2 py-2"
          @scroll="handleScroll"
        >
          <!-- Load more -->
          <div v-if="hasMore && messages.length > 0" class="flex justify-center py-1">
            <Button
              variant="ghost"
              size="sm"
              :disabled="loading"
              class="h-6 text-xs"
              @click="loadMoreMessages"
            >
              {{ loading ? $t('chat.loadingMessages') : $t('chat.loadMore') }}
            </Button>
          </div>

          <!-- Loading indicator -->
          <div v-if="loading && messages.length === 0" class="flex flex-1 items-center justify-center">
            <span class="text-xs text-muted-foreground">{{ $t('chat.loadingMessages') }}</span>
          </div>

          <!-- Empty state -->
          <div v-if="!messages.length && !loading" class="flex flex-1 items-center justify-center">
            <span class="text-xs text-muted-foreground">{{ $t('chat.noMessages') }}</span>
          </div>

          <!-- Messages -->
          <ChatMessage
            v-for="m in messages"
            :key="m.id"
            :message="m"
          />
        </div>

        <!-- Input -->
        <MiniChatInput @submit="submitMessage" />
      </div>
    </Transition>

    <!-- Toggle button -->
    <Button
      size="icon"
      class="h-12 w-12 rounded-full shadow-lg"
      @click="toggle"
    >
      <MessageCircle v-if="!isOpen" class="h-5 w-5" />
      <ChevronDown v-else class="h-5 w-5" />
    </Button>
  </div>
</template>
