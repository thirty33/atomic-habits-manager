import type { FieldDefinition, FormValues, ModalModel } from "./node-schema";

/** Build the initial values for a set of fields, seeding from the model or defaults. */
export function initialValues(fields: FieldDefinition[], model: ModalModel = null): FormValues {
    const values: FormValues = {};
    for (const field of fields) {
        values[field.props.name] = seed(field, model);
    }
    return values;
}

function seed(field: FieldDefinition, model: ModalModel): unknown {
    const name = field.props.name;
    if (model && model[name] !== undefined && model[name] !== null) {
        return model[name];
    }
    if (typeof field.props.defaultValue !== "undefined") {
        return field.props.defaultValue;
    }
    return "";
}

/** Fields whose `visible_when` condition is satisfied by the current values. */
export function visibleFields(fields: FieldDefinition[], values: FormValues): FieldDefinition[] {
    return fields.filter((field) => isVisible(field, values));
}

function isVisible(field: FieldDefinition, values: FormValues): boolean {
    if (!field.visible_when) {
        return true;
    }
    return Object.entries(field.visible_when).every(([key, expected]) =>
        Array.isArray(expected) ? expected.includes(values[key]) : values[key] === expected,
    );
}

/** One-line summary of selected field values (collapsed list items). */
export function summarize(values: FormValues, fieldNames: string[]): string {
    return fieldNames
        .map((name) => values[name])
        .map((value) => (Array.isArray(value) ? value.join(", ") : value))
        .filter((value) => value !== "" && value != null)
        .join(" · ");
}
