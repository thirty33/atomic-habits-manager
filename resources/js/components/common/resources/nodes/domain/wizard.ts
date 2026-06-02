import type { NodeStep } from "./node-schema";

export const FIRST_INDEX = 0;

export function stepAt(steps: NodeStep[], index: number): NodeStep {
    return steps[index];
}

export function isFirst(index: number): boolean {
    return index <= FIRST_INDEX;
}

export function isLast(index: number, total: number): boolean {
    return index >= total - 1;
}

export function nextIndex(index: number, total: number): number {
    return isLast(index, total) ? index : index + 1;
}

export function prevIndex(index: number): number {
    return isFirst(index) ? index : index - 1;
}
