/**
 * Shared links for Vouch's related software and source-code license.
 * These links do not replace product terms, privacy notices, or the MIT license.
 */
export default function VouchEcosystemLinks() {
    return (
        <nav aria-label="Company, related software and licensing" className="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
            <a href="https://tetranyble.com/" className="hover:text-foreground hover:underline underline-offset-4" target="_blank" rel="noopener noreferrer">Tetranyble</a>
            <a href="https://pensuh.com/" className="hover:text-foreground hover:underline underline-offset-4" target="_blank" rel="noopener noreferrer">Pensuh</a>
            <a href="https://github.com/Tetranyble/vouch" className="hover:text-foreground hover:underline underline-offset-4" target="_blank" rel="noopener noreferrer">Source code</a>
            <a href="https://github.com/Tetranyble/vouch/blob/main/LICENSE" className="hover:text-foreground hover:underline underline-offset-4" target="_blank" rel="noopener noreferrer">MIT license</a>
        </nav>
    );
}
