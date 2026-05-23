/**
 * 任务私有频道 WebSocket 监听
 *
 * 监听 private-tasks.{taskId} 频道上的广播事件：
 * - MessageSent — 新消息通知
 * - TaskStatusChanged — 任务状态变更通知
 */
import { ref } from 'vue'

// 全局 Echo 连接状态（所有组件共享）
export const echoConnected = ref(false)

// 检测 Echo 是否就绪
function checkEcho() {
  const ready = !!(window.Echo && window.Echo.connector)
  echoConnected.value = ready
  if (!ready && typeof console !== 'undefined') {
    console.warn('[WebSocket] Echo 未初始化，实时推送不可用。请检查 Reverb 配置。')
  }
  return ready
}

export function useTaskChannel(taskId) {
  const channelName = `private-tasks.${taskId}`

  function onMessageSent(callback) {
    if (!checkEcho()) return
    window.Echo.private(channelName).listen('.MessageSent', (data) => {
      callback(data)
    })
  }

  function onStatusChanged(callback) {
    if (!checkEcho()) return
    window.Echo.private(channelName).listen('.TaskStatusChanged', (data) => {
      callback(data)
    })
  }

  function leave() {
    if (!window.Echo) return
    try {
      window.Echo.leave(channelName)
    } catch (e) {
      // 忽略离开时的错误
    }
  }

  return { onMessageSent, onStatusChanged, leave }
}
