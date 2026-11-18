export default function VouchLogo({ size = 32, showText = true, className = '' }) {
    const textSize = Math.round(size * 0.72);

    return (
        <div className={`flex items-center gap-2 select-none ${className}`}>
            {/* Document-frame mark: four rounded corner brackets */}
            <svg
                width={size}
                height={size}
                viewBox="0 0 48 48"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                {/* Top-left corner */}
                <path
                    d="M6 19V11C6 8.24 8.24 6 11 6H19"
                    stroke="currentColor"
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                {/* Top-right corner */}
                <path
                    d="M29 6H37C39.76 6 42 8.24 42 11V19"
                    stroke="currentColor"
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                {/* Bottom-right corner */}
                <path
                    d="M42 29V37C42 39.76 39.76 42 37 42H29"
                    stroke="currentColor"
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
                {/* Bottom-left corner */}
                <path
                    d="M19 42H11C8.24 42 6 39.76 6 37V29"
                    stroke="currentColor"
                    strokeWidth="4"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>

            {showText && (
                <span
                    style={{
                        fontWeight: 700,
                        fontSize: textSize,
                        lineHeight: 1,
                        letterSpacing: '-0.02em',
                        fontFamily: 'Inter, system-ui, -apple-system, sans-serif',
                    }}
                    className="text-foreground"
                >
                    Vouch
                </span>
            )}
        </div>
    );
}
