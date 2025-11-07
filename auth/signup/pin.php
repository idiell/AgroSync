<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign up Page</title>

  <!-- Lucide icons -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.js"></script>

  <!-- Your compiled Tailwind CSS (built by CLI) -->
  <link href="../../public/public.css" rel="stylesheet" />
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4 shadow-lg">
        <i data-lucide="fingerprint" class="w-8 h-8 text-white"></i>
      </div>
    </div>

    <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-2xl p-8 space-y-8 transition-all duration-500 hover:shadow-xl">
      <div class="text-center">
        <h2 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Welcome Back</h2>
        <p class="text-gray-500 mt-2">Please sign up to continue</p>
      </div>

      <form class="space-y-6">
        <div class="relative">
          <label class="block text-gray-700 text-sm font-medium mb-2" for="email">pin</label>
          <div class="relative">
            <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"></i>
            <input class=" border border-black w-10 h-20 flex" required>
            <input class=" border border-black w-10 h-20 flex" required>
            <input class=" border border-black w-10 h-20 flex" required>
            <input class=" border border-black w-10 h-20 flex" required>
            <input class=" border border-black w-10 h-20 flex" required>
            <input class=" border border-black w-10 h-20 flex" required>
          </div>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-xl hover:opacity-90 transition duration-200 hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
          Sign up
        </button>
      </form>

      <p class="text-center text-sm text-gray-600">
        have an account?
        <a href="../../auth/login/index.php" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors duration-200">Log in</a>
      </p>
    </div>
  </div>

  <script>lucide.createIcons();</script>
</body>
</html>
