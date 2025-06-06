<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1" name="viewport" />
  <title>SecureDocs</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap"
    rel="stylesheet"
  />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    rel="stylesheet"
  />
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
  </style>
</head>
<body class="bg-gradient-to-b from-[#0B0B1E] to-[#0F2A5B] min-h-screen flex flex-col">
  <header class="flex items-center justify-between px-6 py-5 max-w-7xl mx-auto w-full">
    <img
      alt="SecureDocs logo, stylized letters S and D stacked with a document icon"
      class="w-10 h-10"
      height="40"
      src="https://storage.googleapis.com/a1aa/image/78ad8344-da4c-4e3f-d5fb-0a123857bf39.jpg"
      width="40"
    />
    <div class="flex items-center space-x-6 flex-1 justify-center">
      <h1 class="text-white font-extrabold text-lg">
        SECURE<span class="text-[#FF9B00]">DOCS</span>
      </h1>
    </div>
 {{-- Add this anchor tag around the button --}}
 <a href="{{ route('login') }}">
    <button
      class="bg-[#FF9B00] text-black font-bold text-xs rounded-full px-5 py-2 hover:brightness-110 transition"
        type="button"
    >
      LOGIN
    </button>
 </a>
  </header>
  <main
    class="
      flex
      flex-col
      md:flex-row
      items-center
      justify-between max-w-7xl mx-auto px-6 flex-grow w-full"
  >
    <section class="text-white max-w-lg md:max-w-md lg:max-w-lg">
      <h2 class="font-extrabold text-3xl leading-tight mb-3">
        Effortless Access.<br />
        Unwavering Security.
      </h2>
      <p class="text-[#FF9B00] text-lg mb-6 leading-snug">
        Time to fortify your files.<br />
        The future of document trust is here!
      </p>
      <a href="{{ route('register') }}">
        <button
          class="bg-[#FF9B00] text-black font-bold text-sm rounded-full px-6 py-2 hover:brightness-110 transition"
          type="button"
        >
          Try for <span class="uppercase">FREE!</span>
        </button>
      </a>
    </section>
    
    <section class="mt-10 md:mt-0 md:ml-10 flex-shrink-0">
      <img
        alt="Illustration of people interacting with a secure login screen on a large laptop with security icons and cloud"
        class="w-full max-w-[480px] h-auto"
        height="280"
        src="https://storage.googleapis.com/a1aa/image/764e6443-4fb9-495c-81b0-99e1614c11ab.jpg"
        width="480"
      />
      {{-- Add a login link --}}
      <p class="mt-4 text-sm text-gray-600">
        Already have an account? <a href="{{ route('login') }}" class="underline text-gray-900">Log in</a>
      </p>

    </section>
  </main>
  <footer
    class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row justify-between text-[#FF9B00] text-xs font-semibold w-full"
  >
    <div class="flex items-center space-x-3 mb-4 md:mb-0 max-w-xs">
      <i class="fas fa-file-alt text-lg flex-shrink-0"></i>
      <p>Organize, access, and protect your documents!</p>
    </div>
    <div class="flex items-center space-x-3 mb-4 md:mb-0 max-w-xs">
      <i class="fas fa-cube text-lg flex-shrink-0"></i>
      <p>Authenticate with blockchain technology.</p>
    </div>
    <div class="flex items-center space-x-3 max-w-xs">
      <i class="fas fa-robot text-lg flex-shrink-0"></i>
      <p>Need a hand? Use our smart AI assistant.</p>
    </div>
  </footer>
</body>
</html>