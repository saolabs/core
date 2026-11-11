<script type="module">
    const bootstrap = window.SaolaBootstrap;
    const expected = {{ \Illuminate\Support\Js::from($bootstrap) }};
    const entry = {{ \Illuminate\Support\Js::from($entry) }};
    const matches = payload => payload?.schema === 1 && payload.context === expected.context
        && payload.revision === expected.revision && Array.isArray(payload.router?.routes)
        && Object.hasOwn(payload.i18n?.catalogs || {}, expected.locale);
    let payload = await bootstrap?.ready;
    if (bootstrap && !matches(payload)) payload = await bootstrap.reload(expected.revision);
    if (matches(payload)) {
        bootstrap.payload = payload;
        window.APP_CONFIGS.router = payload.router;
        window.APP_CONFIGS.i18n = {...payload.i18n, locale: expected.locale};
        {!! $slot ?? '' !!}
        await import(entry);
    } else {
        console.error('Saola could not load the shared configuration for ' + expected.context + '.');
    }
</script>
