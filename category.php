<?php
require 'db.php';

$cat_name = isset($_GET['name']) ? $_GET['name'] : '';

// FIX: Security & Logic Update
$sql = "SELECT * FROM posts WHERE (status = 'published' OR (status = 'scheduled' AND scheduled_at <= NOW()))";

if($cat_name) {
    $sql .= " AND category LIKE :cat ORDER BY scheduled_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':cat' => "%$cat_name%"]);
} else {
    $sql .= " ORDER BY scheduled_at DESC";
    $stmt = $conn->query($sql);
    $cat_name = "All Courses";
}

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($cat_name); ?> - RajTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] }, colors: { brand: { blue: '#2563EB', dark: '#0F172A' } } } } }
    </script>
</head>
<body class="bg-gray-50 text-slate-800 antialiased">

    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-xl border-b border-gray-100">
        <div class="container mx-auto px-4 h-20 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold tracking-tight text-brand-dark flex items-center gap-2">
                <span class="w-10 h-10 rounded-xl bg-brand-blue text-white flex items-center justify-center text-lg"><i class="fa-solid fa-graduation-cap"></i></span> RajTech
            </a>
            <a href="index.php" class="text-sm font-medium text-gray-500 hover:text-brand-blue">Back Home</a>
        </div>
    </nav>

    <div class="bg-brand-dark text-white py-16 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-brand-blue/20 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2"></div>
        <div class="container mx-auto px-4 relative z-10">
            <p class="text-brand-blue font-bold uppercase tracking-widest text-xs mb-2">Browsing Category</p>
            <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo htmlspecialchars($cat_name); ?></h1>
            <p class="text-gray-400">Found <?php echo count($posts); ?> tutorials available.</p>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 flex flex-col md:flex-row gap-10">
        
        <aside class="md:w-64 shrink-0 space-y-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm sticky top-24">
                <h3 class="font-bold text-gray-800 mb-4">Categories</h3>
                <nav class="space-y-2">
                    <a href="category.php?name=Android" class="block px-4 py-2 rounded-lg <?php echo strpos($cat_name, 'Android') !== false ? 'bg-blue-50 text-brand-blue font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">Android Dev</a>
                    <a href="category.php?name=Web" class="block px-4 py-2 rounded-lg <?php echo strpos($cat_name, 'Web') !== false ? 'bg-blue-50 text-brand-blue font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">Web Dev</a>
                    <a href="category.php?name=Robotics" class="block px-4 py-2 rounded-lg <?php echo strpos($cat_name, 'Robotics') !== false ? 'bg-blue-50 text-brand-blue font-bold' : 'text-gray-600 hover:bg-gray-50'; ?>">Robotics</a>
                </nav>
            </div>
            
            <div class="bg-gray-100 rounded-2xl h-60 flex items-center justify-center text-gray-400 border border-dashed border-gray-300">
                Ad Space
            </div>
        </aside>

        <div class="flex-1 space-y-6">
            <?php if(count($posts) > 0): ?>
                <?php foreach($posts as $post): ?>
                <article class="flex flex-col sm:flex-row bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-md transition group">
                    <div class="sm:w-60 h-48 sm:h-auto shrink-0 relative">
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-6 flex flex-col justify-center">
                        <div class="flex items-center gap-2 text-xs text-gray-400 mb-2">
                            <span class="font-bold text-brand-blue uppercase"><?php echo htmlspecialchars($post['category']); ?></span>
                            <span>&bull; <?php echo date("M d", strtotime($post['scheduled_at'] ?? $post['created_at'])); ?></span>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2 group-hover:text-brand-blue transition">
                            <a href="single.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                        </h2>
                        <p class="text-gray-500 text-sm line-clamp-2 mb-4"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                        <a href="single.php?id=<?php echo $post['id']; ?>" class="text-sm font-bold text-brand-blue flex items-center gap-1">Start Lesson <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-2xl border border-gray-100">
                    <i class="fa-regular fa-folder-open text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No content found in this category yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>