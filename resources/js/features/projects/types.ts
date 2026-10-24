export interface Option {
    value: string | number;
    label: string;
}
export interface ProjectSummary {
    id: number;
    name: string;
    key: string;
    status: string;
    description: string | null;
}
export interface ProjectDetails extends ProjectSummary {
    description: string | null;
    created_at: string;
}
export interface ProjectWorkspace {
    id: number;
    name: string;
}
export interface ProjectMember {
    id: number;
    user: { id: number; name: string; email: string; status: string };
}
export interface ProjectFormData {
    fieldLabels: Record<'name' | 'key' | 'description', string>;
    fieldStates: Record<
        'name' | 'key' | 'description',
        { required: boolean; readOnly: boolean }
    >;
    defaults: {
        name: string;
        key: string;
        description: string | null;
        status: string;
    };
    validationRules: Record<
        'name' | 'key' | 'description',
        {
            required: boolean;
            maxLength: number;
            minLength?: number;
            pattern?: string;
        }
    >;
}
