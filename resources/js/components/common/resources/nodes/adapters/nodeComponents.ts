import { NodeKind } from "../domain/node-schema";

/** Maps a domain node kind to its UI component name (presentation concern, not domain). */
export const NODE_COMPONENTS: Record<NodeKind, string> = {
    [NodeKind.Form]: "AppFormNode",
    [NodeKind.List]: "AppListNode",
};
