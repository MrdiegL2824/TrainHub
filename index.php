<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrainHub-Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white flex items-center justify-center min-h-screen font-sans">

    <div class="bg-[#E2E8F0] w-full max-w-[420px] p-10 rounded-[28px] shadow-sm mx-4">
        
        <h2 class="text-[#2D3748] text-center text-3xl font-bold mb-8">Faça seu login</h2>
        
        <form action="api/auth.php" method="POST" class="space-y-5">
            
            <div>
                <label for="email" class="block text-[#3B4270] font-bold text-base mb-2 ml-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full bg-white h-12 px-4 rounded-full border-none outline-none text-gray-700 shadow-sm focus:ring-2 focus:ring-[#3B4270]">
            </div>
            
            <div>
                <label for="senha" class="block text-[#3B4270] font-bold text-base mb-2 ml-1">Senha</label>
                <input type="password" id="senha" name="senha" required
                    class="w-full bg-white h-12 px-4 rounded-full border-none outline-none text-gray-700 shadow-sm focus:ring-2 focus:ring-[#3B4270]">
                
                <a href="#" class="block text-red-500 text-xs font-semibold mt-2 ml-1 hover:underline">Esqueci minha senha</a>
            </div>
            
            <div class="pt-4 space-y-4">
                <button type="submit" 
                    class="w-full bg-[#3B4E7A] text-white font-bold text-xl py-3 rounded-[14px] shadow-md hover:bg-[#2D3E63] transition duration-200">
                    Entrar
                </button>
                
                <p class="text-center text-xs text-gray-600 font-medium">Não tem uma conta?</p>
                
                <a href="cadastro.php" 
                    class="block text-center w-full bg-[#3B4E7A] text-white font-bold text-xl py-3 rounded-[14px] shadow-md hover:bg-[#2D3E63] transition duration-200">Cadastrar</a>
            </div>

        </form>
    </div>

</body>
</html>