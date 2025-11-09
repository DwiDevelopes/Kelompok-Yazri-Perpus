<?php
session_start();
require_once 'config.php';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id_admin'];
        $_SESSION['admin_nama'] = $admin['nama_lengkap'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="icon.png">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen text-gray-100">
    <div class="bg-gray-800 p-8 rounded-xl shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <i class="fas fa-book text-3xl text-blue-400"></i>
                <h1 class="text-2xl font-bold text-gray-100">Perpustakaan Digital</h1>
            </div>
            <h2 class="text-xl font-semibold text-gray-300">Login Admin</h2>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="bg-red-900 border border-red-600 text-red-300 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-300 text-sm font-bold mb-2" for="username">
                    Username
                </label>
                <input type="text" id="username" name="username" required 
                    class="w-full px-3 py-2 border border-gray-600 bg-gray-700 text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-300 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input type="password" id="password" name="password" required 
                    class="w-full px-3 py-2 border border-gray-600 bg-gray-700 text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <button type="submit" name="login" 
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>
        
        <div class="mt-4 text-center text-sm text-gray-400">
            <p>Default login: <span class="text-blue-400">admin</span> / <span class="text-blue-400">password</span></p>
        </div>
    </div>
</body>
</html>
