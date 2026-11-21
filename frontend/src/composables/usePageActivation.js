import { onActivated } from 'vue'

export function usePageActivation(load, refresh) {
  let hasBeenActivated = false

  onActivated(() => {
    const handler = hasBeenActivated ? refresh : load
    hasBeenActivated = true
    handler()
  })
}
