<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Elysium Hospitality | Coming Soon</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,600" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            background: #1f2937 url('/imx/bg-masthead.jpg') center / cover no-repeat;
            color: #030712;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(31, 41, 55, 0.5);
        }
        main {
            position: relative;
            width: 100%;
            max-width: 560px;
            padding: 56px 32px 72px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.92) url('/imx/wave.svg') bottom / 100% auto no-repeat;
            text-align: center;
        }
        .brand {
            margin: 0;
            font-size: 1rem;
            line-height: 1.75rem;
            font-weight: 600;
            color: #ea580c;
        }
        h1 {
            margin: 8px 0 16px;
            font-size: clamp(2rem, 6vw, 3rem);
            line-height: 1.1;
            font-weight: 600;
            letter-spacing: -0.025em;
            text-wrap: balance;
        }
        .tag {
            display: inline-block;
            margin-bottom: 24px;
            padding: 8px 16px;
            border-radius: 8px;
            background: #ea580c;
            color: #fff;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: lowercase;
        }
        p.note { margin: 0; color: #374151; font-size: 1rem; line-height: 1.6; }
    </style>
</head>
<body>
    <main>
        <p class="brand">Elysium Hospitality</p>
        <h1>Something new is on the way.</h1>
        <span class="tag">coming soon</span>
        <p class="note">We're putting the finishing touches on our new site. Please check back soon.</p>
    </main>
</body>
</html>
