<?php
require 'db.php';

// Check ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // FIX: Security check (Prevent viewing drafts)
    $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ? AND (status = 'published' OR (status = 'scheduled' AND scheduled_at <= NOW()))");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) die("Post not found or unavailable!");

    // FETCH RELATED POSTS (Bonus Feature)
    $stmt_rel = $conn->prepare("SELECT * FROM posts WHERE category = ? AND id != ? AND status = 'published' LIMIT 2");
    $stmt_rel->execute([$post['category'], $id]);
    $related_posts = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($post['title']); ?> - RajTech</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: { brand: { blue: '#2563EB', dark: '#0F172A' } }
                }
            }
        }
    </script>
</head>
<body class="bg-white text-slate-800 antialiased">

    <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100">
        <div class="container mx-auto px-4 h-16 flex justify-between items-center">
            <a href="index.php" class="text-xl font-bold tracking-tight text-brand-dark flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-brand-blue text-white flex items-center justify-center text-sm"><i class="fa-solid fa-graduation-cap"></i></span> RajTech
            </a>
            <a href="index.php" class="text-sm font-bold text-gray-500 hover:text-brand-blue flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-10 max-w-4xl">
        
        <header class="mb-10 text-center md:text-left">
            <a href="category.php?name=<?php echo $post['category']; ?>" class="inline-block bg-blue-50 text-brand-blue font-bold uppercase tracking-wider text-xs px-3 py-1 rounded-full mb-4 hover:bg-blue-100 transition">
                <?php echo htmlspecialchars($post['category']); ?>
            </a>
            <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-6">
                <?php echo htmlspecialchars($post['title']); ?>
            </h1>
            <div class="flex items-center justify-center md:justify-start gap-6 text-gray-500 text-sm font-medium border-b border-gray-100 pb-8">
                <span><i class="fa-regular fa-calendar mr-2"></i> <?php echo date("M d, Y", strtotime($post['scheduled_at'] ?? $post['created_at'])); ?></span>
                <span><i class="fa-regular fa-clock mr-2"></i> 5 min read</span>
            </div>
        </header>

        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="w-full h-[400px] object-cover rounded-2xl shadow-xl mb-12">

        <div class="prose prose-lg prose-blue max-w-none text-gray-700 leading-8">
            <?php echo $post['content']; ?>
        </div>

        <div class="my-12 p-8 bg-gray-50 border border-dashed border-gray-300 rounded-xl text-center">
            <span class="text-xs font-bold uppercase text-gray-400 tracking-widest block mb-2">Advertisement</span>
            <div class="h-24 bg-gray-200 rounded animate-pulse w-full"></div>
        </div>

        <?php if(count($related_posts) > 0): ?>
        <div class="mt-16 border-t border-gray-100 pt-10">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">You might also like</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <?php foreach($related_posts as $rel): ?>
                <a href="single.php?id=<?php echo $rel['id']; ?>" class="group flex gap-4 items-center bg-gray-50 p-4 rounded-xl hover:bg-white hover:shadow-md transition border border-transparent hover:border-gray-100">
                    <img src="<?php echo htmlspecialchars($rel['image_url']); ?>" class="w-20 h-20 object-cover rounded-lg">
                    <div>
                        <h4 class="font-bold text-gray-800 group-hover:text-brand-blue line-clamp-2"><?php echo htmlspecialchars($rel['title']); ?></h4>
                        <span class="text-xs text-gray-500 mt-1 block">Read Article &rarr;</span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <footer class="bg-white border-t border-gray-200 py-8 text-center text-sm text-gray-500 mt-12">
        <p>© 2026 RajTech. All rights reserved.</p>
    </footer>

</body>
</html>