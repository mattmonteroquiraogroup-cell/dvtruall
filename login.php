<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded credentials 
    $validUser = 'admin';
    $validPass = '1234';

    if ($username === $validUser && $password === $validPass) {
        $_SESSION['isLoggedIn'] = true;
        header('Location: index.php'); // redirect to your main DV app
        exit;
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign in to Disbursement Portal</title>
 <link rel="icon" href="talogo.ico" type="image/x-icon">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'light-orange': '#fb923c',
            'dark-orange': '#ea580c'
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-800 flex items-center justify-center p-4 relative overflow-hidden">

  <!-- Animated Background -->
  <div class="absolute inset-0 overflow-hidden">
    <!-- Floating Circles -->
    <div class="absolute top-1/4 left-1/4 w-32 h-32 bg-light-orange opacity-10 rounded-full animate-pulse"></div>
    <div class="absolute top-3/4 right-1/4 w-24 h-24 bg-white opacity-5 rounded-full animate-bounce" style="animation-delay: 1s; animation-duration: 3s;"></div>
    <div class="absolute top-1/2 left-1/6 w-16 h-16 bg-light-orange opacity-15 rounded-full animate-ping" style="animation-delay: 2s;"></div>
    
    <!-- Moving Geometric Shapes -->
    <div class="absolute top-10 right-10 w-20 h-20 border-2 border-white opacity-10 rotate-45 animate-spin" style="animation-duration: 20s;"></div>
    <div class="absolute bottom-20 left-10 w-12 h-12 bg-gradient-to-r from-light-orange to-dark-orange opacity-20 transform rotate-12 animate-pulse" style="animation-delay: 1.5s;"></div>
    
    <!-- Floating Dots -->
    <div class="absolute top-1/3 right-1/3 w-3 h-3 bg-white opacity-30 rounded-full animate-bounce" style="animation-delay: 0.5s; animation-duration: 2s;"></div>
    <div class="absolute bottom-1/3 left-1/2 w-2 h-2 bg-light-orange opacity-40 rounded-full animate-ping" style="animation-delay: 3s;"></div>
    <div class="absolute top-2/3 right-1/6 w-4 h-4 bg-white opacity-20 rounded-full animate-pulse" style="animation-delay: 2.5s;"></div>
    
    <!-- Gradient Orbs -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-gradient-to-br from-blue-600 to-transparent opacity-20 rounded-full blur-3xl animate-pulse" style="animation-duration: 4s;"></div>
    <div class="absolute bottom-0 right-0 w-80 h-80 bg-gradient-to-tl from-orange-500 to-transparent opacity-15 rounded-full blur-2xl animate-pulse" style="animation-delay: 2s; animation-duration: 5s;"></div>
  </div>

  <!-- Your Login Form -->
  <div class="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md relative z-10">
    <!-- Logo + Header -->
    <div class="text-center mb-8">
      <img src="talogo.jpg" alt="TA Logo" class="w-20 h-20 mx-auto mb-4">
      <h1 class="text-2xl font-bold text-gray-800 mb-2">Disbursement Portal</h1>
      <p class="text-gray-600">Please sign in to continue</p>
    </div>

    <!-- Error message (PHP) -->
    <?php if (!empty($error)): ?>
      <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" class="space-y-6">
      <div>
        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
        <input type="text" id="username" name="username" required
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          placeholder="Enter your username">
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
        <input type="password" id="password" name="password" required
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
          placeholder="Enter your password">
      </div>

      <button type="submit"
        class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 transition-all duration-200">
        Sign In
      </button>
    </form>
  </div>

</body>
</html>
