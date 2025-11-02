<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <a href="/" class="flex justify-center">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Welcome back
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Sign in to your account to continue
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-xl rounded-2xl sm:px-10 border border-gray-100">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

                <form class="space-y-6" method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                required
                                autofocus
                                autocomplete="username"
                                value="{{ old('email') }}"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out @error('email') border-red-500 @enderror"
                                placeholder="you@example.com"
                            />
                        </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input
                                id="password"
                                name="password"
                            type="password"
                                required
                                autocomplete="current-password"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 ease-in-out @error('password') border-red-500 @enderror"
                                placeholder="Enter your password"
                            />
                        </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input
                                id="remember_me"
                                name="remember"
                                type="checkbox"
                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded cursor-pointer"
                            />
                            <label for="remember_me" class="ml-2 block text-sm text-gray-700 cursor-pointer">
                                Remember me
            </label>
        </div>

            @if (Route::has('password.request'))
                            <div class="text-sm">
                                <a
                                    href="{{ route('password.request') }}"
                                    class="font-medium text-indigo-600 hover:text-indigo-500 transition duration-150 ease-in-out"
                                >
                                    Forgot password?
                                </a>
                            </div>
            @endif
                    </div>

                    <!-- Login Button -->
                    <div>
                        <button
                            type="submit"
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out transform hover:scale-[1.02] active:scale-[0.98]"
                        >
                            Sign in
                        </button>
                    </div>
                </form>

                <!-- Sign Up Section -->
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Don't have an account?</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a
                            href="{{ route('register.step1') }}"
                            class="w-full flex justify-center py-3 px-4 border-2 border-indigo-600 rounded-lg shadow-sm text-sm font-semibold text-indigo-600 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out transform hover:scale-[1.02] active:scale-[0.98]"
                        >
                            Create new account
                        </a>
                    </div>
                </div>
            </div>

            <!-- Additional Help Text -->
            <p class="mt-6 text-center text-sm text-gray-600">
                By signing in, you agree to our
                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Terms of Service</a>
                and
                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
            </p>
        </div>
    </div>
</x-guest-layout>
