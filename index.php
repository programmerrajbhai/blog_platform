<?php
require 'db.php';

// Helper Function: Time Ago
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes      = round($seconds / 60 );
    $hours        = round($seconds / 3600);
    $days         = round($seconds / 86400);

    if($seconds <= 60) return "Just Now";
    else if($minutes <= 60) return ($minutes==1) ? "one min ago" : "$minutes mins ago";
    else if($hours <= 24) return ($hours==1) ? "an hour ago" : "$hours hrs ago";
    else if($days <= 7) return ($days==1) ? "yesterday" : "$days days ago";
    else return date('M d, Y', $time_ago);
}

// Logic: Only show Published/Scheduled posts passed time
$sql = "SELECT * FROM posts 
        WHERE status = 'published' OR (status = 'scheduled' AND scheduled_at <= NOW()) 
        ORDER BY scheduled_at DESC LIMIT 6";
$stmt = $conn->query($sql);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RajTech - Master Real World Skills</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { brand: { blue: '#2563EB', dark: '#0F172A', light: '#F8FAFC' } },
                    animation: { 'fade-up': 'fadeUp 0.6s ease-out forwards', 'pulse-slow': 'pulse 3s infinite' },
                    keyframes: { fadeUp: { '0%': { opacity: '0', transform: 'translateY(20px)' }, '100%': { opacity: '1', transform: 'translateY(0)' } } }
                }
            }
        }
    </script>
    <style>.group:hover .group-hover\:visible { visibility: visible; } .group:hover .group-hover\:opacity-100 { opacity: 1; }</style>
</head>
<body class="bg-gray-50 text-slate-800 antialiased selection:bg-brand-blue selection:text-white">

    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-gray-100 transition-all duration-300">
        <div class="container mx-auto px-4 h-20 flex justify-between items-center">
            
            <a href="index.php" class="text-2xl font-bold tracking-tight text-brand-dark flex items-center gap-2 group">
                <span class="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-blue to-blue-400 text-white flex items-center justify-center text-lg shadow-lg shadow-blue-500/30 group-hover:rotate-6 transition">
                    <i class="fa-solid fa-code"></i>
                </span>
                RajTech
            </a>

            <div class="hidden md:flex items-center space-x-10">
                <a href="index.php" class="text-sm font-bold text-gray-900 hover:text-brand-blue transition">Home</a>
                
                <div class="relative group h-20 flex items-center">
                    <button class="text-sm font-bold text-gray-600 group-hover:text-brand-blue transition flex items-center gap-1">
                        Start Learning <i class="fa-solid fa-chevron-down text-[10px] opacity-50 ml-1"></i>
                    </button>
                    <div class="absolute top-[80%] left-1/2 -translate-x-1/2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 invisible opacity-0 translate-y-4 transition-all duration-300 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0 p-3 z-50">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-4 py-2">Categories</div>
                        <a href="category.php?name=Android" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-blue-50 text-gray-600 hover:text-brand-blue font-medium transition mb-1">
                            <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center"><i class="fa-brands fa-android"></i></div>
                            Android Dev
                        </a>
                        <a href="category.php?name=Web" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-blue-50 text-gray-600 hover:text-brand-blue font-medium transition mb-1">
                            <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center"><i class="fa-solid fa-code"></i></div>
                            Web Dev
                        </a>
                        <a href="category.php?name=Robotics" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-blue-50 text-gray-600 hover:text-brand-blue font-medium transition">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center"><i class="fa-solid fa-microchip"></i></div>
                            Robotics
                        </a>
                    </div>
                </div>

                <a href="#latest" class="text-sm font-bold text-gray-600 hover:text-brand-blue transition">Latest Posts</a>
            </div>

            <button onclick="document.getElementById('mobileMenu').classList.toggle('hidden')" class="md:hidden w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-700 hover:bg-brand-blue hover:text-white transition">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div id="mobileMenu" class="hidden md:hidden bg-white border-t border-gray-100 absolute w-full left-0 shadow-xl z-50">
            <div class="flex flex-col p-6 space-y-4">
                <a href="index.php" class="font-bold text-gray-800 text-lg">Home</a>
                <a href="category.php?name=Android" class="font-medium text-gray-600 flex items-center gap-2"><i class="fa-brands fa-android text-green-500"></i> Android</a>
                <a href="category.php?name=Web" class="font-medium text-gray-600 flex items-center gap-2"><i class="fa-solid fa-code text-orange-500"></i> Web Dev</a>
                <a href="category.php?name=Robotics" class="font-medium text-gray-600 flex items-center gap-2"><i class="fa-solid fa-microchip text-purple-500"></i> Robotics</a>
            </div>
        </div>
    </nav>

    <header class="relative pt-20 pb-32 bg-white overflow-hidden">
        
        <div class="absolute top-0 left-1/2 w-full -translate-x-1/2 h-full z-0 pointer-events-none">
            <div class="absolute top-20 left-10 w-72 h-72 bg-blue-400/20 rounded-full blur-[100px] animate-pulse-slow"></div>
            <div class="absolute bottom-10 right-10 w-96 h-96 bg-purple-400/20 rounded-full blur-[100px] animate-pulse-slow" style="animation-delay: 1s;"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10 text-center max-w-5xl">
            
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 text-brand-blue text-xs font-bold uppercase tracking-wide mb-8 animate-fade-up shadow-sm">
                <span class="relative flex h-2.5 w-2.5">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                The #1 Platform for Tech Skills
            </div>

            <h1 class="text-5xl md:text-7xl font-extrabold text-brand-dark leading-tight mb-8 animate-fade-up" style="animation-delay: 0.1s;">
                Turn Your Ideas into <br class="hidden md:block" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-brand-blue via-blue-500 to-purple-600">Real-World Innovation</span>
            </h1>

            <p class="text-lg md:text-xl text-gray-500 mb-10 max-w-2xl mx-auto leading-relaxed animate-fade-up" style="animation-delay: 0.2s;">
                Stop watching boring tutorials. Start building with our high-quality source codes for Android, Web, and IoT projects. <span class="text-gray-900 font-bold">Completely Free.</span>
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center animate-fade-up" style="animation-delay: 0.3s;">
                <a href="#latest" class="px-8 py-4 bg-brand-blue text-white rounded-xl font-bold shadow-xl shadow-blue-500/30 hover:bg-blue-600 hover:scale-105 transition transform flex items-center justify-center gap-2">
                    <i class="fa-solid fa-rocket"></i> Start Learning
                </a>
                <a href="category.php?name=Android" class="px-8 py-4 bg-white text-gray-700 border border-gray-200 rounded-xl font-bold hover:bg-gray-50 hover:border-gray-300 transition flex items-center justify-center gap-2">
                    <i class="fa-brands fa-github"></i> View Projects
                </a>
            </div>

            <div class="mt-16 pt-8 border-t border-gray-100/50 flex flex-wrap justify-center gap-8 md:gap-16 text-gray-400 opacity-80 animate-fade-up" style="animation-delay: 0.4s;">
                <div class="flex items-center gap-2 grayscale hover:grayscale-0 transition duration-300">
                    <i class="fa-brands fa-android text-2xl text-green-500"></i> <span class="font-bold text-sm text-gray-600">Android Studio</span>
                </div>
                <div class="flex items-center gap-2 grayscale hover:grayscale-0 transition duration-300">
                    <i class="fa-brands fa-laravel text-2xl text-red-500"></i> <span class="font-bold text-sm text-gray-600">Laravel</span>
                </div>
                <div class="flex items-center gap-2 grayscale hover:grayscale-0 transition duration-300">
                    <i class="fa-brands fa-react text-2xl text-blue-400"></i> <span class="font-bold text-sm text-gray-600">React JS</span>
                </div>
                <div class="flex items-center gap-2 grayscale hover:grayscale-0 transition duration-300">
                    <i class="fa-solid fa-microchip text-2xl text-purple-500"></i> <span class="font-bold text-sm text-gray-600">Arduino IoT</span>
                </div>
            </div>

        </div>
    </header>

    <main id="latest" class="container mx-auto px-4 py-20 bg-white">
        
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-4">
            <div>
                <h2 class="text-3xl font-bold text-brand-dark">Latest Updates</h2>
                <p class="text-gray-500 mt-2">Fresh tutorials added this week</p>
            </div>
            <a href="category.php" class="text-brand-blue font-bold hover:underline flex items-center gap-2">
                View All Posts <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($posts as $i => $post): ?>
            <article class="bg-white rounded-3xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1 transition-all duration-300 flex flex-col h-full group animate-fade-up" style="animation-delay: <?php echo $i * 100; ?>ms">
                
                <div class="relative h-56 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition duration-500 z-10"></div>
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700">
                    
                    <span class="absolute top-4 right-4 bg-white/95 backdrop-blur text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow-sm text-brand-dark uppercase tracking-wider z-20">
                        <?php echo htmlspecialchars($post['category']); ?>
                    </span>
                </div>

                <div class="p-7 flex flex-col flex-1">
                    <div class="flex items-center gap-2 mb-3 text-xs text-gray-400 font-bold uppercase tracking-wider">
                        <i class="fa-regular fa-clock text-brand-blue"></i> <?php echo time_ago($post['scheduled_at'] ?? $post['created_at']); ?>
                    </div>
                    
                    <h3 class="text-xl font-bold text-brand-dark mb-3 leading-snug group-hover:text-brand-blue transition-colors">
                        <a href="single.php?id=<?php echo $post['id']; ?>" class="block">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </h3>
                    
                    <p class="text-gray-500 text-sm line-clamp-2 mb-6 leading-relaxed">
                        <?php echo htmlspecialchars($post['excerpt']); ?>
                    </p>
                    
                    <div class="mt-auto border-t border-gray-50 pt-5 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-500">RA</div>
                            <span class="text-xs font-bold text-gray-500">Raj Ahmad</span>
                        </div>
                        <a href="single.php?id=<?php echo $post['id']; ?>" class="w-10 h-10 rounded-full bg-blue-50 text-brand-blue flex items-center justify-center hover:bg-brand-blue hover:text-white transition shadow-sm">
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="bg-brand-dark text-white py-16 border-t border-white/10">
        <div class="container mx-auto px-4 text-center">
            <div class="inline-flex items-center gap-2 mb-6">
                <span class="w-8 h-8 rounded-lg bg-brand-blue text-white flex items-center justify-center text-sm"><i class="fa-solid fa-code"></i></span>
                <span class="text-2xl font-bold tracking-tight">RajTech</span>
            </div>
            <p class="text-gray-400 max-w-md mx-auto text-sm mb-8 leading-relaxed">
                Empowering the next generation of developers with free, high-quality, and project-based education.
            </p>
            
            <div class="flex justify-center gap-6 mb-8">
                <a href="#" class="text-gray-400 hover:text-white transition transform hover:scale-110"><i class="fa-brands fa-facebook text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition transform hover:scale-110"><i class="fa-brands fa-youtube text-xl"></i></a>
                <a href="#" class="text-gray-400 hover:text-white transition transform hover:scale-110"><i class="fa-brands fa-github text-xl"></i></a>
            </div>

            <p class="text-gray-500 text-xs border-t border-white/10 pt-8">
                &copy; 2026 RajTech. Designed for AdSense Approval.
            </p>
        </div>
    </footer>

</body>
</html>