<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => 'Elysium Hospitality'])
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,400i,500,500i,600" rel="stylesheet" />
    @livewireStyles
</head>
<body class="dark min-h-screen bg-ink font-sans text-silver antialiased selection:bg-gold/40 selection:text-white">
    <div class="pointer-events-none fixed inset-0 -z-10" aria-hidden="true">
        <img src="/imx/bg-masthead.jpg" alt="" class="size-full object-cover opacity-70" />
        <div class="absolute inset-0 bg-gradient-to-b from-ink/50 via-ink/60 to-ink"></div>
    </div>

    <header class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-baseline gap-3">
            <span class="font-serif text-3xl font-medium tracking-wide text-white">Elysium</span>
            <span class="hidden text-[0.65rem] font-medium tracking-[0.35em] text-gold uppercase sm:inline">Hospitality</span>
        </a>
        <nav class="flex items-center gap-8 text-sm">
            <a href="#consulting" class="hidden text-silver/80 transition hover:text-white md:inline">Consulting</a>
            <a href="#services" class="hidden text-silver/80 transition hover:text-white md:inline">Services</a>
            <a href="#contact" class="border border-gold/60 px-4 py-2 tracking-wide text-gold-light transition hover:border-gold hover:bg-gold hover:text-ink">Get in touch</a>
        </nav>
    </header>

    <section class="mx-auto flex min-h-[72vh] max-w-6xl flex-col justify-end px-6 pt-16 pb-20 lg:px-8">
        <p class="text-xs font-medium tracking-[0.3em] text-gold uppercase">The hard part is already done</p>
        <h1
            class="mt-6 max-w-4xl font-serif text-5xl leading-[1.05] font-normal text-white sm:text-7xl"
            x-data="{
                words: ['restaurant', 'bar', 'café', 'hotel', 'bistro'],
                index: 0,
                visible: true,
                init() {
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }
                    setInterval(() => {
                        this.visible = false;
                        setTimeout(() => {
                            this.index = (this.index + 1) % this.words.length;
                            this.visible = true;
                        }, 450);
                    }, 3000);
                },
            }"
        >
            Everything you need to get your
            <em class="text-gold-light transition-opacity duration-500" :class="visible ? 'opacity-100' : 'opacity-0'" x-text="words[index]">restaurant</em>
            back on track.
        </h1>
        <div class="mt-10 flex flex-col gap-6 border-t border-silver/15 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="max-w-xl text-lg/8 text-silver/85">
                Hospitality consulting backed by over 25 years in the industry, for independent operators across Northern Indiana and Michiana.
            </p>
            <p class="text-xs tracking-[0.25em] text-silver/60 uppercase lg:whitespace-nowrap">Restaurants · Bars · Cafés · Hotels · Bistros</p>
        </div>
    </section>

    <main class="bg-ink">
        {{-- Consulting --}}
        <section id="consulting" class="mx-auto grid max-w-6xl gap-12 px-6 py-24 lg:grid-cols-[1fr_1.4fr] lg:gap-20 lg:px-8">
            <div>
                <p class="text-xs font-medium tracking-[0.3em] text-gold uppercase">Consulting</p>
                <h2 class="mt-4 font-serif text-4xl/tight text-white sm:text-5xl/tight">An honest look at the whole house.</h2>
                <p class="mt-6 max-w-md text-base/7 text-silver/75">
                    Elysium pulls from over 25 years of experience in the hospitality industry.
                </p>
            </div>

            <ol class="divide-y divide-silver/10 border-y border-silver/10">
                <li class="grid grid-cols-[3rem_1fr] gap-4 py-8">
                    <span class="font-serif text-2xl text-gold italic">i.</span>
                    <div>
                        <h3 class="text-lg font-medium text-white">Restaurant Evaluation</h3>
                        <p class="mt-2 text-base/7 text-silver/75">We'll look at every aspect of your business — service, menu, staffing, ambiance, and more.</p>
                    </div>
                </li>
                <li class="grid grid-cols-[3rem_1fr] gap-4 py-8">
                    <span class="font-serif text-2xl text-gold italic">ii.</span>
                    <div>
                        <h3 class="text-lg font-medium text-white">Hospitality Consulting</h3>
                        <p class="mt-2 text-base/7 text-silver/75">
                            Hospitality goes beyond the service industry — it's the heart of every unforgettable experience and the key to building customer loyalty. It's the defining factor that makes a business stand out in a competitive market.
                        </p>
                    </div>
                </li>
                <li class="grid grid-cols-[3rem_1fr] gap-4 py-8">
                    <span class="font-serif text-2xl text-gold italic">iii.</span>
                    <div>
                        <h3 class="text-lg font-medium text-white">COGS Evaluation</h3>
                        <p class="mt-2 text-base/7 text-silver/75">The largest controllable of any business is its cost of goods. We can help analyze your costs, from dollars lost to dimes.</p>
                    </div>
                </li>
            </ol>
        </section>

        {{-- Services --}}
        <section id="services" class="bg-charcoal">
            <div class="mx-auto max-w-6xl px-6 py-24 lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-medium tracking-[0.3em] text-gold uppercase">Services</p>
                        <h2 class="mt-4 font-serif text-4xl text-white sm:text-5xl">Ongoing support</h2>
                    </div>
                    <p class="max-w-sm text-base/7 text-silver/75">We offer a few ongoing services to our clients for minimal costs.</p>
                </div>

                <div class="mt-14 grid gap-px bg-silver/10 sm:grid-cols-2">
                    <div class="flex flex-col bg-charcoal py-8 sm:pe-12">
                        <h3 class="font-serif text-3xl text-white">Permit Tracking</h3>
                        <p class="mt-3 flex-1 text-base/7 text-silver/75">We'll help you track your permits for employees and your business.</p>
                        <a href="#contact" class="group mt-6 inline-flex items-center gap-2 self-start text-sm tracking-wide text-gold-light">
                            Contact us
                            <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                        </a>
                    </div>
                    <div class="flex flex-col bg-charcoal py-8 sm:ps-12">
                        <h3 class="font-serif text-3xl text-white">Tip Tracking</h3>
                        <p class="mt-3 flex-1 text-base/7 text-silver/75">
                            Many small businesses do not use standard tipout procedures, and most payroll software gives minimal customization. We can help with that.
                        </p>
                        <a href="#contact" class="group mt-6 inline-flex items-center gap-2 self-start text-sm tracking-wide text-gold-light">
                            Learn more
                            <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Local service --}}
        <section class="mx-auto grid max-w-6xl items-center gap-12 px-6 py-24 md:grid-cols-2 lg:px-8">
            <div>
                <p class="text-xs font-medium tracking-[0.3em] text-gold uppercase">Local Service</p>
                <h2 class="mt-4 font-serif text-4xl/tight text-white sm:text-5xl/tight">Northern Indiana into Michiana.</h2>
                <p class="mt-6 max-w-md text-base/7 text-silver/75">If we're outside of the circle, we can still work together.</p>
            </div>
            <div class="relative mx-auto w-full max-w-lg md:max-w-none">
                <div class="absolute top-[50%] left-[49.7%] size-[30%] -translate-1/2 rounded-full bg-gold/25 blur-2xl" aria-hidden="true"></div>
                <img
                    src="/imx/states.svg?v=3"
                    alt="Map of the Northern Indiana and Michiana service area"
                    class="relative w-full opacity-45 invert [mask-image:radial-gradient(circle_at_center,black_45%,transparent_72%)]"
                />
            </div>
        </section>

        {{-- Contact --}}
        <section id="contact" class="border-t border-silver/10 bg-charcoal">
            <div class="mx-auto grid max-w-6xl gap-12 px-6 py-24 lg:grid-cols-[1fr_1.4fr] lg:gap-20 lg:px-8">
                <div>
                    <p class="text-xs font-medium tracking-[0.3em] text-gold uppercase">Contact</p>
                    <h2 class="mt-4 font-serif text-4xl/tight text-white sm:text-5xl/tight">Let's talk about your place.</h2>
                    <p class="mt-6 max-w-md text-base/7 text-silver/75">Tell us a little about your business and we'll get back to you.</p>
                </div>
                @livewire('contact-form')
            </div>
        </section>
    </main>

    <footer class="bg-ink">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-6 py-10 text-xs tracking-wide text-silver/50 sm:flex-row sm:justify-between lg:px-8">
            <span><span class="font-serif text-base text-silver/80">Elysium</span> Hospitality</span>
            <span>&copy; {{ date('Y') }} Elysium Hospitality</span>
        </div>
    </footer>

        {{-- Pricing for service (draft, not shown)
        <div class="bg-white col-span-2 align-items-center justify-content-center text-center opacity-90 p-4 rounded-md">
            <div class="flex flex-col">
                <h2 class="text-5xl w-full font-bold pb-2 tracking-wide">Simple Pricing</h2>
                <div class="mb-4 text-gray-500 text-md text-lg ">We specialize in helping small businesses thrive, and that services should be affordable for everyone.</div>
                <div class="flex flex-col sm:flex-row gap-4 border border-gray-200 rounded-md p-4">
                    <div class="sm:w-2/3 flex-col sm:flex-row">
                        <div>If you don't need the consult, just use the tools!</div>
                        <div class="flex flex-row gap-2 items-center pb-4">
                            <span class="text-orange-600 tracking-wide font-semibold">What's Included</span>
                            <hr class="bg-gray-200 h-[1px] flex-auto hidden"/>
                        </div>
                        <div>
                            <ul class='text-sm leading-5 gap-4 grid grid-cols-2'>
                                <li class="flex gap-3 text-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Track Employee Permits/Licenses</li>
                                <li class="flex gap-3 text-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Track Organization's Permits/Licenses
                                </li>
                                <li class="flex gap-3 text-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Be notified in-app or via email of upcoming expirations
                                </li>
                                <li class="flex gap-3 text-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke-width="1.5" stroke="currentColor" class="size-4 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Optionally notify employee of upcoming expirations
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="sm:w-1/3 flex-col sm:flex-row bg-gray-100 border border-gray-200 rounded-md items-center p-4">
                        <div class="font-semibold leading-5 pb-2 text-gray-700">Only pay for what you need</div>
                        <div class="font-bold text-5xl p-2 tracking-tight">$5.99<span class="text-base items-end tracking-normal ps-2">USD</span></div>
                        <div class="text-sm tracking-wide mb-2 font-extralight text-gray-700">
                            Invoices and receipts available for easy company reimbursement
                        </div>
                        <flux:button>Sign Up Now!</flux:button>
                    </div>
                    
                </div>
            </div>
        </div> 
        --}}

    @if (config('app.portal_enabled'))
        <div class="hidden h-14.5 lg:block"></div>
    @endif
    @livewireScripts
</body>
</html>
