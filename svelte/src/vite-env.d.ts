/*
South African Theological Seminary
 */

/// <reference types="svelte" />
/// <reference types="vite/client" />

declare interface Window {
    M: {
        cfg: {
            wwwroot: string;
            sesskey: string;
        };
        str: {
            langconfig: {
                localecldr: string;
            };
        };
    };
    require: (deps: string[], callback: (...modules: unknown[]) => void) => void;
    local_satsmail_navbar_data: Record<string, unknown> | undefined;
    local_satsmail_view_data: Record<string, unknown> | undefined;
}

