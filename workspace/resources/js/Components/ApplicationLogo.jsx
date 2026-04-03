export default function ApplicationLogo({
    className = '',
    iconClassName = 'h-10 w-10',
    lightIconClassName,
    darkIconClassName,
}) {
    const resolvedLightIconClassName = lightIconClassName ?? iconClassName;
    const resolvedDarkIconClassName = darkIconClassName ?? iconClassName;

    return (
        <div className={`inline-flex items-center gap-3 ${className}`.trim()}>
            <img
                src="/images/truetrack-logo-light.png"
                alt="TrueTrack logo"
                className={`${resolvedLightIconClassName} block dark:hidden object-contain`}
            />

            <img
                src="/images/truetrack-logo-dark.png"
                alt="TrueTrack logo"
                className={`${resolvedDarkIconClassName} hidden dark:block object-contain`}
            />

        </div>
    );
}
