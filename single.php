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

function fmt_date_full(string $dt): string {
  $ts = strtotime($dt ?: '');
  return $ts ? date('M d, Y • h:i A', $ts) : date('M d, Y • h:i A');
}

function is_published(array $p): bool { return (($p['status'] ?? 'draft') === 'published'); }

function excerpt_html(string $html, int $limit=160): string {
  $t = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
  if (mb_strlen($t) <= $limit) return $t;
  return mb_substr($t, 0, $limit) . '...';
}

function build_toc(string $html): array {
  // create ids for h2/h3 and return toc + updated html
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED);
  $xpath = new DOMXPath($dom);

  $nodes = $xpath->query('//h2|//h3');
  $toc = [];
  $used = [];

  foreach ($nodes as $node) {
    $tag = strtolower($node->nodeName);
    $text = trim($node->textContent ?? '');
    if ($text === '') continue;

    $base = preg_replace('~[^\pL\pN]+~u', '-', mb_strtolower($text));
    $base = trim($base, '-') ?: 'section';
    $id = $base;
    $n = 2;
    while (isset($used[$id])) { $id = $base . '-' . $n; $n++; }
    $used[$id] = true;

    $node->setAttribute('id', $id);
    $toc[] = ['level'=>$tag, 'id'=>$id, 'text'=>$text];
  }

  $newHtml = $dom->saveHTML() ?: $html;
  return [$toc, $newHtml];
}

$slug = trim((string)($_GET['slug'] ?? ''));
$token = trim((string)($_GET['token'] ?? ''));

$postsAll = read_posts($DATA_FILE);

// find by slug
$post = null;
foreach ($postsAll as $p) {
  if (($p['slug'] ?? '') === $slug) { $post = $p; break; }
}

if (!$post) {
  http_response_code(404);
  echo "<h1 style='font-family:Arial'>404 - Post not found</h1>";
  exit();
}

// draft rules: allow only with token
if (!is_published($post)) {
  if ($token === '' || $token !== (string)($post['preview_token'] ?? '')) {
    http_response_code(404);
    echo "<h1 style='font-family:Arial'>404 - Draft not accessible</h1>";
    exit();
  }
}

[$toc, $contentWithIds] = build_toc((string)($post['content'] ?? ''));

// related posts by tags
$tags = (array)($post['tags'] ?? []);
$related = [];
if (!empty($tags)) {
  foreach ($postsAll as $p) {
    if (($p['id'] ?? '') === ($post['id'] ?? '')) continue;
    if (($p['status'] ?? 'draft') !== 'published') continue;
    $pt = (array)($p['tags'] ?? []);
    $score = count(array_intersect($tags, $pt));
    if ($score > 0) $related[] = ['p'=>$p, 's'=>$score];
  }
  usort($related, fn($a,$b)=> $b['s'] <=> $a['s']);
  $related = array_slice($related, 0, 4);
}

$seoTitle = trim((string)($post['seo_title'] ?? ''));
$seoDesc  = trim((string)($post['seo_desc'] ?? ''));
if ($seoTitle === '') $seoTitle = (string)($post['title'] ?? '');
if ($seoDesc === '') $seoDesc = excerpt_html((string)($post['content'] ?? ''), 160);

$ogImg = (string)($post['meta_image'] ?? $post['image'] ?? '');
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <title><?= e($seoTitle) ?></title>
  <meta name="description" content="<?= e($seoDesc) ?>">

  <meta property="og:title" content="<?= e($seoTitle) ?>">
  <meta property="og:description" content="<?= e($seoDesc) ?>">
  <meta property="og:type" content="article">
  <?php if($ogImg): ?><meta property="og:image" content="<?= e($ogImg) ?>"><?php endif; ?>

  <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = { theme:{ extend:{
      fontFamily:{ sans:['Outfit','sans-serif'], mono:['JetBrains Mono','monospace'] },
      colors:{ brand:{ blue:'#2563EB' } }
    } } }
  </script>
</head>
<body class="bg-white text-slate-800 antialiased">

<div class="fixed top-0 left-0 h-1 bg-brand-blue z-[100] transition-all duration-200" id="progress-bar" style="width:0%"></div>

<nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-100">
  <div class="container mx-auto px-4 h-16 flex justify-between items-center">
    <a href="index.php" class="text-sm font-extrabold text-gray-800"><i class="fa-solid fa-arrow-left mr-2"></i> Back</a>
    <div class="flex items-center gap-3">
      <a href="category.php?cat=<?= rawurlencode((string)($post['category'] ?? '')) ?>" class="text-xs font-extrabold text-brand-blue bg-blue-50 px-3 py-1 rounded-full">
        <?= e((string)($post['category'] ?? '')) ?>
      </a>
      <a href="admin.php" class="text-sm font-extrabold text-gray-800 hover:text-brand-blue">Admin</a>
    </div>
  </div>
</nav>

<div class="container mx-auto px-4 py-10 max-w-6xl">
  <div class="flex flex-col lg:flex-row gap-12">

    <article class="lg:w-[70%]">
      <header class="mb-9">
        <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 leading-tight mb-5"><?= e((string)($post['title'] ?? '')) ?></h1>

        <div class="flex items-center gap-4 text-gray-500 text-sm border-b border-gray-100 pb-7">
          <img src="https://ui-avatars.com/api/?name=<?= rawurlencode((string)($post['author'] ?? 'RajTech')) ?>&background=2563EB&color=fff" class="w-10 h-10 rounded-full" alt="author">
          <div>
            <p class="font-extrabold text-gray-900"><?= e((string)($post['author'] ?? '')) ?></p>
            <p><?= e(fmt_date_full((string)($post['published_at'] ?? ''))) ?> • <?= (int)($post['read_minutes'] ?? 1) ?> min read</p>
          </div>
          <?php if(($post['status'] ?? 'draft') === 'draft'): ?>
            <span class="ml-auto px-3 py-1 rounded-full text-xs font-extrabold bg-orange-100 text-orange-700">DRAFT PREVIEW</span>
          <?php endif; ?>
        </div>
      </header>

      <?php if(!empty($post['image'])): ?>
        <img src="<?= e((string)($post['image'])) ?>" class="rounded-2xl w-full mb-9 shadow-lg" alt="cover">
      <?php endif; ?>

      <div class="prose prose-lg prose-blue max-w-none text-gray-700 leading-8">
        <?= $contentWithIds ?>
      </div>

      <!-- Tags -->
      <?php if(!empty($tags)): ?>
        <div class="mt-10 pt-6 border-t">
          <div class="font-extrabold text-gray-900 mb-3">Tags</div>
          <div class="flex flex-wrap gap-2">
            <?php foreach($tags as $t): ?>
              <a href="category.php?tag=<?= rawurlencode((string)$t) ?>" class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-extrabold hover:bg-blue-50 hover:text-brand-blue transition">
                #<?= e((string)$t) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Related -->
      <?php if(!empty($related)): ?>
        <div class="mt-12 pt-8 border-t">
          <h3 class="text-xl font-extrabold text-gray-900 mb-5">Related Posts</h3>
          <div class="grid sm:grid-cols-2 gap-6">
            <?php foreach($related as $rp): $p=$rp['p']; ?>
              <a href="single.php?slug=<?= rawurlencode((string)($p['slug'] ?? '')) ?>"
                 class="group rounded-2xl border border-gray-200 overflow-hidden hover:shadow-lg transition bg-white">
                <div class="h-40 overflow-hidden">
                  <img src="<?= e((string)($p['thumb'] ?? $p['image'] ?? '')) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700" alt="thumb">
                </div>
                <div class="p-4">
                  <div class="text-xs text-gray-500 font-extrabold"><?= e((string)($p['category'] ?? '')) ?> • <?= e(date('M d, Y', strtotime((string)($p['published_at'] ?? '')))) ?></div>
                  <div class="mt-2 font-extrabold text-gray-900 group-hover:text-brand-blue transition"><?= e((string)($p['title'] ?? '')) ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </article>

    <aside class="lg:w-[30%] space-y-8">
      <div class="sticky top-24 space-y-8">

        <!-- TOC -->
        <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
          <h4 class="font-extrabold text-gray-900 mb-4">Table of Contents</h4>
          <?php if(empty($toc)): ?>
            <div class="text-sm text-gray-500">No headings found (use H2/H3).</div>
          <?php else: ?>
            <ul class="space-y-2 text-sm text-gray-600">
              <?php foreach($toc as $it): ?>
                <li class="<?= $it['level']==='h3'?'pl-4':'' ?>">
                  <a class="block px-3 py-2 rounded-xl hover:bg-white hover:text-brand-blue transition font-extrabold"
                     href="#<?= e($it['id']) ?>">
                    <?= e($it['text']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl border border-dashed border-gray-300 p-6 text-center text-gray-400">
          Ad Space (300x250)
        </div>
      </div>
    </aside>

  </div>
</div>

<script>
  // Progress Bar
  window.addEventListener('scroll', () => {
    const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = height > 0 ? (winScroll / height) * 100 : 0;
    document.getElementById("progress-bar").style.width = scrolled + "%";
  });

  // Code copy: copy code blocks on click
  document.querySelectorAll('pre').forEach(pre=>{
    pre.style.position='relative';
    const btn = document.createElement('button');
    btn.className = "absolute top-3 right-3 text-xs font-bold bg-white/10 text-white px-3 py-1 rounded hover:bg-brand-blue transition";
    btn.innerText = "Copy";
    btn.addEventListener('click', async (e)=>{
      e.preventDefault(); e.stopPropagation();
      const code = pre.innerText;
      await navigator.clipboard.writeText(code);
      btn.innerText = "Copied!";
      setTimeout(()=>btn.innerText="Copy", 1500);
    });
    // dark style
    pre.classList.add('bg-slate-900','text-slate-100','rounded-xl','p-4','overflow-x-auto');
    pre.appendChild(btn);
  });
</script>
</body>
</html>
