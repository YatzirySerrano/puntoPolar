import { onBeforeUnmount, onMounted } from 'vue'

export function useReveal() {
    let observer: IntersectionObserver | null = null

    onMounted(() => {
        if (typeof window === 'undefined') return

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.querySelectorAll<HTMLElement>('[data-reveal]').forEach((el) => {
                el.classList.add('is-visible')
            })
            return
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible')
                        observer?.unobserve(entry.target)
                    }
                })
            },
            { threshold: 0.1, rootMargin: '0px 0px -6% 0px' },
        )

        document.querySelectorAll<HTMLElement>('[data-reveal]').forEach((el) => {
            observer?.observe(el)
        })
    })

    onBeforeUnmount(() => {
        observer?.disconnect()
    })
}
