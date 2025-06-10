<!-- nigga balls -->
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1" name="viewport"/>
  <title>SecureDocs</title>
  <link rel="icon" type="image/png" href="{{ asset('logo-white.png') }}">


  @vite(['resources/css/app.css', 'resources/js/app.js'])

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
  <style>
    body {
      font-family: "Poppins", sans-serif;
    }
  </style>
</head>
<body class="bg-gradient-to-b from-[#0f0f23] to-[#1a2a5a] min-h-screen flex flex-col">

  <header class="flex items-center justify-between px-10 sm:px-20 md:px-32 py-10">
    <img 
      alt="SecureDocs logo with stylized SD and stacked documents icon in white" 
      class="w-12 h-12" 
      height="48" 
      src="logo-white.png" 
      width="48" 
    />
    <h1 class="text-white text-xl sm:text-2xl font-extrabold tracking-tight leading-none">
      SECURE<span class="text-[#ff9c00]">DOCS</span>
    </h1>
    <a href="{{ route('login') }}">
      <button class="bg-[#ff9c00] text-black font-extrabold text-sm sm:text-base rounded-full px-6 py-2 hover:brightness-110 transition" type="button">
        LOGIN
      </button>
    </a>
  </header>

  <main class="flex flex-col lg:flex-row items-center justify-between flex-grow px-10 sm:px-20 md:px-32 mt-10">
    <section class="max-w-xl lg:max-w-lg mb-16 lg:mb-0 px-6 sm:px-12">
      <h2 class="text-white text-3xl sm:text-4xl font-extrabold leading-tight mb-6">
        Effortless Access.<br/>
        Unwavering Security.
      </h2>
      <p class="text-[#ff9c00] text-lg sm:text-xl font-medium mb-12 max-w-md leading-snug">
        Time to fortify your files.<br/>
        The future of document trust is here!
      </p>
      <div class="flex justify-center">
        <a href="{{ route('register') }}">
          <button class="bg-[#ff9c00] text-black font-semibold text-base rounded-full px-8 py-3 hover:brightness-110 transition" type="button">
            Try for <span class="font-extrabold">FREE!</span>
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
        src="{{ asset('hero-clipart-1.png') }}" 
        width="40"
      />
      <p class="max-w-[350px]">Organize, access, and protect your documents!</p>
    </div>
    <div class="flex items-center gap-6 max-w-xs text-[#ff9c00] text-base sm:text-lg font-medium px-4">
      <img 
        alt="Blockchain icon" 
        class="w-10 h-10 flex-shrink-0" 
        height="40" 
        src="{{ asset('hero-clipart-2.png') }}" 
        width="40"
      />
      <p class="max-w-[280px]">Authenticate with blockchain technology.</p>
    </div>
    <div class="flex items-center gap-6 max-w-xs text-[#ff9c00] text-base sm:text-lg font-medium px-4">
      <img 
        alt="AI robot icon" 
        class="w-10 h-10 flex-shrink-0" 
        height="40" 
        src="{{ asset('hero-clipart-3.png') }}" 
        width="40"
      />
      <p class="max-w-[280px]">Need a hand? Use our smart AI assistant.</p>
    </div>
  </footer>

</body>
</html>

