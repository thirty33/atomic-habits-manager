export function createSaveQueue() {
    let running = false;
    let pending = null;

    async function enqueue(operation) {
        if (running) {
            pending = operation;
            return;
        }

        running = true;
        try {
            await operation();
        } finally {
            running = false;

            if (pending) {
                const next = pending;
                pending = null;
                await enqueue(next);
            }
        }
    }

    return { enqueue };
}