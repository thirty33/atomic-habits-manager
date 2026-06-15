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

/** An option of a select-like field, as produced by PHP `SelectField`. */
interface FieldOption {
    text: string;
    value: unknown;
}

function fieldOptions(field: FieldDefinition | undefined): FieldOption[] | undefined {
    return field?.props.options as FieldOption[] | undefined;
}

/** Human label for a single value, resolving a select option to its text. */
function labelFor(field: FieldDefinition | undefined, value: unknown): string {
    const match = fieldOptions(field)?.find((option) => option.value === value);

    return match ? match.text : String(value);
}

/** Human-readable display of a field value (joins multi-value selects). */
export function displayValue(field: FieldDefinition | undefined, value: unknown): string {
    return Array.isArray(value)
        ? value.map((entry) => labelFor(field, entry)).join(", ")
        : labelFor(field, value);
}

/**
 * One-line summary of selected field values (collapsed list items). Resolves
 * select fields to their option label so the header shows "Algunos días de la
 * semana" instead of the raw enum value "weekly".
 */
export function summarize(values: FormValues, fields: FieldDefinition[], fieldNames: string[]): string {
    return fieldNames
        .map((name) => ({ field: fields.find((f) => f.props.name === name), value: values[name] }))
        .filter(({ value }) => value !== "" && value != null)
        .map(({ field, value }) => displayValue(field, value))
        .join(" · ");
}
