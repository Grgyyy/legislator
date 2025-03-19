<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>

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
                <img src="/images/forbidden.png" alt="403 Forbidden" class="w-full">
            </div>
        </div>

        <div class="text-center">
            <h1 class="text-4xl text-white drop-shadow-lg">No Entry!</h1>
            <p class="text-xl pt-2 text-coral">You don't have access.</p>

            <p class="max-w-md mx-auto pt-6 font-normal text-sm text-gray-300 dark:text-dolphin leading-relaxed">
                It looks like you don't have the necessary permissions to view this page. 
                If you think this is an error, please contact support.
            </p>

            <a href="{{ url('/') }}" class="group relative inline-block mt-10">
                <div class="flex items-center justify-center gap-3 rounded-xl bg-pearl px-5 py-2 text-gray-900 font-medium text-sm
                    transition duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 "> 

                    <span>Go to Dashboard</span>

                    <div class="transition duration-300 will-change-transform group-hover:translate-x-1 ">
                        <svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                clip-rule="evenodd" />
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
