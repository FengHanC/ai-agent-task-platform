/**
 * 任务私有频道 WebSocket 监听
 *
 * 监听 private-tasks.{taskId} 频道上的广播事件：
 * - MessageSent — 新消息通知
 * - TaskStatusChanged — 任务状态变更通知
 *
 * 使用方式：
 *   const channel = useTaskChannel(taskId)
 *   channel.onMessageSent(msg => { ... })
 *   channel.onStatusChanged(data => { ... })
 *   channel.leave()  // 组件卸载时调用
 */
export function useTaskChannel(taskId) {
  const channelName = `private-tasks.${taskId}`

  function onMessageSent(callback) {
    if (!window.Echo) return
    window.Echo.private(channelName).listen('.MessageSent', (data) => {
      callback(data)
    })
  }

  function onStatusChanged(callback) {
    if (!window.Echo) return
    window.Echo.private(channelName).listen('.TaskStatusChanged', (data) => {
      callback(data)
    })
  }

  function leave() {
    if (!window.Echo) return
    window.Echo.leave(channelName)
  }

  // 组件卸载时自动离开
  return { onMessageSent, onStatusChanged, leave }
}
