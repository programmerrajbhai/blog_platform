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

function is_published(array $p): bool { return (($p['status'] ?? 'draft') === 'published'); }

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
$posts = array_values(array_filter($postsAll, fn($p)=> is_published($p)));

$cat = trim((string)($_GET['cat'] ?? ''));
$tag = trim((string)($_GET['tag'] ?? ''));
$q   = trim((string)($_GET['q'] ?? ''));

if ($cat !== '') {
  $cc = mb_strtolower($cat);
  $posts = array_values(array_filter($posts, fn($p)=> mb_strtolower((string)($p['category'] ?? '')) === $cc));
}

if ($tag !== '') {
  $tt = mb_strtolower($tag);
  $posts = array_values(array_filter($posts, function($p) use ($tt){
    $tags = array_map('mb_strtolower', (array)($p['tags'] ?? []));
    return in_array($tt, $tags, true);
  }));
}

if ($q !== '') {
  $qq = mb_strtolower($q);
  $posts = array_values(array_filter($posts, function($p) use ($qq){
    $title = mb_strtolower((string)($p['title'] ?? ''));
    $slug  = mb_strtolower((string)($p['slug'] ?? ''));
    $tags  = mb_strtolower(implode(',', (array)($p['tags'] ?? [])));
    return strpos($title,$qq)!==false || strpos($slug,$qq)!==false || strpos($tags,$qq)!==false;
  }));
}

usort($posts, fn($a,$b)=> strtotime((string)($b['published_at'] ?? '1970-01-01')) <=> strtotime((string)($a['published_at'] ?? '1970-01-01')));

// pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 8;
$total = count($posts);
$totalPages = max(1, (int)ceil($total/$perPage));
$page = min($page, $totalPages);
$offset = ($page-1)*$perPage;
$list = array_slice($posts, $offset, $perPage);

function page_link(int $p, string $cat, string $tag, string $q): string {
  $params = ['page'=>$p];
  if ($cat !== '') $params['cat'] = $cat;
  if ($tag !== '') $params['tag'] = $tag;
  if ($q !== '') $params['q'] = $q;
  return 'category.php?' . http_build_query($params);
}

$title = $cat !== '' ? $cat : ($tag !== '' ? "Tag: $tag" : "All Tutorials");
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($title) ?> - RajTech</title>

  <meta name="description" content="Browse posts by category, tag and search.">
  <meta property="og:title" content="<?= e($title) ?> - RajTech">
  <meta property="og:description" content="Browse posts by category, tag and search.">

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = { theme:{ extend:{
      fontFamily:{ sans:['Outfit','sans-serif'] },
      colors:{ brand:{ blue:'#2563EB' } },
      animation:{ 'slide-in':'slideIn .45s ease-out both' },
      keyframes:{ slideIn:{ '0%':{opacity:'0',transform:'translateY(10px)'}, '100%':{opacity:'1',transform:'translateY(0)'} } }
    } } }
  </script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

<nav class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-200">
  <div class="container mx-auto px-4 h-16 flex justify-between items-center">
    <a href="index.php" class="text-2xl font-extrabold text-gray-800">Raj<span class="text-brand-blue">Tech</span></a>
    <div class="flex items-center gap-3">
      <a href="admin.php" class="text-sm font-extrabold text-gray-700 hover:text-brand-blue">Admin</a>
      <a href="index.php" class="text-sm font-extrabold text-gray-700 hover:text-brand-blue">Home</a>
    </div>
  </div>
</nav>

<div class="bg-gradient-to-r from-blue-700 via-blue-600 to-cyan-500 py-14 text-center text-white relative overflow-hidden">
  <div class="container mx-auto px-4 relative z-10">
    <h1 class="text-4xl font-extrabold mb-2"><?= e($title) ?></h1>
    <p class="text-blue-100">Total Posts: <?= count($posts) ?></p>
    <form class="mt-6 max-w-xl mx-auto flex gap-2" method="get" action="category.php">
      <?php if($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
      <?php if($tag !== ''): ?><input type="hidden" name="tag" value="<?= e($tag) ?>"><?php endif; ?>
      <input name="q" value="<?= e($q) ?>" placeholder="Search inside this list..."
             class="w-full h-12 px-4 rounded-xl text-gray-900 outline-none">
      <button class="h-12 px-5 rounded-xl bg-white/15 border border-white/30 font-extrabold hover:bg-white/25 transition">Search</button>
    </form>

    <?php if($cat!=='' || $tag!=='' || $q!==''): ?>
      <div class="mt-4 text-sm text-white/90">
        <a class="underline font-extrabold" href="category.php">Clear Filter</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<main class="container mx-auto px-4 py-12 max-w-5xl">

  <div class="space-y-6">
    <?php foreach($list as $idx => $p): ?>
      <article class="flex flex-col sm:flex-row bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-lg transition opacity-0 animate-slide-in group"
               style="animation-delay: <?= (int)($idx*90) ?>ms;">
        <div class="sm:w-64 h-48 sm:h-auto shrink-0 overflow-hidden">
          <img src="<?= e((string)($p['thumb'] ?? $p['image'] ?? '')) ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition duration-500" alt="thumb">
        </div>
        <div class="p-6 flex flex-col justify-center flex-grow">
          <div class="flex items-center text-xs text-gray-400 mb-2 gap-3">
            <span><i class="fa-regular fa-calendar"></i> <?= e(fmt_date((string)($p['published_at'] ?? ''))) ?></span>
            <a href="category.php?cat=<?= rawurlencode((string)($p['category'] ?? '')) ?>" class="font-extrabold hover:text-brand-blue">
              <i class="fa-regular fa-folder"></i> <?= e((string)($p['category'] ?? '')) ?>
            </a>
          </div>

          <h2 class="text-xl font-extrabold text-gray-800 mb-2 group-hover:text-brand-blue transition">
            <a href="single.php?slug=<?= rawurlencode((string)($p['slug'] ?? '')) ?>"><?= e((string)($p['title'] ?? '')) ?></a>
          </h2>

          <p class="text-gray-500 text-sm line-clamp-2 mb-4"><?= e(excerpt_html((string)($p['content'] ?? ''), 150)) ?></p>

          <div class="flex items-center justify-between mt-auto">
            <a href="single.php?slug=<?= rawurlencode((string)($p['slug'] ?? '')) ?>"
               class="text-brand-blue font-extrabold text-sm flex items-center">
              Read <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
            </a>
            <?php $tags = (array)($p['tags'] ?? []); ?>
            <?php if(!empty($tags)): ?>
              <a href="category.php?tag=<?= rawurlencode((string)($tags[0])) ?>" class="text-xs font-extrabold text-gray-600 hover:text-brand-blue">
                #<?= e((string)$tags[0]) ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if(empty($list)): ?>
      <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center text-gray-600">
        <div class="text-2xl font-extrabold text-gray-900">No posts found</div>
        <p class="mt-2">Try different keyword or clear filters.</p>
      </div>
    <?php endif; ?>
  </div>

  <?php if($totalPages > 1): ?>
    <div class="mt-12 flex justify-center gap-2">
      <a class="w-10 h-10 rounded-lg border bg-white hover:bg-gray-50 flex items-center justify-center <?= $page<=1?'pointer-events-none opacity-50':'' ?>"
         href="<?= e(page_link(max(1,$page-1), $cat,$tag,$q)) ?>"><i class="fa-solid fa-chevron-left"></i></a>

      <?php for($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
        <a class="w-10 h-10 rounded-lg flex items-center justify-center font-extrabold <?= $p===$page?'bg-brand-blue text-white shadow-lg shadow-blue-500/30':'border bg-white hover:bg-gray-50' ?>"
           href="<?= e(page_link($p, $cat,$tag,$q)) ?>"><?= $p ?></a>
      <?php endfor; ?>

      <a class="w-10 h-10 rounded-lg border bg-white hover:bg-gray-50 flex items-center justify-center <?= $page>=$totalPages?'pointer-events-none opacity-50':'' ?>"
         href="<?= e(page_link(min($totalPages,$page+1), $cat,$tag,$q)) ?>"><i class="fa-solid fa-chevron-right"></i></a>
    </div>
  <?php endif; ?>
</main>

<footer class="text-center py-8 text-gray-400 text-sm border-t mt-12 bg-white">
  &copy; <?= date('Y') ?> RajTech.
</footer>
</body>
</html>
