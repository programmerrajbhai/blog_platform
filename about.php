<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - RajTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { brand: { blue: '#2563EB', dark: '#0F172A', light: '#F8FAFC' } }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-800 antialiased flex flex-col min-h-screen">

    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-gray-100">
        <div class="container mx-auto px-4 h-20 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold tracking-tight text-brand-dark flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-brand-blue text-white flex items-center justify-center text-lg shadow-lg shadow-blue-500/30">
                    <i class="fa-solid fa-code"></i>
                </span>
                RajTech
            </a>
            <a href="index.php" class="text-sm font-bold text-gray-500 hover:text-brand-blue flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-16 max-w-4xl text-center md:text-left">
        
        <div class="flex flex-col md:flex-row items-center gap-12 mb-16">
            <div class="md:w-1/2">
                <span class="text-brand-blue font-bold uppercase tracking-wider text-sm mb-2 block">Who We Are</span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-brand-dark leading-tight mb-6">
                    Building the Future with <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-blue to-purple-600">Code & Innovation</span>
                </h1>
                <p class="text-lg text-gray-500 leading-relaxed mb-6">
                    Welcome to <strong>RajTech</strong>. I am Raj Ahmad, a passionate Full Stack Developer and Robotics Enthusiast. 
                    My mission is to simplify complex programming concepts and make technology accessible to everyone.
                </p>
                <div class="flex flex-wrap gap-4 justify-center md:justify-start">
                    <div class="flex items-center gap-2 px-4 py-2 bg-blue-50 text-brand-blue rounded-lg font-bold text-sm">
                        <i class="fa-brands fa-android"></i> Android Dev
                    </div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-orange-50 text-orange-600 rounded-lg font-bold text-sm">
                        <i class="fa-solid fa-code"></i> Web Dev
                    </div>
                    <div class="flex items-center gap-2 px-4 py-2 bg-purple-50 text-purple-600 rounded-lg font-bold text-sm">
                        <i class="fa-solid fa-robot"></i> IoT & Robotics
                    </div>
                </div>
            </div>
            <div class="md:w-1/2 relative">
                <div class="absolute inset-0 bg-brand-blue/10 rounded-3xl transform rotate-3"></div>
                <img src="https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=800&auto=format&fit=crop" class="relative rounded-3xl shadow-2xl w-full object-cover">
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-8 text-center">
            <div class="p-6 border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-blue-100 text-brand-blue rounded-full flex items-center justify-center mx-auto mb-4 text-xl"><i class="fa-solid fa-graduation-cap"></i></div>
                <h3 class="font-bold text-xl mb-2">Free Education</h3>
                <p class="text-gray-500 text-sm">Providing high-quality tutorials absolutely free for students.</p>
            </div>
            <div class="p-6 border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl"><i class="fa-solid fa-box-open"></i></div>
                <h3 class="font-bold text-xl mb-2">Open Source</h3>
                <p class="text-gray-500 text-sm">Real-world project source codes available for everyone.</p>
            </div>
            <div class="p-6 border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl"><i class="fa-solid fa-users"></i></div>
                <h3 class="font-bold text-xl mb-2">Community</h3>
                <p class="text-gray-500 text-sm">Helping thousands of developers build their dream careers.</p>
            </div>
        </div>

    </main>

    <footer class="bg-brand-dark text-white py-8 text-center text-sm border-t border-white/10 mt-auto">
        <p>&copy; 2026 RajTech. All rights reserved.</p>
    </footer>

</body>
</html>