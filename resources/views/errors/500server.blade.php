<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 Internal Server Error</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        black: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        midnight: '#0f033a',
                        coral: '#d98e79',
                        blush: '#fce7eb',
                        dolphin: '#7f7797',
                        background: '#12151d',
                        pearl: '#f1e5e4',
                    }
                }
            }
        }
    </script>
</head>

<body class="font-black bg-background flex items-center justify-center min-h-screen p-10">
    <div class="mx-auto w-full max-w-screen-lg flex flex-col items-center justify-center gap-5 md:gap-10">
        <div class="relative w-full max-w-md">
            <div class="w-2/3 mx-auto overflow-hidden rounded-xl">
                <img src="/images/server.png" alt="500 Internal Server Error" class="w-full">
            </div>
        </div>

        <div class="text-center">
            <h1 class="text-4xl text-white drop-shadow-lg">Yikes!</h1>
            <p class="text-xl pt-2 text-coral">Something broke.</p>

            <p class="max-w-md mx-auto pt-6 font-normal text-sm text-gray-300 dark:text-dolphin leading-relaxed">
                Something went wrong on our end. Try again later or contact support.
            </p>

            <a href="{{ url('/') }}" class="group relative inline-block mt-10">
                <div class="flex items-center justify-center gap-3 rounded-xl bg-pearl px-5 py-2 text-gray-900 font-medium text-sm
                    transition duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 "> 

                    <span>Refresh</span>

                    <div class="transition duration-300 will-change-transform group-hover:translate-x-1 ">
                        <svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fillRule="evenodd"
                                d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z"
                                clipRule="evenodd" />
                        </svg>
                    </div>
                </div>

                <div class="absolute inset-0 -z-10 h-full w-full -translate-x-1.5 translate-y-1.5 rounded-xl bg-coral transition duration-300
                    group-hover:-translate-x-2 group-hover:translate-y-2 group-hover:bg-rose-300 ">
                </div>
            </a>
        </div>
    </div>
</body>
</html>