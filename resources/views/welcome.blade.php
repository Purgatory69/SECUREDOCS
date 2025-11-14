<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SecureDocs</title>

  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet" />
  
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    body { 
      font-family: 'Poppins', sans-serif; 
    }
    .unified-section {
      /* Sets a consistent, large minimum height */
      min-height: 90vh;

      /* Uses flex to vertically center the content inside */
      display: flex;
      align-items: center;

      /* Sets large, uniform padding (top/bottom, left/right) */
      padding: 6rem 4rem;
    }
  </style>
</head>
<body class="bg-[#0f0f23] text-white min-h-screen">

  <div class="bg-[#141326] px-6 py-6 sticky top-0 z-50">
      <div class="flex items-center justify-between w-full">
        <img src="logo-white.png" class="w-10 h-10" alt="Logo" />
          <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
          <h1 class="text-xl font-extrabold">SECURE<span class="text-[#ff9c00]">DOCS</span></h1>          </div>
          <div class="flex items-center gap-6">
          <a href="/login" class="text-sm font-medium transition-all duration-200 hover:text-[#ff9c00]">{{ __('auth.login') }}</a>
          <a href="/register" class="bg-[#ff9c00] text-black px-4 py-2 rounded-full font-bold transition-all duration-200 hover:brightness-110">{{ __('auth.signup') }}</a>
          </div>
      </div>
  </div>

  <section class="bg-[#1D1D2F] unified-section relative overflow-hidden">
    <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-12 relative z-10">
      <div class="w-full lg:w-1/2">
        <h2 class="text-5xl font-extrabold leading-tight mb-4">{!! __('auth.hero_sec_11')!!}</h2>
        <p class="text-[#ff9c00] text-xl mb-8">{!! __('auth.hero_sec_12')!!}</p>
        <a href="/register" class="bg-[#ff9c00] text-black font-semibold px-6 py-3 rounded-full transition-all duration-200 hover:brightness-110 inline-block">{{ __('auth.try_for_free') }}</a>
      </div>
      <div class="w-full lg:w-1/2 flex justify-center lg:justify-end">
      </div>
    </div>
    <img src="hero-1.png" alt="Hero Illustration" class="absolute bottom-0 right-0 w-[40rem] h-[40rem] z-0 translate-x-[50px] translate-y-[70px]" />
  </section>

  <section class="bg-[#24243B] unified-section">
    <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row-reverse items-center gap-12">
      <div class="w-full lg:w-1/2 text-right">
        <h3 class="text-5xl font-extrabold mb-4">{!! __('auth.hero_sec_21')!!}</h3>
        <p class="text-[#ff9c00] text-xl">{!! __('auth.hero_sec_22')!!}</p>
      </div>
      <div class="w-full lg:w-1/2 flex justify-center lg:justify-start">
        <img src="hero-2.png" alt="Management icons" class="w-full max-w-md" />
      </div>
    </div>
  </section>

  <section class="bg-[#1D1D2F] unified-section">
    <div class="w-full max-w-7xl mx-auto flex flex-col lg:flex-row items-center gap-12">
      <div class="w-full lg:w-1/2">
        <h3 class="text-5xl font-extrabold mb-4">{!! __('auth.hero_sec_31')!!}</h3>
        <p class="text-[#ff9c00] text-xl">{!! __('auth.hero_sec_32')!!}</p>
      </div>
      <div class="w-full lg:w-1/2 flex justify-center lg:justify-end">
        <img src="hero-3.png" alt="Security icons" class="w-full max-w-md" />
      </div>
    </div>
  </section>

  <section class="bg-[#ff9c00] text-black unified-section relative overflow-hidden">

      <img src="hero-4.png" 
          class="absolute inset-0 w-full h-full object-cover z-0" />

      <div class="w-full max-w-7xl mx-auto text-center relative z-10">
          <h3 class="text-4xl font-extrabold mb-4">{!! __('auth.hero_sec_41')!!}</h3>
          <p class="text-xl mb-12">{!! __('auth.hero_sec_42')!!}</p>
          <a href="/register" class="bg-black text-white px-8 py-3 rounded-full font-semibold transition-all duration-200 hover:bg-white hover:text-black">{!! __('auth.use_sd_now')!!}</a>
      </div>
  </section>



  <div class="bg-[#141326] px-6 py-6">
      <div class="flex items-center justify-between w-full">
        <img src="logo-white.png" class="w-10 h-10" alt="Logo" />
          <div class="flex items-center space-x-3 absolute left-1/2 transform -translate-x-1/2">
            <span class="text-sm text-white">{!! __('auth.footer_text')!!}</span>
          </div>
          <!-- Dropdown Menu -->
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
  </div>


  <script>
// Language Dropdown Toggle
document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('language-toggle');
        const dropdown = document.getElementById('language-dropdown');
        
        if (toggleButton && dropdown) {
            toggleButton.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!toggleButton.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }
    });
</script>

</body>
</html>