/**
 * Toast 通知系统
 * 全局状态管理，支持 success / error / info 三种类型
 */
import { reactive, ref } from 'vue'

const toasts = reactive([])
let nextId = 0

const DEFAULT_DURATION = 3000

export function useToast() {
  function addToast(type, message, duration = DEFAULT_DURATION) {
    const id = ++nextId
    const toast = { id, type, message, leaving: false }
    toasts.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }

    return id
  }

  function removeToast(id) {
    const index = toasts.findIndex(t => t.id === id)
    if (index === -1) return

    // 先标记离开动画
    toasts[index].leaving = true

    // 动画完成后移除 DOM
    setTimeout(() => {
      const i = toasts.findIndex(t => t.id === id)
      if (i !== -1) toasts.splice(i, 1)
    }, 300)
  }

  function success(message, duration) {
    return addToast('success', message, duration)
  }

  function error(message, duration) {
    return addToast('error', message, duration)
  }

  function info(message, duration) {
    return addToast('info', message, duration)
  }

  return {
    toasts,
    success,
    error,
    info,
    removeToast,
  }
}
