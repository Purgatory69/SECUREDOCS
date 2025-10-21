<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>SecureDocs</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

  @vite(['resources/css/app.css'])

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
    body {
      font-family: "Poppins", sans-serif;
    }
  </style>
</head>
<body class="bg-gradient-to-b from-[#0f0f23] to-[#1a2a5a] min-h-screen flex flex-col">

<!-- Language Toggle Button - Fixed Position Bottom Right -->
<div class="fixed bottom-6 right-6 z-50">
    <div class="relative">
        <!-- Toggle Button -->
        <button id="language-toggle" class="bg-[#3c3f58] text-white p-3 rounded-full shadow-lg transition
            style="transition: background-color 0.2s;"
            onmouseover="this.style.backgroundColor='#55597C';"
            onmouseout="this.style.backgroundColor='';">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
        </button>

        <!-- Dropdown Menu -->
        <div id="language-dropdown" style="background-color: #3c3f58; border: 3px solid #1F1F33" class="absolute bottom-full right-0 mb-2 hidden bg-[#3c3f58] rounded-lg shadow-xl overflow-hidden min-w-[140px]">
            <a href="{{ route('language.switch', 'en') }}"
                class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'en' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                @if(app()->getLocale() != 'en')
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';"
                @endif>
                <span class="mr-2">🇺🇸</span>
                English
            </a>
            <a href="{{ route('language.switch', 'fil') }}"
                class="flex items-center px-4 py-3 text-sm transition-colors {{ app()->getLocale() == 'fil' ? 'bg-[#f89c00] text-black font-bold' : 'text-white' }}"
                @if(app()->getLocale() != 'fil')
                    style="transition: background-color 0.2s;"
                    onmouseover="this.style.backgroundColor='#55597C';"
                    onmouseout="this.style.backgroundColor='';"
                @endif>
                <span class="mr-2">🇵🇭</span>
                Filipino
            </a>
        </div>
    </div>
</div>

<header class="grid grid-cols-3 items-center px-10 sm:px-20 md:px-32 py-10">
  <img 
    alt="SecureDocs logo with stylized SD and stacked documents icon in white" 
    class="w-12 h-12 justify-self-start" 
    height="48" 
    src="logo-white.png" 
    width="48" 
  />
  <h1 class="text-white text-xl sm:text-2xl font-extrabold tracking-tight leading-none justify-self-center">
      SECURE<span class="text-[#ff9c00]">DOCS</span>
    </h1>
    <div class="justify-self-end">
      <a href="/login">
        <button class="bg-[#ff9c00] text-black font-extrabold text-sm sm:text-base rounded-full px-6 py-2 hover:brightness-110 transition" type="button">
        {{ __('auth.login') }}
        </button>
      </a>
    </div>
  </header>

  <main class="flex flex-col lg:flex-row items-center justify-center lg:justify-start flex-grow px-10 sm:px-20 md:px-32 mt-10 gap-8 lg:gap-16">
  <section class="w-full lg:w-2/3 mb-16 lg:mb-0 px-6 sm:px-12">
      <h2 class="text-white text-3xl sm:text-4xl font-extrabold leading-tight mb-6">{!! __('auth.hero_mid_1')!!}
      </h2>
      <p class="text-[#ff9c00] text-lg sm:text-xl font-medium mb-12 max-w-md leading-snug">{!! __('auth.hero_mid_2')!!}
      </p>
      <div class="flex justify-left">
        <!-- Replace with your actual register route when converting to Blade -->
        <a href="/register">
          <button class="bg-[#ff9c00] text-black font-semibold text-base rounded-full px-8 py-3 hover:brightness-110 transition" type="button">
            {{ __('auth.try_for_free') }}
          </button>
        </a>
      </div>
    </section>

    <section class="flex-shrink-0 w-[600px] mt-10 lg:mt-0 px-6 sm:px-12">
      <img 
        alt="Illustration of people interacting with a secure login screen" 
        class="w-full h-auto" 
        height="350" 
        src="Hero_clipart2.png" 
        width="600"
      />
    </section>
  </main>

  <footer class="flex flex-col sm:flex-row justify-between items-center px-10 sm:px-20 md:px-32 py-12 gap-8 sm:gap-0 mt-10">
    <div class="flex items-center gap-6 max-w-xs text-[#ff9c00] text-base sm:text-lg font-medium px-4">
      <img 
        alt="Document icon" 
        class="w-10 h-10 flex-shrink-0" 
        height="40" 
        src="hero-clipart-1.png" 
        width="40"
      />
      <p class="max-w-[350px]">{{ __('auth.footer_1') }}</p>
    </div>
    <div class="flex items-center gap-6 max-w-xs text-[#ff9c00] text-base sm:text-lg font-medium px-4">
      <img 
        alt="Blockchain icon" 
        class="w-10 h-10 flex-shrink-0" 
        height="40" 
        src="hero-clipart-2.png" 
        width="40"
      />
      <p class="max-w-[280px]">{{ __('auth.footer_2') }}</p>
    </div>
    <div class="flex items-center gap-6 max-w-xs text-[#ff9c00] text-base sm:text-lg font-medium px-4">
      <img 
        alt="AI robot icon" 
        class="w-10 h-10 flex-shrink-0" 
        height="40" 
        src="hero-clipart-3.png" 
        width="40"
      />
      <p class="max-w-[280px]">{{ __('auth.footer_3') }}</p>
    </div>
  </footer>

  <!-- Language Dropdown JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('language-toggle');
        const dropdown = document.getElementById('language-dropdown');
        
        console.log('Toggle button:', toggleButton);
        console.log('Dropdown:', dropdown);
        
        if (toggleButton && dropdown) {
            toggleButton.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('Toggle clicked');
                dropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!toggleButton.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        } else {
            console.error('Language toggle elements not found');
        }
    });
  </script>

</body>
</html>