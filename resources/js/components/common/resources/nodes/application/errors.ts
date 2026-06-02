/**
 * Domain-level errors surfaced by the HTTP layer. The UI maps ValidationError
 * to per-field messages; everything else is a generic RequestError shown as a
 * toast (mirrors useForm.js: 422 → fields, otherwise → toast).
 */
export class ValidationError extends Error {
    constructor(public readonly errors: Record<string, string[]>) {
        super("Validation failed");
        this.name = "ValidationError";
    }
}

export class RequestError extends Error {
    constructor(
        message: string,
        public readonly status: number,
    ) {
        super(message);
        this.name = "RequestError";
    }
}

export class UnauthorizedError extends RequestError {
    constructor() {
        super("No autorizado", 401);
        this.name = "UnauthorizedError";
    }
}