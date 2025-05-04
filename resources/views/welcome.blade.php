<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

            @livewireStyles
            @include('partials.head')

    </head>
    <body class="bg-gray-800 text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
        <img class="absolute left-0 top-0 z-0 opacity-50" src="/imx/bg-masthead.jpg" />
        <header class="w-full lg:max-w-4xl max-w-[335px] text-sm mb-6 not-has-[nav]:hidden">
            {{-- @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4">
                    @auth
                        <a
                            href="{{ url('/dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal"
                        >
                            Dashboard
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] text-[#1b1b18] border border-transparent hover:border-[#19140035] dark:hover:border-[#3E3E3A] rounded-sm text-sm leading-normal"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-[#EDEDEC] border-[#19140035] hover:border-[#1915014a] border text-[#1b1b18] dark:border-[#3E3E3A] dark:hover:border-[#62605b] rounded-sm text-sm leading-normal">
                                Register
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif --}}
        </header>
    <div class="text-gray-950 grid grid-cols-2 max-w-5xl mx-auto gap-4 lg:gap-5">
        <div class="col-span-2 align-items-center justify-content-center text-center opacity-90">
            <div class="p-12 rounded-lg bg-white opacity-90 bg-bottom bg-no-repeat " style="background-image: url('{{ asset('imx/wave.svg') }}') ">
                <h2 class="text-center text-base/7 font-semibold text-orange-600">
                    THE HARD PART IS ALREADY DONE
                </h2>
                <p
                    class="mx-auto mt-2 max-w-lg text-center text-4xl font-semibold tracking-tight text-balance text-gray-950 sm:text-5xl">
                    Everything you need to get your
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4 w-full pt-4">
                    <span class="rounded-lg px-4 py-2  font-semibold tracking-widest items-center text-white bg-orange-600">restaurant</span>
                    <span class="rounded-lg px-4 py-2  font-semibold tracking-widest items-center text-white bg-orange-600">bar</span>
                    <span class="rounded-lg px-4 py-2  font-semibold tracking-widest items-center text-white bg-orange-600">cafe</span>
                    <span class="rounded-lg px-4 py-2  font-semibold tracking-widest items-center text-white bg-orange-600">hotel</span>
                    <span class="rounded-lg px-4 py-2  font-semibold tracking-widest items-center text-white bg-orange-600">bistro</span>
                </div>
                <p
                    class="mx-auto mt-2 max-w-lg text-center text-4xl font-semibold tracking-tight text-balance text-gray-950 sm:text-5xl">
                    back on track</p>
            </div>
        </div>


        {{-- Consulting --}}
        <div class="p-4 rounded-lg bg-white opacity-90 col-span-2 sm:col-span-1">
            <h2 class="font-semibold text-lg w-full text-center">Consulting</h2>
            <p class="mt-2 max-w-lg text-sm/6 text-gray-700 max-lg:text-center mb-4">Elysium pulls from over 25 years of
                experience in the hospitality industry.</p>
            <div class="flex items-center p-4 rounded-md bg-gray-200 mb-4"><!-- item container -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="hidden sm:block size-20">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 0 1-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0 1 12 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M13.125 12h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125M20.625 12c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5M12 14.625v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 14.625c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m0 1.5v-1.5m0 0c0-.621.504-1.125 1.125-1.125m0 0h7.5" />
                </svg>
                <div class="ps-4">
                    <h2 class="font-semibold">Restaurant Evaluation</h2>
                    We'll look at every aspect of your business - service, menu, staffing, ambiance, and more.
                </div>
            </div>
            <div class="flex items-center p-2 mb-4"><!-- item container -->
                <div class="pe-4">
                    <h2 class="font-semibold">Hospitality Consulting</h2>
                    Hospitality goes beyond the service industry—it's the heart of every unforgettable experience and
                    the key to building customer loyalty. It’s the defining factor that makes a business stand out in a
                    competitive market.
                </div>
                {{-- <div class="flex items-center p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="hidden sm:block size-20">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />
                    </svg>
                </div> --}}
            </div>
            <div class="flex items-center p-4 rounded-md bg-gray-200"><!-- item container -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="hidden sm:block size-20">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
                <div class="ps-4">
                    <h2 class="font-semibold">COGS Evaluation</h2>
                    The largest controllable of any business is their cost of goods. We can help analyze your costs, to
                    dollars lost to dimes.
                </div>
            </div>
            {{-- Operations --}}
            {{-- Labor --}}
            {{-- Hospitality --}}
            {{-- Service Training --}}
            {{-- Management Training --}}
        </div>
        {{-- States --}}
        <div class="flex flex-col p-4 rounded-lg bg-white opacity-90 col-span-2 sm:col-span-1">
            <h2 class="font-semibold text-lg w-full text-center">Local Service</h2>
            <p class="mt-2 max-w-lg text-sm/6 text-gray-700 max-lg:text-center mb-4">
                Serving local Northern Indiana into Michiana. If we're outside of the circle, we can still work together! 
            </p>
            <div class="rounded-lg bg-top-[-100px] bg-left-[-100] h-50 flex-1 border border-gray-800 shadow-md hidden sm:block" style="background-image: url('{{ asset('imx/states.svg') }}') "></div>
        </div>
        {{-- Services --}}
        <div class="p-4 rounded-lg bg-white opacity-90  col-span-2">
            <h2 class="font-semibold text-lg w-full text-center">Services</h2>
            <p class="mt-2 max-w-lg text-sm/6 text-gray-700 max-lg:text-center mb-4">We offer a few ongoing services to
                our clients for minimal costs.
            </p>

            <div class="flex flex-col sm:flex-row">
                <div class="flex items-center p-4 rounded-md bg-gray-200 mb-4 sm:w-1/2"><!-- item container -->
                    <div class="pe-4">
                        <h2 class="font-semibold">Permit Tracking</h2>
                        <div>We'll help you track your permits for employees and your business.<br />
                            <a href="/contact" class="text-orange-600 font-semibold cursor-pointer">Contact Us</a>
                        </div>
                    </div>
                    {{-- <div class="pe-4">
                        <h2 class="font-semibold">Permit Tracking</h2>
                        <div>Track your employee and business permits online with our tracker! Recieve emails before permits
                            are due for your employees or location.<br />
                            <a href="#" class="text-orange-600 font-semibold cursor-pointer">Learn More</a>
                        </div>
                    </div> --}}
                    <div class="flex items-center p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" class="hidden sm:block size-20">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Zm6-10.125a1.875 1.875 0 1 1-3.75 0 1.875 1.875 0 0 1 3.75 0Zm1.294 6.336a6.721 6.721 0 0 1-3.17.789 6.721 6.721 0 0 1-3.168-.789 3.376 3.376 0 0 1 6.338 0Z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-center rounded-md flex-1"><!-- item container -->
                    <div class="flex items-center p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="hidden sm:block size-20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                          </svg>
                    </div>
                    <div class="pe-4">
                        <h2 class="font-semibold">Tip Tracking</h2>
                        <div>Many small businesses do not use standard tipout procedures. Most payroll software gives minimal customization. We can help with that!<br />
                            <a href="#" class="text-orange-600 font-semibold cursor-pointer">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

   


        <div class="col-span-2 rounded-md bg-white opacity-90"><!-- item container -->
            @livewire('contact-form')
        </div><!-- End Container -->
        {{-- Pricing for service
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

        

        <!-- End Container -->
    </div>
    @if (Route::has('login'))
    <div class="h-14.5 hidden lg:block"></div>
    @endif
    @LivewireScripts
</body>
</html>