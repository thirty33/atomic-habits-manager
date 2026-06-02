import axios from "axios";
import type { ActionRequest, MutationResult } from "../../application/ports";
import { RequestError, UnauthorizedError, ValidationError } from "../../application/errors";

/**
 * Thin base over the axios singleton configured in resources/js/bootstrap.js
 * (CSRF cookie + X-Requested-With). We do NOT create a new instance — that would
 * drop the session CSRF defaults. Subclasses are semantic wrappers per resource.
 */
export abstract class BaseHttpClient {
    protected async send<P>(action: ActionRequest, payload?: P): Promise<MutationResult> {
        try {
            const { data } = await axios.request<MutationResult>({
                url: action.url,
                method: action.method,
                data: payload,
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    ...action.headers,
                },
            });
            return data;
        } catch (error: unknown) {
            throw this.normalize(error);
        }
    }

    /** Translates an axios error into a domain error the UI knows how to render. */
    private normalize(error: unknown): Error {
        if (axios.isAxiosError(error) && error.response) {
            const status = error.response.status;
            const data = error.response.data as { errors?: Record<string, string[]>; message?: string };

            if (status === 422) {
                return new ValidationError(data?.errors ?? {});
            }
            if (status === 401) {
                return new UnauthorizedError();
            }
            return new RequestError(data?.message ?? "Error", status);
        }
        return error instanceof Error ? error : new RequestError("Error desconocido", 0);
    }
}