<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Dhaka');

$DATA_FILE = __DIR__ . '/data/posts.json';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function read_posts(string $file): array {
  if (!file_exists($file)) return [];
  $j = file_get_contents($file);
  $d = json_decode($j ?: '[]', true);
  return is_array($d) ? $d : [];
}

function is_published(array $p): bool {
  return (($p['status'] ?? 'draft') === 'published');
}

function excerpt_html(string $html, int $limit=150): string {
  $t = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
  if (mb_strlen($t) <= $limit) return $t;
  return mb_substr($t, 0, $limit) . '...';
}

function fmt_date(string $dt): string {
  $ts = strtotime($dt ?: '');
  return $ts ? date('M d, Y', $ts) : date('M d, Y');
}

$postsAll = read_posts($DATA_FILE);

// Published only
$posts = array_values(array_filter($postsAll, fn($p)=> is_published($p)));

// search
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
  $qq = mb_strtolower($q);
  $posts = array_values(array_filter($posts, function($p) use ($qq){
    $title = mb_strtolower((string)($p['title'] ?? ''));
    $cat   = mb_strtolower((string)($p['category'] ?? ''));
    $slug  = mb_strtolower((string)($p['slug'] ?? ''));
    $tags  = implode(',', (array)($p['tags'] ?? []));
    $tags  = mb_strtolower($tags);
    return strpos($title,$qq)!==false || strpos($cat,$qq)!==false || strpos($slug,$qq)!==false || strpos($tags,$qq)!==false;
  }));
}

// newest first
usort($posts, fn($a,$b)=> strtotime((string)($b['published_at'] ?? '1970-01-01')) <=> strtotime((string)($a['published_at'] ?? '1970-01-01')));

$featured = $posts[0] ?? null;

// pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$total = count($posts);
$totalPages = max(1, (int)ceil($total/$perPage));
$page = min($page, $totalPages);
$offset = ($page-1)*$perPage;

$list = array_slice($posts, $offset, $perPage);

function page_link(int $p, string $q): string {
  $params = ['page'=>$p];
  if ($q !== '') $params['q'] = $q;
  return 'index.php?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>RajTech - Home</title>

  <meta name="description" content="RajTech — tutorials, projects and source codes.">
  <meta property="og:title" content="RajTech - Home">
  <meta property="og:description" content="Latest tutorials, projects and source codes.">
  <meta property="og:type" content="website">

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = { theme:{ extend:{
      fontFamily:{ sans:['Outfit','sans-serif'] },
      colors:{ brand:{ blue:'#2563EB', dark:'#0F172A' } },
      animation:{ 'fade-in-up':'fadeInUp .7s ease-out both' },
      keyframes:{ fadeInUp:{ '0%':{opacity:'0',transform:'translateY(18px)'}, '100%':{opacity:'1',transform:'translateY(0)'} } }
    } } }
  </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased selection:bg-brand-blue selection:text-white">

<nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-lg border-b border-gray-100">
  <div class="container mx-auto px-4 h-16 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-extrabold tracking-tight text-gray-900 group">
      <i class="fa-solid fa-layer-group text-brand-blue mr-1 group-hover:rotate-12 transition"></i> Raj<span class="text-brand-blue">Tech</span>
    </a>

    <div class="hidden md:flex space-x-8">
      <a href="index.php" class="text-sm font-extrabold text-gray-700 hover:text-brand-blue transition">Home</a>
      <a href="category.php" class="text-sm font-extrabold text-gray-700 hover:text-brand-blue transition">Tutorials</a>
      <a href="admin.php" class="text-sm font-extrabold text-gray-700 hover:text-brand-blue transition">Admin</a>
    </div>

    <div class="flex items-center gap-3">
      <form method="get" action="index.php" class="hidden md:flex items-center gap-2 bg-gray-100 rounded-xl px-3 h-10">
        <i class="fa-solid fa-magnifying-glass text-gray-500 text-sm"></i>
        <input name="q" value="<?= e($q) ?>" placeholder="Search posts, tags..."
               class="bg-transparent outline-none text-sm w-64">
      </form>

      <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="md:hidden text-xl text-gray-700">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>

  <div id="mobile-menu" class="hidden md:hidden bg-white border-t p-4 space-y-3">
    <form method="get" action="index.php" class="flex items-center gap-2 bg-gray-100 rounded-xl px-3 h-11">
      <i class="fa-solid fa-magnifying-glass text-gray-500 text-sm"></i>
      <input name="q" value="<?= e($q) ?>" placeholder="Search..."
             class="bg-transparent outline-none text-sm w-full">
    </form>
    <a href="index.php" class="block font-extrabold text-gray-700">Home</a>
    <a href="category.php" class="block font-extrabold text-gray-700">Tutorials</a>
    <a href="admin.php" class="block font-extrabold text-gray-700">Admin</a>
  </div>
</nav>

<header class="relative pt-10 pb-16 overflow-hidden">
  <div class="container mx-auto px-4 relative z-10">
    <?php if($featured): ?>
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div class="space-y-5 animate-fade-in-up">
          <span class="inline-block px-3 py-1 rounded-full bg-blue-100 text-brand-blue text-xs font-extrabold tracking-wide uppercase">
            Featured
          </span>
          <h1 class="text-4xl md:text-6xl font-extrabold text-brand-dark leading-tight">
            <?= e((string)$featured['title']) ?>
          </h1>
          <p class="text-lg text-gray-500 leading-relaxed">
            <?= e(excerpt_html((string)($featured['content'] ?? ''), 180)) ?>
          </p>
          <div class="flex gap-4 pt-1">
            <a href="single.php?slug=<?= rawurlencode((string)$featured['slug']) ?>"
               class="px-8 py-3 bg-brand-blue text-white rounded-xl font-extrabold hover:bg-blue-700 transition transform hover:-translate-y-1 shadow-lg shadow-blue-500/30">
              Read Now
            </a>
            <a href="category.php?cat=<?= rawurlencode((string)($featured['category'] ?? '')) ?>"
               class="px-8 py-3 bg-white border border-gray-200 rounded-xl font-extrabold hover:border-brand-blue hover:text-brand-blue transition">
              <?= e((string)($featured['category'] ?? 'Category')) ?>
            </a>
          </div>
          <div class="text-sm text-gray-500">
            <span class="font-extrabold text-gray-800"><?= e((string)($featured['author'] ?? '')) ?></span>
            • <?= e(fmt_date((string)($featured['published_at'] ?? ''))) ?>
            • <?= (int)($featured['read_minutes'] ?? 1) ?> min read
          </div>
        </div>

        <div class="relative group animate-fade-in-up" style="animation-delay:.15s">
          <div class="absolute inset-0 bg-gradient-to-tr from-blue-600 to-purple-500 rounded-3xl blur-2xl opacity-20 group-hover:opacity-30 transition duration-500"></div>
          <img src="<?= e((string)($featured['image'] ?? '')) ?>"
               class="relative rounded-3xl shadow-2xl w-full object-cover transform transition duration-500 group-hover:scale-[1.02]"
               alt="featured">
        </div>
      </div>
    <?php else: ?>
      <div class="bg-white border border-gray-200 rounded-2xl p-8 text-center">
        <div class="text-2xl font-extrabold text-gray-900">No published posts yet</div>
        <p class="text-gray-500 mt-2">Go to <a class="text-brand-blue font-extrabold" href="admin.php">admin</a> and publish a post.</p>
      </div>
    <?php endif; ?>
  </div>
</header>

<main class="container mx-auto px-4 pb-20">
  <div class="flex items-center justify-between mb-8 border-b border-gray-200 pb-4">
    <h2 class="text-2xl font-extrabold text-gray-800">Latest Updates</h2>
    <a href="category.php" class="text-sm font-extrabold text-brand-blue hover:underline">View All <i class="fa-solid fa-arrow-right ml-1"></i></a>
  </div>

  <?php if($q !== ''): ?>
    <div class="mb-6 text-sm text-gray-600">
      Search: <span class="font-extrabold text-gray-900"><?= e($q) ?></span> • Results: <span class="font-extrabold"><?= count($posts) ?></span>
      <a class="ml-2 text-brand-blue font-extrabold hover:underline" href="index.php">Clear</a>
    </div>
  <?php endif; ?>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    <?php foreach($list as $i => $p): ?>
      <article class="bg-white rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 group animate-fade-in-up"
               style="animation-delay: <?= (int)($i*70) ?>ms;">
        <div class="relative h-52 overflow-hidden">
          <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-xs font-extrabold px-3 py-1 rounded-md z-10 shadow-sm">
            <?= e((string)($p['category'] ?? '')) ?>
          </span>
          <img src="<?= e((string)($p['thumb'] ?? $p['image'] ?? '')) ?>"
               class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700"
               alt="thumb">
        </div>
        <div class="p-6">
          <h3 class="text-xl font-extrabold text-gray-800 mb-3 group-hover:text-brand-blue transition leading-snug">
            <a href="single.php?slug=<?= rawurlencode((string)($p['slug'] ?? '')) ?>"><?= e((string)($p['title'] ?? '')) ?></a>
          </h3>
          <p class="text-gray-500 text-sm mb-4 line-clamp-2"><?= e(excerpt_html((string)($p['content'] ?? ''), 140)) ?></p>

          <div class="flex items-center justify-between text-xs text-gray-500">
            <span><?= e(fmt_date((string)($p['published_at'] ?? ''))) ?></span>
            <span class="font-extrabold"><?= (int)($p['read_minutes'] ?? 1) ?> min</span>
          </div>

          <a href="single.php?slug=<?= rawurlencode((string)($p['slug'] ?? '')) ?>"
             class="mt-4 text-brand-blue text-sm font-extrabold inline-flex items-center group/link">
            Read Article <i class="fa-solid fa-arrow-right ml-2 transform group-hover/link:translate-x-1 transition"></i>
          </a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if($totalPages > 1): ?>
    <div class="mt-12 flex justify-center gap-2">
      <a class="w-10 h-10 rounded-lg border bg-white hover:bg-gray-50 flex items-center justify-center <?= $page<=1?'pointer-events-none opacity-50':'' ?>"
         href="<?= e(page_link(max(1,$page-1), $q)) ?>"><i class="fa-solid fa-chevron-left"></i></a>

      <?php for($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
        <a class="w-10 h-10 rounded-lg flex items-center justify-center font-extrabold <?= $p===$page?'bg-brand-blue text-white shadow-lg shadow-blue-500/30':'border bg-white hover:bg-gray-50' ?>"
           href="<?= e(page_link($p, $q)) ?>"><?= $p ?></a>
      <?php endfor; ?>

      <a class="w-10 h-10 rounded-lg border bg-white hover:bg-gray-50 flex items-center justify-center <?= $page>=$totalPages?'pointer-events-none opacity-50':'' ?>"
         href="<?= e(page_link(min($totalPages,$page+1), $q)) ?>"><i class="fa-solid fa-chevron-right"></i></a>
    </div>
  <?php endif; ?>
</main>

<footer class="bg-brand-dark text-white py-12 mt-10">
  <div class="container mx-auto px-4 text-center">
    <h3 class="text-2xl font-extrabold mb-3">RajTech</h3>
    <p class="text-gray-400 text-sm">&copy; <?= date('Y') ?> RajTech. Crafted for Developers.</p>
  </div>
</footer>
</body>
</html>
