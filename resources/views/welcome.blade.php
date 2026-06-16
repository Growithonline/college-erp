<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gaurangi Technologies — ERP Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', sans-serif; }
        .card { transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-4px); }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col">

    {{-- Header --}}
    <header class="bg-white shadow-sm py-4 px-6 flex items-center gap-3">
        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg">B</div>
        <div>
            <h1 class="text-lg font-bold text-gray-800 leading-tight">Gaurangi Technologies</h1>
            <p class="text-xs text-gray-500">College Management System</p>
        </div>
    </header>

    {{-- Main --}}
    <main class="flex-1 flex flex-col items-center justify-center px-4 py-12">

        <div class="text-center mb-10">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Welcome to ERP Portal</h2>
            <p class="text-gray-500 text-sm">Select your role to continue</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 w-full max-w-5xl">

            {{-- Institute Admin --}}
            <a href="{{ url('/login') }}" class="card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center gap-4 hover:shadow-xl">
                <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-base">Institute Admin</h3>
                    <p class="text-xs text-gray-500 mt-1">Manage your institution</p>
                </div>
                <span class="mt-auto w-full text-center bg-indigo-600 text-white text-sm py-2 rounded-lg font-medium">Login</span>
            </a>

            {{-- Staff --}}
            <a href="{{ url('/staff/login') }}" class="card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center gap-4 hover:shadow-xl">
                <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-base">Staff</h3>
                    <p class="text-xs text-gray-500 mt-1">Teachers & admin staff</p>
                </div>
                <span class="mt-auto w-full text-center bg-green-600 text-white text-sm py-2 rounded-lg font-medium">Login</span>
            </a>

            {{-- Center --}}
            <a href="{{ url('/center/login') }}" class="card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center gap-4 hover:shadow-xl">
                <div class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-base">Center</h3>
                    <p class="text-xs text-gray-500 mt-1">Study center portal</p>
                </div>
                <span class="mt-auto w-full text-center bg-orange-500 text-white text-sm py-2 rounded-lg font-medium">Login</span>
            </a>

            {{-- Channel Partner --}}
            <a href="{{ url('/partner/login') }}" class="card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center gap-4 hover:shadow-xl">
                <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-base">Channel Partner</h3>
                    <p class="text-xs text-gray-500 mt-1">Partner commission portal</p>
                </div>
                <span class="mt-auto w-full text-center bg-purple-600 text-white text-sm py-2 rounded-lg font-medium">Login</span>
            </a>

            {{-- Student --}}
            <a href="{{ url('/student/login') }}" class="card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center gap-4 hover:shadow-xl">
                <div class="w-16 h-16 rounded-full bg-teal-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422A12.083 12.083 0 0121 13c0 5.523-4.477 10-9 10S3 18.523 3 13c0-.538.068-1.06.196-1.562L12 14z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="font-semibold text-gray-800 text-base">Student</h3>
                    <p class="text-xs text-gray-500 mt-1">Student portal</p>
                </div>
                <span class="mt-auto w-full text-center bg-teal-600 text-white text-sm py-2 rounded-lg font-medium">Login</span>
            </a>

        </div>

    </main>

    {{-- Footer --}}
    <footer class="text-center py-4 text-xs text-gray-400">
        &copy; {{ date('Y') }} Gaurangi Technologies. All rights reserved.
    </footer>

</body>
</html>
