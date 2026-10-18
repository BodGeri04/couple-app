import { reactive } from 'vue';

/** Shared list of flying hearts/stars, rendered by <BurstLayer />. */
export const particles = reactive([]);

const palette = ['rgb(var(--c-primary))', 'rgb(var(--c-accent))', 'rgb(var(--c-peach))'];
let nextId = 1;
const rand = (min, max) => Math.random() * (max - min) + min;

export function useBurst() {
    /**
     * Pop hearts/stars at the tapped spot.
     *   @click="burst($event)"
     *   @click="burst($event, 'mixed', 14)"   // kind: 'heart' | 'star' | 'mixed'
     */
    const burst = (event, kind = 'heart', count = 9) => {
        let x = event?.clientX;
        let y = event?.clientY;

        if (!x && !y) {
            const rect = event?.currentTarget?.getBoundingClientRect?.();
            x = rect ? rect.left + rect.width / 2 : window.innerWidth / 2;
            y = rect ? rect.top + rect.height / 2 : window.innerHeight / 2;
        }

        for (let i = 0; i < count; i++) {
            const angle = rand(0, Math.PI * 2);
            const distance = rand(60, 130);
            const id = nextId++;

            particles.push({
                id,
                kind: kind === 'mixed' ? (i % 3 === 0 ? 'star' : 'heart') : kind,
                x,
                y,
                dx: Math.cos(angle) * distance,
                dy: Math.sin(angle) * distance - 40, // drift upwards
                rot: rand(-60, 60),
                size: rand(14, 26),
                color: palette[i % palette.length],
            });

            setTimeout(() => {
                const index = particles.findIndex((p) => p.id === id);
                if (index !== -1) particles.splice(index, 1);
            }, 1000);
        }

        navigator.vibrate?.(12); // Android only; iOS simply ignores it
    };

    return { burst };
}