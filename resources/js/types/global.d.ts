declare global {
    function route(name: string, params?: string | number | Record<string, string | number | boolean | null>): string;
}

declare global {
    interface Window {
        axios: typeof import('axios').default;
    }
}
export {};
