@register('resources')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for navigation links
            document.querySelectorAll('.docs-nav a[href^="#"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });

                        // Update active link
                        document.querySelectorAll('.docs-nav a').forEach(l => l.classList.remove('active'));
                        this.classList.add('active');

                        // Update URL without triggering navigation
                        history.pushState(null, null, this.getAttribute('href'));
                    }
                });
            });

        });
    </script>

    <style>
        /* Fix grid layout for docs page */
        .docs-page .row {
            margin: 0 -var(--space-xs);
        }

        .docs-page .col-3 {
            padding-right: var(--space-md);
        }

        .docs-page .col-9 {
            padding-left: var(--space-md);
        }

        .docs-content h2 {
            border-bottom: 2px solid var(--bg-light);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .docs-content h3 {
            color: var(--primary-color);
            margin-top: 2rem;
        }

        .docs-nav {
            position: sticky;
            top: 2rem;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
            padding: var(--space-md);
            background: var(--bg-primary, #fff);
            border-radius: var(--radius-md, 8px);
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
            /* Prevent flex item from overflowing */
        }

        .docs-content {
            min-width: 0;
            /* Prevent flex item from overflowing */
            width: 100%;
        }

        .docs-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .docs-nav li {
            margin-bottom: var(--space-xs, 0.5rem);
        }

        .docs-nav a {
            display: block;
            padding: var(--space-xs, 0.5rem) var(--space-sm, 0.75rem);
            color: var(--text-primary, #333);
            text-decoration: none;
            border-radius: var(--radius-sm, 4px);
            transition: background-color 0.2s ease;
        }

        .docs-nav a:hover,
        .docs-nav a.active {
            background-color: var(--primary-color, #007cba);
            color: var(--text-white, #fff);
        }

        @media (max-width: 768px) {

            .docs-page .col-3,
            .docs-page .col-9 {
                flex: 0 0 100%;
                max-width: 100%;
                padding: 0;
            }

            .docs-nav {
                position: static;
                margin-bottom: 2rem;
                max-height: none;
            }
        }
    </style>
@endRegister

